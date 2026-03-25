<?php
/**
 * add-result.php
 * Sistema de Gestión de Calificaciones IPT
 * Registro de resultados por materia y periodo
 */

session_start();
// Cambiar a E_ALL para depuración en desarrollo, 0 en producción
error_reporting(E_ALL); 
ini_set('display_errors', 1);

include(__DIR__ . '/includes/config.php');

// Verificación de Sesión
if (!isset($_SESSION['alogin']) || strlen($_SESSION['alogin']) == 0) {
    header("Location: index.php");
    exit;
}

$msg = "";
$error = "";

// PROCESAMIENTO DEL FORMULARIO
if (isset($_POST['submit'])) {
    $class = $_POST['class'];
    $studentid = $_POST['studentid'];
    $mark = $_POST['marks']; // Array de calificaciones
    $trimestre = $_POST['trimestre'] ?? null;

    if (empty($trimestre)) {
        $error = "Por favor selecciona un período (Trimestre/Bimestre).";
    } else {
        // 1. Obtener materias asignadas a la clase
        $stmt = $dbh->prepare("SELECT tblsubjects.id 
                               FROM tblsubjectcombination 
                               JOIN tblsubjects ON tblsubjects.id = tblsubjectcombination.SubjectId 
                               WHERE tblsubjectcombination.ClassId = :cid AND tblsubjectcombination.status = 1
                               ORDER BY tblsubjects.SubjectName");
        $stmt->execute([':cid' => $class]);
        $subjectIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($subjectIds)) {
            $error = "No hay materias asignadas a este grupo.";
        } else {
            $insert_success = true;
            $dbh->beginTransaction();

            try {
                for ($i = 0; $i < count($mark); $i++) {
                    if ($mark[$i] !== "") { // Solo si se ingresó un valor
                        $val = $mark[$i];
                        $sid = $subjectIds[$i];

                        // Insertar resultado
                        $sql = "INSERT INTO tblresult(StudentId, ClassId, SubjectId, marks, Trimestre) 
                                VALUES(:studentid, :class, :sid, :marks, :trimestre)";
                        $query = $dbh->prepare($sql);
                        $query->execute([
                            ':studentid' => $studentid,
                            ':class' => $class,
                            ':sid' => $sid,
                            ':marks' => $val,
                            ':trimestre' => $trimestre
                        ]);
                    }
                }
                $dbh->commit();
                $msg = "Resultados guardados correctamente para el $trimestre.";
            } catch (Exception $e) {
                $dbh->rollBack();
                $error = "Error al guardar: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IPT | Agregar Resultado</title>
    <link rel="stylesheet" href="css/bootstrap.min.css" media="screen">
    <link rel="stylesheet" href="css/font-awesome.min.css" media="screen">
    <link rel="stylesheet" href="css/main.css" media="screen">
    
    <style>
        /* Estilos de Diseño Moderno */
        .main-card { background: #fff; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: none; margin-bottom: 30px; }
        .card-header-custom { background: #f8f9fa; border-bottom: 1px solid #edf2f9; padding: 25px; border-radius: 12px 12px 0 0; }
        .card-title { color: #334155; font-weight: 700; margin: 0; display: flex; align-items: center; }
        .card-title i { margin-right: 12px; color: #3b82f6; }
        
        .form-section { padding: 25px; border-bottom: 1px solid #f1f5f9; }
        .form-section-title { font-size: 12px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 20px; display: block; }
        
        .form-control { border-radius: 8px; border: 1px solid #e2e8f0; padding: 10px 15px; height: auto; transition: all 0.2s; }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        
        label { font-weight: 600; color: #475569; margin-bottom: 8px; }
        .btn-save { background: #10b981; color: white; border: none; padding: 14px; border-radius: 8px; font-weight: 700; transition: all 0.2s; width: 100%; text-transform: uppercase; }
        .btn-save:hover { background: #059669; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); }
        
        .subject-container { background: #f8fafc; padding: 20px; border-radius: 10px; border: 1px dashed #cbd5e1; }
        .alert-modern { border-radius: 10px; border: none; padding: 15px 20px; margin-bottom: 20px; }
    </style>
</head>
<body class="top-navbar-fixed">
    <div class="main-wrapper">
        <?php include('includes/topbar.php'); ?>
        <div class="content-wrapper">
            <div class="content-container">
                <?php 
                if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'teacher') {
                    include('includes/leftbar-teacher.php');
                } else {
                    include('includes/leftbar.php');
                }
                ?>

                <div class="main-page">
                    <div class="container-fluid">
                        <div class="row page-title-div">
                            <div class="col-md-12">
                                <h2 class="title">Carga de Calificaciones</h2>
                                <p class="text-muted">Ingresa los resultados académicos por materia y período</p>
                            </div>
                        </div>
                        
                        <div class="row breadcrumb-div">
                            <div class="col-md-12">
                                <ul class="breadcrumb">
                                    <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                                    <li class="active">Agregar Resultado</li>
                                </ul>
                            </div>
                        </div>

                        <section class="section">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-md-10 col-md-offset-1">
                                        
                                        <?php if ($msg) { ?>
                                            <div class="alert alert-success alert-modern"><i class="fa fa-check-circle"></i> <?php echo htmlentities($msg); ?></div>
                                        <?php } else if ($error) { ?>
                                            <div class="alert alert-danger alert-modern"><i class="fa fa-times-circle"></i> <?php echo htmlentities($error); ?></div>
                                        <?php } ?>

                                        <div class="main-card">
                                            <div class="card-header-custom">
                                                <h4 class="card-title"><i class="fa fa-edit"></i> Panel de Evaluación</h4>
                                            </div>

                                            <form method="post">
                                                <div class="form-section">
                                                    <span class="form-section-title">1. Contexto Académico</span>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Grado y Grupo</label>
                                                                <select name="class" class="form-control clid" id="classid" onChange="getStudent(this.value);" required>
                                                                    <option value="">Seleccionar...</option>
                                                                    <?php
                                                                    $sql = "SELECT id, ClassName, Section, educationLevel FROM tblclasses ORDER BY AcademicYear DESC, ClassName ASC";
                                                                    $query = $dbh->prepare($sql);
                                                                    $query->execute();
                                                                    foreach ($query->fetchAll(PDO::FETCH_OBJ) as $result) { ?>
                                                                        <option value="<?php echo $result->id; ?>" data-level="<?php echo $result->educationLevel; ?>">
                                                                            <?php echo htmlentities($result->ClassName . " (" . $result->Section . ") - " . ucfirst($result->educationLevel)); ?>
                                                                        </option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Período Evaluativo</label>
                                                                <select name="trimestre" id="trimestre" class="form-control" required>
                                                                    <option value="">Selecciona un grupo primero</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-section">
                                                    <span class="form-section-title">2. Estudiante</span>
                                                    <div class="form-group">
                                                        <label>Nombre del Alumno</label>
                                                        <select name="studentid" class="form-control stid" id="studentid" required onChange="getresult(this.value);">
                                                            <option value="">Esperando grupo...</option>
                                                        </select>
                                                    </div>
                                                    <div id="reslt"></div>
                                                </div>

                                                <div class="form-section" style="border-bottom: none;">
                                                    <span class="form-section-title">3. Registro de Materias</span>
                                                    <div id="subject" class="subject-container">
                                                        <p class="text-center text-muted m-0">Selecciona un estudiante para cargar su carga académica.</p>
                                                    </div>
                                                </div>

                                                <div class="p-25">
                                                    <div class="row">
                                                        <div class="col-md-4 col-md-offset-4">
                                                            <button type="submit" name="submit" class="btn-save">
                                                                <i class="fa fa-save"></i> Guardar Calificaciones
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jquery/jquery-2.2.4.min.js"></script>
    <script src="js/bootstrap/bootstrap.min.js"></script>
    <script>
        function getStudent(val) {
            // Manejo dinámico de periodos
            var level = $('#classid option:checked').data('level');
            var $t = $('#trimestre').empty().append('<option value="">Seleccionar Período</option>');
            var opts = (level === 'infantil') ? ['Bimestre 1', 'Bimestre 2', 'Bimestre 3', 'Bimestre 4', 'Bimestre 5'] : ['Trimestre 1', 'Trimestre 2', 'Trimestre 3'];
            opts.forEach(o => $t.append(`<option value="${o}">${o}</option>`));

            // Carga de estudiantes
            $.post("get_student.php", {classid: val}, d => $("#studentid").html(d));
            // Carga de materias
            $.post("get_student.php", {classid1: val}, d => $("#subject").html(d));
        }

        function getresult(val) {
            var cid = $(".clid").val();
            $.post("get_student.php", {studclass: cid + '$' + val}, d => $("#reslt").html(d));
        }
    </script>
</body>
</html>
<?php include('includes/footer.php'); ?>