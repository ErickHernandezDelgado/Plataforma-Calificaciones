<?php
/**
 * get_student.php
 * Endpoint AJAX para cargar estudiantes y materias por grupo
 * Soporta filtrado por idioma: ?lang=es|en
 * ESTRUCTURA REAL: tblsubjects(id, SubjectName, SubjectCode, Language, ...), tblresult(id, StudentId, ClassId, SubjectId, marks, Trimestre, term)
 */
include(__DIR__ . '/includes/check-login.php');

// PARÁMETRO DE IDIOMA
$lang = isset($_GET['lang']) && $_GET['lang'] == 'en' ? 'en' : 'es';

// 1. CARGA DE ESTUDIANTES PARA EL SELECTOR (Dropdown)
if (!empty($_POST["classid"])) {
    $classid = intval($_POST['classid']);
    
    $stmt = $dbh->prepare("SELECT StudentName, StudentId FROM tblstudents WHERE ClassId = :id AND Status = 1 ORDER BY StudentName");
    $stmt->execute([':id' => $classid]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo '<option value="">Seleccionar Estudiante</option>';
    if (count($students) > 0) {
        foreach ($students as $student) {
            echo '<option value="' . htmlentities($student['StudentId']) . '">' . htmlentities($student['StudentName']) . '</option>';
        }
    } else {
        $no_students_msg = ($lang == 'en') ? 'No students in this class' : 'No hay alumnos en este grupo';
        echo '<option value="">' . $no_students_msg . '</option>';
    }
}

// 2. CARGA DE MATERIAS (Crea los inputs de calificación)
if (!empty($_POST["classid1"])) {
    $classid1 = intval($_POST['classid1']);

    // Obtener educationLevel de tblclasses
    $sql_period = "SELECT educationLevel FROM tblclasses WHERE id = :classid";
    $stmt_period = $dbh->prepare($sql_period);
    $stmt_period->execute([':classid' => $classid1]);
    $period_info = $stmt_period->fetch(PDO::FETCH_ASSOC);

    // Determinar tipo de período basado en educationLevel
    // Secundaria: trimestres (3). Todos los demás niveles: bimestres (4).
    $label = ($lang == 'en') ? "Subjects" : "Materias";
    if ($period_info) {
        if ($period_info['educationLevel'] === 'secundaria') {
            $label = ($lang == 'en') ? "Subjects (3 Trimesters)" : "Trimestrales (3 períodos)";
        } else {
            $label = ($lang == 'en') ? "Subjects (4 Bimonthly)" : "Bimestrales (4 períodos)";
        }
    }

    // Obtener Materias del grupo (filtradas por idioma usando Language enum)
    $lang_filter = ($lang == 'en') ? 'en' : 'es';
    $session_role     = $_SESSION['role']      ?? null;
    $session_teacherid = $_SESSION['teacherid'] ?? null;

    if ($session_role === 'teacher' && $session_teacherid) {
        // Maestro: solo materias asignadas en tblteacher_subject para este grupo
        $stmt2 = $dbh->prepare("SELECT SubjectName, id as SubjectId
                                FROM tblsubjects
                                WHERE id IN (
                                    SELECT SubjectId FROM tblsubjectcombination
                                    WHERE ClassId = :id AND status = 1
                                )
                                AND id IN (
                                    SELECT SubjectId FROM tblteacher_subject
                                    WHERE TeacherId = :tid AND ClassId = :id
                                )
                                AND Language = :lang
                                ORDER BY SubjectName");
        $stmt2->execute([':id' => $classid1, ':tid' => $session_teacherid, ':lang' => $lang_filter]);
    } else {
        // Admin: todas las materias del grupo
        $stmt2 = $dbh->prepare("SELECT SubjectName, id as SubjectId
                                FROM tblsubjects
                                WHERE id IN (
                                    SELECT SubjectId FROM tblsubjectcombination
                                    WHERE ClassId = :id AND status = 1
                                )
                                AND Language = :lang
                                ORDER BY SubjectName");
        $stmt2->execute([':id' => $classid1, ':lang' => $lang_filter]);
    }
    $subjects = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    if (count($subjects) > 0) {
        echo '<h5 class="form-section-title">Carga Académica (' . htmlentities($label) . '):</h5>';
        
        foreach ($subjects as $subject) {
            echo '<div class="row" style="margin-bottom:15px;">
                    <div class="col-md-8">
                        <p style="margin-top:7px; font-weight:600;">' . htmlentities($subject['SubjectName']) . '</p>
                    </div>
                    <div class="col-md-4">
                        <input type="number" name="marks[]" class="form-control" placeholder="0-100" min="0" max="100" step="1" required>
                    </div>
                  </div>';
        }
    } else {
        $no_subjects_msg = ($lang == 'en') ? 'No subjects assigned to this class.' : 'No hay materias asignadas a este grupo.';
        echo '<p class="text-danger">' . $no_subjects_msg . '</p>';
    }
}

// 3. VALIDACIÓN DE DUPLICADOS (estructura real: StudentId, ClassId, SubjectId, term)
if (!empty($_POST["studclass"])) {
    $data = explode("$", $_POST['studclass']);
    if(count($data) >= 3) {
        $cid = intval($data[0]);
        $sid = intval($data[1]);
        // El JS envía el período como "type|number" (1=Bimestre, 2=Trimestre).
        // Se compara por el texto exacto de la columna Trimestre para NO confundir
        // "Bimestre 1" con "Trimestre 1" (ambos tendrían term=1).
        $periodo_parts = explode("|", $data[2]);
        $p_type = intval($periodo_parts[0] ?? 0);
        $p_num  = intval($periodo_parts[1] ?? 0);
        $trimestre_text = ($p_type == 1) ? "Bimestre " . $p_num : "Trimestre " . $p_num;

        // Buscar en tblresult usando StudentId, ClassId, Trimestre (texto) Y el idioma de la materia
        $dup_role      = $_SESSION['role']      ?? null;
        $dup_teacherid = $_SESSION['teacherid'] ?? null;

        if ($dup_role === 'teacher' && $dup_teacherid) {
            // Maestro: solo verifica duplicados en sus materias asignadas
            $sql = "SELECT tr.id FROM tblresult tr
                    JOIN tblsubjects ts ON ts.id = tr.SubjectId
                    WHERE tr.StudentId = :sid AND tr.ClassId = :cid AND tr.Trimestre = :trim
                    AND ts.Language = :lang
                    AND tr.SubjectId IN (
                        SELECT SubjectId FROM tblteacher_subject
                        WHERE TeacherId = :tid AND ClassId = :cid
                    )
                    LIMIT 1";
        } else {
            $sql = "SELECT tr.id FROM tblresult tr
                    JOIN tblsubjects ts ON ts.id = tr.SubjectId
                    WHERE tr.StudentId = :sid AND tr.ClassId = :cid AND tr.Trimestre = :trim
                    AND ts.Language = :lang
                    LIMIT 1";
        }
        $query = $dbh->prepare($sql);
        if ($dup_role === 'teacher' && $dup_teacherid) {
            $query->execute([':sid' => $sid, ':cid' => $cid, ':trim' => $trimestre_text, ':lang' => $lang, ':tid' => $dup_teacherid]);
        } else {
            $query->execute([':sid' => $sid, ':cid' => $cid, ':trim' => $trimestre_text, ':lang' => $lang]);
        }

        if ($query->rowCount() > 0) {
            $duplicate_msg = ($lang == 'en') ? 'This student already has grades recorded for this period.' : 'El alumno ya cuenta con resultados registrados para este período.';
            echo '<div class="alert alert-warning" style="margin-top:10px;">
                    <i class="fa fa-exclamation-triangle"></i> ' . $duplicate_msg . '
                  </div>';
        }
    }
}
?>