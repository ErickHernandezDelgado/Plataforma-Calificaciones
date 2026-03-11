<?php
// Inicia la sesión
session_start();
// Desactiva los reportes de error (para producción, cambia a error_reporting(E_ALL) para desarrollo)
error_reporting(0);

// Conexión a la base de datos
include(__DIR__ . '/includes/config.php');

// Verifica si el usuario ha iniciado sesión como admin
if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php"); // Si no hay sesión activa, redirige al login
} else {

    // Si el formulario fue enviado (se presionó el botón "Actualizar")
    if (isset($_POST['update'])) {
        // Obtiene los valores del formulario
        $classname = $_POST['classname'];
        $classnamenumeric = $_POST['classnamenumeric'];
        $section = $_POST['section'];

        // Obtiene el ID de la clase desde la URL
        $cid = intval($_GET['classid']);

        // Prepara consulta SQL para actualizar los datos de la clase
        $sql = "UPDATE tblclasses 
                SET ClassName = :classname,
                    ClassNameNumeric = :classnamenumeric,
                    Section = :section 
                WHERE id = :cid";

        // Ejecuta la consulta preparada
        $query = $dbh->prepare($sql);
        $query->bindParam(':classname', $classname, PDO::PARAM_STR);
        $query->bindParam(':classnamenumeric', $classnamenumeric, PDO::PARAM_STR);
        $query->bindParam(':section', $section, PDO::PARAM_STR);
        $query->bindParam(':cid', $cid, PDO::PARAM_STR);
        $query->execute();

        // Mensaje de éxito
        $msg = "Información de año fue actualizada correctamente";
    }
?>

<!-- ========== BARRA SUPERIOR ========== -->
<?php include('includes/topbar.php'); ?>

<!-- Contenedor general de la página -->
<div class="content-wrapper">
    <div class="content-container">

        <!-- Menú lateral -->
        <?php include('includes/leftbar.php'); ?>

        <!-- Contenido principal -->
        <div class="main-page">
            <div class="container-fluid">

                <!-- Título -->
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Actualizar Año</h2>
                    </div>
                </div>

                <!-- Navegación / breadcrumb -->
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li><a href="#">Años</a></li>
                            <li class="active">Actualizar Año</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Formulario de edición -->
            <section class="section">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Actualizar Información de Año</h5>
                                    </div>
                                </div>

                                <!-- Mensajes de éxito o error -->
                                <?php if ($msg) { ?>
                                    <div class="alert alert-success left-icon-alert" role="alert">
                                        <strong>Proceso Exitoso! </strong><?php echo htmlentities($msg); ?>
                                    </div>
                                <?php } else if ($error) { ?>
                                    <div class="alert alert-danger left-icon-alert" role="alert">
                                        <strong>Hubo inconvenientes! </strong> <?php echo htmlentities($error); ?>
                                    </div>
                                <?php } ?>

                                <!-- Inicio del formulario -->
                                <form method="post">
                                    <?php
                                    // Obtiene los datos actuales de la clase para mostrarlos en el formulario
                                    $cid = intval($_GET['classid']);
                                    $sql = "SELECT * FROM tblclasses WHERE id = :cid";
                                    $query = $dbh->prepare($sql);
                                    $query->bindParam(':cid', $cid, PDO::PARAM_STR);
                                    $query->execute();
                                    $results = $query->fetchAll(PDO::FETCH_OBJ);

                                    // Si se encontraron datos, los muestra
                                    if ($query->rowCount() > 0) {
                                        foreach ($results as $result) {
                                    ?>
                                            <div class="form-group has-success">
                                                <label class="control-label">Nombre de Año</label>
                                                <input type="text" name="classname" value="<?php echo htmlentities($result->ClassName); ?>" required class="form-control">
                                                <span class="help-block">Ejemplo: Primero, Segundo, Tercero</span>
                                            </div>

                                            <div class="form-group has-success">
                                                <label class="control-label">Nombre de Año en Número</label>
                                                <input type="number" name="classnamenumeric" value="<?php echo htmlentities($result->ClassNameNumeric); ?>" required class="form-control">
                                                <span class="help-block">Ejemplo: 1, 2, 3</span>
                                            </div>

                                            <div class="form-group has-success">
                                                <label class="control-label">Sección</label>
                                                <input type="text" name="section" value="<?php echo htmlentities($result->Section); ?>" required class="form-control">
                                                <span class="help-block">Ejemplo: A, B, C</span>
                                            </div>
                                    <?php
                                        }
                                    }
                                    ?>

                                    <!-- Botón para guardar cambios -->
                                    <div class="form-group has-success">
                                        <button type="submit" name="update" class="btn btn-success btn-labeled">
                                            Actualizar <span class="btn-label btn-label-right"><i class="fa fa-check"></i></span>
                                        </button>
                                    </div>
                                </form>
                                <!-- Fin del formulario -->
                            </div>
                        </div>
                    </div> <!-- /.row -->
                </div>
            </section>
            <!-- Fin de sección -->
        </div> <!-- /.main-page -->
    </div> <!-- /.content-container -->
</div> <!-- /.content-wrapper -->

<!-- Pie de página -->
<?php include('includes/footer.php'); ?>

<?php } // Fin del else principal ?>

