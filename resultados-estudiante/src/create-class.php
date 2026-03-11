<?php
// Inicia la sesión y desactiva los errores visibles
session_start();
error_reporting(0);
include(__DIR__ . '/includes/config.php');

// Verifica si el usuario ha iniciado sesión, si no redirige al login
if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php");
} else {
    // Si el formulario fue enviado
    if (isset($_POST['submit'])) {
        $classname = $_POST['classname']; // Nombre del año (ej. Primero)
        $classnamenumeric = $_POST['classnamenumeric']; // Número del año (ej. 1)
        $section = $_POST['section']; // Sección (ej. A)

        // Inserta los datos en la tabla tblclasses
        $sql = "INSERT INTO  tblclasses(ClassName,ClassNameNumeric,Section) VALUES(:classname,:classnamenumeric,:section)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':classname', $classname, PDO::PARAM_STR);
        $query->bindParam(':classnamenumeric', $classnamenumeric, PDO::PARAM_STR);
        $query->bindParam(':section', $section, PDO::PARAM_STR);
        $query->execute();

        // Verifica si se insertó correctamente
        $lastInsertId = $dbh->lastInsertId();
        if ($lastInsertId) {
            $msg = "Class Created successfully";
        } else {
            $error = "Something went wrong. Please try again";
        }
    }
?>

<!-- Barra superior -->
<?php include('includes/topbar.php'); ?>

<div class="content-wrapper">
    <div class="content-container">

        <!-- Barra lateral -->
        <?php include('includes/leftbar.php'); ?>

        <div class="main-page">
            <div class="container-fluid">
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Crear Año</h2>
                    </div>
                </div>

                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li><a href="#">Año</a></li>
                            <li class="active">Crear Año</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sección de formulario para crear clase/año -->
            <section class="section">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Crear Año</h5>
                                    </div>
                                </div>

                                <!-- Mensaje de éxito o error -->
                                <?php if ($msg) { ?>
                                    <div class="alert alert-success left-icon-alert" role="alert">
                                        <strong>Bien Hecho</strong><?php echo htmlentities($msg); ?>
                                    </div>
                                <?php } else if ($error) { ?>
                                    <div class="alert alert-danger left-icon-alert" role="alert">
                                        <strong>Hubo un problema</strong> <?php echo htmlentities($error); ?>
                                    </div>
                                <?php } ?>

                                <div class="panel-body">
                                    <form method="post">
                                        <!-- Campo: Nombre del año (texto) -->
                                        <div class="form-group has-success">
                                            <label class="control-label">Nombre Año</label>
                                            <input type="text" name="classname" class="form-control" required>
                                            <span class="help-block">Puedes poner Primero, Segundo o algo así</span>
                                        </div>

                                        <!-- Campo: Año en número (número) -->
                                        <div class="form-group has-success">
                                            <label class="control-label">Año en Número</label>
                                            <input type="number" name="classnamenumeric" class="form-control" required>
                                            <span class="help-block">Puedes poner 1, 2, 3...</span>
                                        </div>

                                        <!-- Campo: Sección (texto) -->
                                        <div class="form-group has-success">
                                            <label class="control-label">Sección</label>
                                            <input type="text" name="section" class="form-control" required>
                                            <span class="help-block">Puedes poner A, B, C...</span>
                                        </div>

                                        <!-- Botón para enviar -->
                                        <div class="form-group has-success">
                                            <button type="submit" name="submit" class="btn btn-success">Submit</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div> <!-- /.col-md-8 -->
                    </div> <!-- /.row -->
                </div> <!-- /.container-fluid -->
            </section>
        </div> <!-- /.main-page -->
    </div> <!-- /.content-container -->
</div> <!-- /.content-wrapper -->

<!-- Pie de página -->
<?php include('includes/footer.php'); ?>
<?php } ?>

