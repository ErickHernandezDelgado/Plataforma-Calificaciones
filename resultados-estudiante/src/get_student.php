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

    // Determinar tipo de período basado en educationLevel.
    // Primaria y secundaria: 3 trimestres. Maternal/kinder/preprimaria: 5 bimestres.
    $level = $period_info['educationLevel'] ?? null;
    $isTrimestral = in_array($level, ['primaria', 'secundaria'], true);
    $numPeriodos  = $isTrimestral ? 3 : 5;
    $label = ($lang == 'en') ? "Subjects" : "Materias";
    if ($period_info) {
        if ($isTrimestral) {
            $label = ($lang == 'en') ? "Subjects (3 Trimesters)" : "Trimestrales (3 períodos)";
        } else {
            $label = ($lang == 'en') ? "Subjects (5 Bimonthly)" : "Bimestrales (5 períodos)";
        }
    }

    // Obtener Materias del grupo (filtradas por idioma usando Language enum)
    $lang_filter = ($lang == 'en') ? 'en' : 'es';
    $session_role     = $_SESSION['role']      ?? null;
    $session_teacherid = $_SESSION['teacherid'] ?? null;

    if ($session_role === 'teacher' && $session_teacherid) {
        // Maestro: solo materias asignadas en tblteacher_subject para este grupo
        $stmt2 = $dbh->prepare("SELECT SubjectName, id as SubjectId, subject_type
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
                                ORDER BY id");
        $stmt2->execute([':id' => $classid1, ':tid' => $session_teacherid, ':lang' => $lang_filter]);
    } else {
        // Admin: todas las materias del grupo
        $stmt2 = $dbh->prepare("SELECT SubjectName, id as SubjectId, subject_type
                                FROM tblsubjects
                                WHERE id IN (
                                    SELECT SubjectId FROM tblsubjectcombination
                                    WHERE ClassId = :id AND status = 1
                                )
                                AND Language = :lang
                                ORDER BY id");
        $stmt2->execute([':id' => $classid1, ':lang' => $lang_filter]);
    }
    $subjects = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    if (count($subjects) > 0) {
        echo '<h5 class="form-section-title">Carga Académica (' . htmlentities($label) . '):</h5>';

        // Cada input lleva el SubjectId EN EL NAME (marks[ID] / letters[ID]) para que el
        // guardado no dependa del orden de la lista. Las materias 'behavior' se califican
        // con letra (E/VG/G/S/N); el resto con número 0-100.
        // Se AGRUPA por subject_type igual que en la boleta: normales, conducta (letra),
        // y adicionales (extras), en el orden en que aparecen en la boleta.
        $letter_options = ['', 'E', 'VG', 'G', 'S', 'N'];

        // Repartir en grupos.
        $g_normal = [];
        $g_behavior = [];
        $g_extra = [];
        foreach ($subjects as $subject) {
            $t = $subject['subject_type'] ?? 'normal';
            if ($t === 'behavior')      $g_behavior[] = $subject;
            elseif ($t === 'extra')     $g_extra[] = $subject;
            else                        $g_normal[] = $subject;
        }

        // Renderiza una materia (número o letra según tipo).
        $renderSubject = function ($subject) use ($letter_options, $lang) {
            $sid = (int) $subject['SubjectId'];
            echo '<div class="row" style="margin-bottom:15px;">
                    <div class="col-md-8">
                        <p style="margin-top:7px; font-weight:600;">' . htmlentities($subject['SubjectName']) . '</p>
                    </div>
                    <div class="col-md-4">';
            if (($subject['subject_type'] ?? 'normal') === 'behavior') {
                echo '<select name="letters[' . $sid . ']" class="form-control">';
                foreach ($letter_options as $opt) {
                    $lbl = $opt === '' ? (($lang == 'en') ? '-- Select --' : '-- Seleccionar --') : $opt;
                    echo '<option value="' . htmlentities($opt) . '">' . htmlentities($lbl) . '</option>';
                }
                echo '</select>';
            } else {
                echo '<input type="number" name="marks[' . $sid . ']" class="form-control" placeholder="0-100" min="0" max="100" step="1">';
            }
            echo '</div>
                  </div>';
        };

        // Encabezado de grupo (estilo discreto, separa secciones).
        $groupHeader = function ($text) {
            echo '<p style="margin:18px 0 10px; padding-bottom:5px; border-bottom:1px solid #e2e8f0;'
               . ' font-weight:800; font-size:12px; letter-spacing:1px; text-transform:uppercase; color:#64748b;">'
               . htmlentities($text) . '</p>';
        };

        // Títulos por idioma, alineados con la boleta.
        if ($lang == 'en') {
            // Inglés: Report Card (número) + Behavior Observations (letra).
            if ($g_normal) {
                $groupHeader('Report Card');
                foreach ($g_normal as $s) $renderSubject($s);
            }
            if ($g_behavior) {
                $groupHeader('Behavior Observations (E / VG / G / S / N)');
                foreach ($g_behavior as $s) $renderSubject($s);
            }
        } else {
            // Español: materias + adicionales (extras). Si hay extras se titulan ambos grupos.
            if ($g_normal) {
                if ($g_extra) $groupHeader('Asignaturas');
                foreach ($g_normal as $s) $renderSubject($s);
            }
            if ($g_extra) {
                $groupHeader('Asignaturas adicionales');
                foreach ($g_extra as $s) $renderSubject($s);
            }
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