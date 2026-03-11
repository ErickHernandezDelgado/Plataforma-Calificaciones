<?php
// Inicia la sesión
session_start();

// Desactiva reportes de error (puede evitar mostrar errores en producción)
error_reporting(0);

// Incluye la configuración de conexión a la base de datos
include(__DIR__ . '/includes/config.php');

// Verifica que haya sesión iniciada
if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php");
    exit;
}

// Se obtienen el ID y el rol del maestro (si aplica)
$teacherId = $_SESSION['teacherid'] ?? null;
$teacherRole = $_SESSION['role'] ?? null;
$teacherSubjects = [];
$teacherSubjectId = null;

// Si el usuario es maestro, se obtienen TODAS sus materias
if ($teacherRole === 'teacher' && $teacherId) {
    $sql = "SELECT DISTINCT ts.SubjectId, s.SubjectName 
            FROM tblteacher_subject ts
            JOIN tblsubjects s ON ts.SubjectId = s.id
            WHERE ts.TeacherId = :teacherid
            ORDER BY s.SubjectName";
    $query = $dbh->prepare($sql);
    $query->bindParam(':teacherid', $teacherId, PDO::PARAM_INT);
    $query->execute();
    $teacherSubjects = $query->fetchAll(PDO::FETCH_OBJ);
    
    // Si hay materias, usar la primera por defecto (o la seleccionada)
    if (count($teacherSubjects) > 0) {
        $teacherSubjectId = $_POST['subject_id'] ?? intval($teacherSubjects[0]->SubjectId);
    }
}

// Inicialización de variables para mensajes de estado
$msg = "";
$error = "";

// Si se envió el formulario
if (isset($_POST['submit'])) {
    $class = $_POST['class'] ?? null; // Clase seleccionada
    $marks = $_POST['marks'] ?? [];   // Calificaciones recibidas

    // Validaciones iniciales
    if (!$class) {
        $error = "Por favor selecciona un grupo.";
    } elseif ($teacherRole === 'teacher' && empty($teacherSubjects)) {
        $error = "No tienes materias asignadas. Contacta al administrador para que te asigne materias.";
    } elseif (!$teacherSubjectId) {
        $error = "Faltan datos requeridos.";
    } elseif (!is_array($marks) || empty($marks)) {
        $error = "No se enviaron calificaciones válidas.";
    } else {
        // Inicia transacción para garantizar que todos los cambios se hagan correctamente
        $dbh->beginTransaction();
        $allOk = true;

        // Recorre cada entrada de calificación (por estudiante)
        foreach ($marks as $studentId => $markValue) {
            $studentId = intval($studentId);
            $markValue = trim($markValue);

            // Si el valor está vacío, se omite
            if ($markValue === '') continue;

            // Validación de que sea un número válido entre 0 y 100
            if (!is_numeric($markValue) || $markValue < 0 || $markValue > 100) {
                $error = "Calificación inválida para estudiante $studentId.";
                $allOk = false;
                break;
            }

            // Verifica si ya existe una calificación registrada para este estudiante, clase y materia
            $sql = "SELECT id FROM tblresult WHERE StudentId = :studentid AND ClassId = :classid AND SubjectId = :subjectid";
            $query = $dbh->prepare($sql);
            $query->execute([
                ':studentid' => $studentId,
                ':classid' => $class,
                ':subjectid' => $teacherSubjectId
            ]);
            $existing = $query->fetch(PDO::FETCH_ASSOC);

            // Si existe, se actualiza
            if ($existing) {
                $sqlUpd = "UPDATE tblresult SET marks = :marks WHERE id = :id";
                $queryUpd = $dbh->prepare($sqlUpd);
                $queryUpd->bindParam(':marks', $markValue, PDO::PARAM_STR);
                $queryUpd->bindParam(':id', $existing['id'], PDO::PARAM_INT);
                if (!$queryUpd->execute()) {
                    $allOk = false;
                    $error = "Error al actualizar calificación.";
                    break;
                }
            } else {
                // Si no existe, se inserta nuevo registro
                $sqlIns = "INSERT INTO tblresult (StudentId, ClassId, SubjectId, marks) 
                           VALUES (:studentid, :classid, :subjectid, :marks)";
                $queryIns = $dbh->prepare($sqlIns);
                $queryIns->bindParam(':studentid', $studentId, PDO::PARAM_INT);
                $queryIns->bindParam(':classid', $class, PDO::PARAM_INT);
                $queryIns->bindParam(':subjectid', $teacherSubjectId, PDO::PARAM_INT);
                $queryIns->bindParam(':marks', $markValue, PDO::PARAM_STR);
                if (!$queryIns->execute()) {
                    $allOk = false;
                    $error = "Error al guardar calificación.";
                    break;
                }
            }
        }

        // Si todo se ejecutó correctamente, se confirma la transacción
        if ($allOk) {
            $dbh->commit();
            $msg = "Resultados guardados correctamente.";
        } else {
            // Si hubo error, se revierte
            $dbh->rollBack();
            if (!$error) {
                $error = "Error desconocido al guardar resultados.";
            }
        }
    }
}
?>

<!-- Carga jQuery desde CDN -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Función para cargar la tabla de estudiantes dinámicamente vía AJAX -->
<script>
    function getStudentList(classId) {
        if (classId == "") {
            $("#student-subjects-table").html("");
            return;
        }
        $.ajax({
            type: "POST",
            url: "get_student_subjects.php", // Archivo PHP que genera el HTML de la tabla
            data: { classid: classId },
            success: function(data) {
                $("#student-subjects-table").html(data);
            },
            error: function() {
                $("#student-subjects-table").html("<div class='alert alert-danger'>Error al cargar los datos.</div>");
            }
        });
    }
</script>

<!-- Incluye barra superior y navegación lateral -->
<?php include('includes/topbar.php'); ?>

<div class="content-wrapper">
    <div class="content-container">
        <?php
        // Barra lateral según el rol
        if ($_SESSION['role'] == 'teacher') {
            include('includes/leftbar-teacher.php');
        } else {
            include('includes/leftbar.php');
        }
        ?>

        <!-- Página principal -->
        <div class="main-page">
            <div class="container-fluid">
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Agregar Resultado</h2>
                    </div>
                </div>
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li class="active">Agregar Resultado</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sección del formulario -->
            <section class="section">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-10 col-md-offset-1">
                            <div class="panel">
                                <div class="panel-body">

                                    <!-- Mensajes de estado -->
                                    <?php if ($msg) { ?>
                                        <div class="alert alert-success" role="alert">
                                            <strong>¡Éxito!</strong> <?php echo htmlentities($msg); ?>
                                        </div>
                                    <?php } else if ($error) { ?>
                                        <div class="alert alert-danger" role="alert">
                                            <strong>¡Error!</strong> <?php echo htmlentities($error); ?>
                                        </div>
                                    <?php } ?>

                                    <!-- Formulario para ingresar resultados -->
                                    <form method="post" id="results-form">
                                        <!-- Selector de clase -->
                                        <div class="form-group">
                                            <label for="classid">Año y Grupo</label>
                                            <select name="class" class="form-control" id="classid" required onChange="getStudentList(this.value);">
                                                <option value="">Seleccionar Año y Grupo</option>
                                                <?php
                                                // Carga de las clases disponibles
                                                $sql = "SELECT * from tblclasses ORDER BY ClassName, Section";
                                                $query = $dbh->prepare($sql);
                                                $query->execute();
                                                $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                if ($query->rowCount() > 0) {
                                                    foreach ($results as $result) {
                                                        echo '<option value="' . $result->id . '">' . $result->ClassName . ' - ' . $result->Section . '</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <?php if ($teacherRole === 'teacher' && count($teacherSubjects) > 1): ?>
                                            <!-- Selector de materia (solo para maestros con múltiples materias) -->
                                            <div class="form-group">
                                                <label for="subject_id">Materia</label>
                                                <select name="subject_id" class="form-control" id="subject_id" required onChange="location.href='add-result2.php?subject_id=' + this.value;">
                                                    <?php foreach ($teacherSubjects as $subj): ?>
                                                        <option value="<?= $subj->SubjectId ?>" <?= $subj->SubjectId == $teacherSubjectId ? 'selected' : '' ?>>
                                                            <?= htmlentities($subj->SubjectName) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        <?php elseif ($teacherRole === 'teacher' && count($teacherSubjects) === 1): ?>
                                            <!-- Mostrar materia única si maestro tiene una sola -->
                                            <div class="form-group">
                                                <label>Materia</label>
                                                <input type="text" class="form-control" value="<?= htmlentities($teacherSubjects[0]->SubjectName) ?>" disabled>
                                                <input type="hidden" name="subject_id" value="<?= $teacherSubjects[0]->SubjectId ?>">
                                            </div>
                                        <?php endif; ?>

                                        <!-- Aquí aparecerá la tabla de estudiantes generada por AJAX -->
                                        <div id="student-subjects-table"></div>

                                        <!-- Botón para guardar -->
                                        <div class="form-group">
                                            <button type="submit" name="submit" class="btn btn-success">Guardar Resultados</button>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Fin sección -->
        </div>
        <!-- /.main-page -->
    </div>
</div>

<!-- Pie de página -->
<?php include('includes/footer.php'); ?>
<?php include('includes/p_footer.php'); ?>
</body>
</html>

