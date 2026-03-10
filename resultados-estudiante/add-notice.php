<?php
// Inicia la sesión
session_start();

// Oculta reportes de errores (no recomendado en producción sin control)
error_reporting(0);

// Incluye el archivo de configuración con la conexión a la base de datos
include('includes/config.php');

// Verifica si el usuario ha iniciado sesión (variable de sesión 'alogin')
if (strlen($_SESSION['alogin']) == "") {
    // Si no ha iniciado sesión, redirige a la página de login
    header("Location: index.php");
} else {
    // Si el formulario ha sido enviado
    if (isset($_POST['submit'])) {
        // Obtiene los datos del formulario
        $ntitle = $_POST['noticetitle']; // Título del comunicado
        $ndetails = $_POST['noticedetails']; // Detalle o contenido del comunicado

        // Prepara la consulta SQL para insertar un nuevo comunicado
        $sql = "INSERT INTO  tblnotice(noticeTitle,noticeDetails) VALUES(:ntitle,:ndetails)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':ntitle', $ntitle, PDO::PARAM_STR);
        $query->bindParam(':ndetails', $ndetails, PDO::PARAM_STR);
        $query->execute();

        // Obtiene el ID del último registro insertado
        $lastInsertId = $dbh->lastInsertId();

        // Verifica si el insert fue exitoso
        if ($lastInsertId) {
            // Muestra alerta de éxito y redirige a la página de gestión
            echo '<script>alert("Comunicado agregado, correctamente")</script>';
            echo "<script>window.location.href ='manage-notices.php'</script>";
        } else {
            // Muestra alerta de error
            echo '<script>alert("Algo salió mal. Inténtalo de nuevo.")</script>';
        }
    }
?>

<!-- ========== BARRA SUPERIOR ========== -->
<?php include('includes/topbar.php'); ?>

<!-- Contenedor principal del contenido -->
<div class="content-wrapper">
    <div class="content-container">

        <!-- Barra lateral izquierda -->
        <?php include('includes/leftbar.php'); ?>

        <!-- Página principal -->
        <div class="main-page">
            <div class="container-fluid">
                <!-- Título de la página -->
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Agregar Comunicado</h2>
                    </div>
                </div>

                <!-- Ruta de navegación (breadcrumb) -->
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li><a href="#">Comunicado</a></li>
                            <li class="active">Agregar Comunicado</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sección principal -->
            <section class="section">
                <div class="container-fluid">

                    <!-- Formulario centrado -->
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Agregar Comunicado</h5>
                                    </div>
                                </div>

                                <div class="panel-body">
                                    <!-- Formulario para ingresar el comunicado -->
                                    <form method="post">
                                        <!-- Campo para el título -->
                                        <div class="form-group has-success">
                                            <label for="success" class="control-label">Título de Comunicado</label>
                                            <div class="">
                                                <input type="text" name="noticetitle" class="form-control" required="required" id="noticetitle">
                                            </div>
                                        </div>

                                        <!-- Campo para el contenido del comunicado -->
                                        <div class="form-group has-success">
                                            <label for="success" class="control-label">Información de Comunicado</label>
                                            <div class="">
                                                <textarea class="form-control" name="noticedetails" required rows="5"></textarea>
                                            </div>
                                        </div>

                                        <!-- Botón para enviar el formulario -->
                                        <div class="form-group has-success">
                                            <div class="">
                                                <button type="submit" name="submit" class="btn btn-success">Enviar</button>
                                            </div>
                                        </div>
                                    </form>
                                    <!-- Fin del formulario -->
                                </div>
                            </div>
                        </div>
                        <!-- /.col-md-8 col-md-offset-2 -->
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

<!-- Pie de página -->
<?php include('includes/footer.php'); ?>

<?php } ?>
