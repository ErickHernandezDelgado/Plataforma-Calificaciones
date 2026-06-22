<?php
// Inicia la sesión
session_start();

// Desactiva los mensajes de error (no recomendado en producción)
error_reporting(0);

// Incluye archivo de configuración (conexión a la base de datos, etc.)
include(__DIR__ . '/includes/config.php');

// Verifica si el administrador ha iniciado sesión
if (strlen($_SESSION['alogin']) == "") {
    // Si no ha iniciado sesión, redirige al login
    header("Location: index.php");
} else {

    if (isset($_POST['Update'])) {
        $sid = intval($_GET['subjectid']);
        $subjectname = trim($_POST['subjectname']);

        $sql = "UPDATE tblsubjects SET SubjectName = :subjectname WHERE id = :sid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':subjectname', $subjectname, PDO::PARAM_STR);
        $query->bindParam(':sid', $sid, PDO::PARAM_STR);
        $query->execute();

        $msg = " Información de Materia Actualizada Correctamente";
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
                                            <strong>Bien hecho!</strong><?php echo htmlentities($msg); ?>
                                        </div>
                                    <?php } else if ($error) { ?>
                                        <div class="alert alert-danger left-icon-alert" role="alert">
                                            <strong>Hubo inconvenientes!</strong> <?php echo htmlentities($error); ?>
                                        </div>
                                    <?php } ?>

                                    <!-- Formulario de actualización -->
                                    <form class="form-horizontal" method="post">

                                        <?php
                                        // Obtiene el ID de la materia desde GET
                                        $sid = intval($_GET['subjectid']);

                                        // Consulta SQL para obtener los datos actuales de la materia
                                        $sql = "SELECT * from tblsubjects where id=:sid";
                                        $query = $dbh->prepare($sql);
                                        $query->bindParam(':sid', $sid, PDO::PARAM_STR);
                                        $query->execute();

                                        // Obtiene todos los resultados
                                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                                        $cnt = 1;

                                        // Si hay resultados, muestra los campos del formulario con los valores actuales
                                        if ($query->rowCount() > 0) {
                                            foreach ($results as $result) {
                                        ?>
                                                <!-- Campo: Nombre de la materia -->
                                                <div class="form-group">
                                                    <label class="col-sm-2 control-label">Nombre Materia</label>
                                                    <div class="col-sm-10">
                                                        <input type="text" name="subjectname" value="<?php echo htmlentities($result->SubjectName); ?>" class="form-control" placeholder="Nombre Materia" required>
                                                    </div>
                                                </div>

                                                <!-- Código: solo lectura, no se puede editar -->
                                                <div class="form-group">
                                                    <label class="col-sm-2 control-label">Código Materia</label>
                                                    <div class="col-sm-10">
                                                        <input type="text" class="form-control" value="<?php echo htmlentities($result->SubjectCode); ?>" disabled>
                                                        <span class="help-block">El código se asigna automáticamente al crear la materia.</span>
                                                    </div>
                                                </div>
                                        <?php }
                                        } ?>

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

