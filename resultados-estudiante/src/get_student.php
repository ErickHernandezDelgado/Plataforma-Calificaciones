<?php
/**
 * get_student.php
 * Endpoint AJAX para cargar estudiantes y materias por grupo
 */
include(__DIR__ . '/includes/check-login.php');

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
        echo '<option value="">No hay alumnos en este grupo</option>';
    }
}

// 2. CARGA DE MATERIAS (Crea los inputs de calificación)
if (!empty($_POST["classid1"])) {
    $classid1 = intval($_POST['classid1']);

    // CORRECCIÓN: Obtener educationLevel de tblclasses (no tblperiod_types que no existe)
    $sql_period = "SELECT educationLevel FROM tblclasses WHERE id = :classid";
    $stmt_period = $dbh->prepare($sql_period);
    $stmt_period->execute([':classid' => $classid1]);
    $period_info = $stmt_period->fetch(PDO::FETCH_ASSOC);

    // Determinar tipo de período basado en educationLevel
    $label = "Materia";
    if ($period_info) {
        if ($period_info['educationLevel'] === 'infantil') {
            $label = "Bimestrales (2 períodos)";
        } elseif ($period_info['educationLevel'] === 'primaria' || $period_info['educationLevel'] === 'secundaria') {
            $label = "Trimestrales (3 períodos)";
        }
    }

    // Obtener Materias del grupo
    $stmt2 = $dbh->prepare("SELECT SubjectName, id as SubjectId
                            FROM tblsubjects 
                            WHERE id IN (
                                SELECT SubjectId FROM tblsubjectcombination 
                                WHERE ClassId = :id AND status = 1
                            )
                            ORDER BY SubjectName");
    $stmt2->execute([':id' => $classid1]);
    $subjects = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    if (count($subjects) > 0) {
        // Mostramos el nombre del tipo de periodo (Bimestre o Trimestre) detectado
        echo '<h5 class="form-section-title">Carga Académica (' . htmlentities($label) . '):</h5>';
        
        foreach ($subjects as $subject) {
            echo '<div class="row" style="margin-bottom:15px;">
                    <div class="col-md-8">
                        <p style="margin-top:7px; font-weight:600;">' . htmlentities($subject['SubjectName']) . '</p>
                    </div>
                    <div class="col-md-4">
                        <input type="number" name="marks[]" class="form-control" placeholder="0-10" min="0" max="10" step="0.1" required>
                    </div>
                  </div>';
        }
    } else {
        echo '<p class="text-danger">No hay materias asignadas a este grupo.</p>';
    }
}

// 3. VALIDACIÓN DE DUPLICADOS
if (!empty($_POST["studclass"])) {
    $data = explode("$", $_POST['studclass']);
    if(count($data) >= 4) {
        $cid = intval($data[0]);
        $sid = intval($data[1]);
        $ptid = intval($data[2]);      // period_type_id
        $pnum = intval($data[3]);      // period_number

        // CORRECCIÓN: Buscar en tblresult usando period_type_id y period_number (estructura real)
        $sql = "SELECT id FROM tblresult 
                WHERE StudentId = :sid AND ClassId = :cid AND period_type_id = :ptid AND period_number = :pnum
                LIMIT 1";
        $query = $dbh->prepare($sql);
        $query->execute([':sid' => $sid, ':cid' => $cid, ':ptid' => $ptid, ':pnum' => $pnum]);

        if ($query->rowCount() > 0) {
            echo '<div class="alert alert-warning" style="margin-top:10px;">
                    <i class="fa fa-exclamation-triangle"></i> 
                    El alumno ya cuenta con resultados registrados para este período.
                  </div>';
        }
    }
}
?>