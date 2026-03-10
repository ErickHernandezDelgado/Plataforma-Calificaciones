<?php
// Inicia la sesión y desactiva la visualización de errores
session_start();
error_reporting(0);
include('includes/config.php'); // Conexión a la base de datos

// Verifica si el usuario está logueado; si no, lo redirige al login
if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php");
} else {
    // Si se envió el formulario
    if (isset($_POST['submit'])) {
        $subjectname = $_POST['subjectname']; // Nombre de la materia
        $subjectcode = $_POST['subjectcode']; // Código de la materia

        // Prepara la consulta para insertar una nueva materia
        $sql = "INSERT INTO tblsubjects(SubjectName,SubjectCode) VALUES(:subjectname,:subjectcode)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':subjectname', $subjectname, PDO::PARAM_STR);
        $query->bindParam(':subjectcode', $subjectcode, PDO::PARAM_STR);
        $query->execute();

        // Verifica si se insertó correctamente
        $lastInsertId = $dbh->lastInsertId();
        if ($lastInsertId) {
            $msg = "Materia creada correctamente";
        } else {
            $error = "Hubo un fallo, reintenta";
        }
    }
?>

<!-- ========== BARRA SUPERIOR ========== -->
<?php include('includes/topbar.php'); ?>

<!-- ========== CONTENEDOR PRINCIPAL ========== -->
<div class="content-wrapper">
    <div class="content-container">

        <!-- ========== MENÚ LATERAL IZQUIERDO ========== -->
        <?php include('includes/leftbar.php'); ?>

        <!-- ========== CONTENIDO PRINCIPAL ========== -->
        <div class="main-page">

            <!-- ========== ENCABEZADO ========== -->
            <div class="container-fluid">
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Crear Materia</h2>
                    </div>
                </div>

                <!-- ========== BREADCRUMB ========== -->
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li>Materia</li>
                            <li class="active">Crear Materia</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- ========== SECCIÓN PRINCIPAL ========== -->
            <section class="section">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col-md-8 col-md-offset-2">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Crear Materia</h5>
                                    </div>
                                </div>
                                <div class="panel-body">
                                    <!-- Mensaje de éxito o error -->
                                    <?php if ($msg) { ?>
                                        <div class="alert alert-success left-icon-alert" role="alert">
                                            <strong>Bien hecho</strong> <?php echo htmlentities($msg); ?>
                                        </div>
                                    <?php } else if ($error) { ?>
                                        <div class="alert alert-danger left-icon-alert" role="alert">
                                            <strong>Hubo inconvenientes!</strong> <?php echo htmlentities($error); ?>
                                        </div>
                                    <?php } ?>

                                    <!-- Formulario para crear una nueva materia -->
                                    <form method="post">
                                        <div class="form-group">
                                            <label for="default" class="control-label">Nombre Materia</label>
                                            <input type="text" name="subjectname" class="form-control" id="default" placeholder="Nombre Materia" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="default" class="control-label">Código Materia</label>
                                            <input type="text" name="subjectcode" class="form-control" id="default" placeholder="Código Materia" required>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" name="submit" class="btn btn-success">Enviar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- /.col-md-8 -->
                    </div>
                    <!-- /.row -->
                </div>
                <!-- /.container-fluid -->
            </section>
            <!-- /.section -->
        </div>
        <!-- /.main-page -->
    </div>
    <!-- /.content-container -->
</div>
<!-- /.content-wrapper -->

<!-- ========== PIE DE PÁGINA ========== -->
<?php include('includes/footer.php'); ?>

<?php } // Fin del else que verifica sesión ?>
