<?php
// Inicia la sesión
session_start();

// Desactiva los mensajes de error (no recomendado en producción)
error_reporting(0);

// Incluye archivo de configuración (conexión a la base de datos, etc.)
include(__DIR__ . '/includes/config.php');

// Verifica que el usuario haya iniciado sesión y que su rol sea 'admin'
if (!isset($_SESSION['alogin']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
} else {
    // Inicializa los mensajes para evitar variables indefinidas en la vista
    $msg = '';
    $error = '';

    // Valida que se haya recibido un ID de materia y que la materia exista
    $sid = intval($_GET['subjectid'] ?? 0);
    if (!$sid) {
        header("Location: manage-subjects.php");
        exit;
    }
    $chk = $dbh->prepare("SELECT * FROM tblsubjects WHERE id = :sid");
    $chk->bindParam(':sid', $sid, PDO::PARAM_INT);
    $chk->execute();
    $subject = $chk->fetch(PDO::FETCH_OBJ);
    if (!$subject) {
        header("Location: manage-subjects.php");
        exit;
    }

    if (isset($_POST['Update'])) {
        $subjectname = trim($_POST['subjectname']);

        if ($subjectname === '') {
            $error = "El nombre de la materia es obligatorio.";
        } else {
            $sql = "UPDATE tblsubjects SET SubjectName = :subjectname WHERE id = :sid";
            $query = $dbh->prepare($sql);
            $query->bindParam(':subjectname', $subjectname, PDO::PARAM_STR);
            $query->bindParam(':sid', $sid, PDO::PARAM_INT);
            $query->execute();

            $msg = "Información de materia actualizada correctamente.";
            // Recargar datos para reflejar el cambio en el formulario
            $chk->execute();
            $subject = $chk->fetch(PDO::FETCH_OBJ);
        }
    }
?>

    <!-- Incluye barra superior -->
    <?php include('includes/topbar.php'); ?>

    <!-- Contenedor principal de contenido -->
    <div class="content-wrapper">
        <div class="content-container">

            <!-- Incluye barra lateral izquierda -->
            <?php include('includes/leftbar.php'); ?>
            <!-- /.left-sidebar -->

            <div class="main-page">

                <!-- Contenedor de título -->
                <div class="container-fluid">
                    <div class="row page-title-div">
                        <div class="col-md-6">
                            <h2 class="title">Actualizar Materia</h2>
                        </div>
                    </div>

                    <!-- Breadcrumb de navegación -->
                    <div class="row breadcrumb-div">
                        <div class="col-md-6">
                            <ul class="breadcrumb">
                                <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                                <li> Materia</li>
                                <li class="active">Actualizar Materia</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Contenido principal -->
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Actualizar Materia</h5>
                                    </div>
                                </div>

                                <div class="panel-body">
                                    <!-- Mensajes de éxito o error -->
                                    <?php if ($msg) { ?>
                                        <div class="alert alert-success left-icon-alert" role="alert">
                                            <strong>Bien hecho!</strong> <?php echo htmlentities($msg); ?>
                                        </div>
                                    <?php } else if ($error) { ?>
                                        <div class="alert alert-danger left-icon-alert" role="alert">
                                            <strong>Hubo inconvenientes!</strong> <?php echo htmlentities($error); ?>
                                        </div>
                                    <?php } ?>

                                    <!-- Formulario de actualización -->
                                    <form class="form-horizontal" method="post">

                                        <!-- Campo: Nombre de la materia -->
                                                <div class="form-group">
                                                    <label class="col-sm-2 control-label">Nombre Materia</label>
                                                    <div class="col-sm-10">
                                                        <input type="text" name="subjectname" value="<?php echo htmlentities($subject->SubjectName); ?>" class="form-control" placeholder="Nombre Materia" required>
                                                    </div>
                                                </div>

                                                <!-- Código: solo lectura, no se puede editar -->
                                                <div class="form-group">
                                                    <label class="col-sm-2 control-label">Código Materia</label>
                                                    <div class="col-sm-10">
                                                        <input type="text" class="form-control" value="<?php echo htmlentities($subject->SubjectCode); ?>" disabled>
                                                        <span class="help-block">El código se asigna automáticamente al crear la materia.</span>
                                                    </div>
                                                </div>

                                        <!-- Botón para enviar el formulario -->
                                        <div class="form-group">
                                            <div class="col-sm-offset-2 col-sm-10">
                                                <button type="submit" name="Update" class="btn btn-primary">Actualizar</button>
                                            </div>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>
                        <!-- /.col-md-12 -->
                    </div>
                </div>
            </div>
            <!-- /.content-container -->
        </div>
        <!-- /.content-wrapper -->

        <!-- Incluye pie de página -->
        <?php include('includes/footer.php'); ?>

<?PHP } ?>

