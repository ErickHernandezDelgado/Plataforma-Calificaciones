<?php
// Inicia sesión
session_start();

// Desactiva la visualización de errores
error_reporting(0);

// Conexión a la base de datos
include(__DIR__ . '/includes/config.php');

// Verifica si la sesión está vacía (usuario no logueado), redirige al login
if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php");
} else {
    // Si se envió el formulario
    if (isset($_POST['submit'])) {

        // Obtiene la contraseña actual y la nueva desde el formulario y las convierte a md5
        $password = md5($_POST['password']);
        $newpassword = md5($_POST['newpassword']);

        // Obtiene el nombre de usuario de la sesión
        $username = $_SESSION['alogin'];

        // Verifica si la contraseña ingresada coincide con la guardada en la base de datos
        $sql = "SELECT Password FROM admin WHERE UserName=:username and Password=:password";
        $query = $dbh->prepare($sql);
        $query->bindParam(':username', $username, PDO::PARAM_STR);
        $query->bindParam(':password', $password, PDO::PARAM_STR);
        $query->execute();

        $results = $query->fetchAll(PDO::FETCH_OBJ);

        // Si encontró un resultado, significa que la contraseña actual es correcta
        if ($query->rowCount() > 0) {
            // Actualiza la contraseña del usuario con la nueva
            $con = "update admin set Password=:newpassword where UserName=:username";
            $chngpwd1 = $dbh->prepare($con);
            $chngpwd1->bindParam(':username', $username, PDO::PARAM_STR);
            $chngpwd1->bindParam(':newpassword', $newpassword, PDO::PARAM_STR);
            $chngpwd1->execute();

            // Mensaje de éxito
            $msg = "Tu contraseña ha cambiado correctamente";
        } else {
            // Mensaje de error si la contraseña actual no coincide
            $error = "Tu contraseña actual no es la que ingresaste";
        }
    }
?>

<!-- Validación del lado del cliente con JavaScript -->
<script type="text/javascript">
    function valid() {
        // Compara si las contraseñas nuevas coinciden
        if (document.chngpwd.newpassword.value != document.chngpwd.confirmpassword.value) {
            alert("La nueva contraseña y su contraseña de confirmación no coinciden");
            document.chngpwd.confirmpassword.focus();
            return false;
        }
        return true;
    }
</script>

<!-- Incluye barra superior -->
<?php include('includes/topbar.php'); ?>

<div class="content-wrapper">
    <div class="content-container">

        <!-- Barra lateral -->
        <?php include('includes/leftbar.php'); ?>

        <div class="main-page">
            <div class="container-fluid">

                <!-- Título -->
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Cambiar Contraseña</h2>
                    </div>
                </div>

                <!-- Migas de pan -->
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Home</a></li>
                            <li class="active">Cambiar Contraseña</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sección principal -->
            <section class="section">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Cambiar Contraseña</h5>
                                    </div>
                                </div>

                                <!-- Mensajes de alerta -->
                                <?php if ($msg) { ?>
                                    <div class="alert alert-success left-icon-alert" role="alert">
                                        <strong>Bien hecho! </strong><?php echo htmlentities($msg); ?>
                                    </div>
                                <?php } else if ($error) { ?>
                                    <div class="alert alert-danger left-icon-alert" role="alert">
                                        <strong>Algo salió mal! </strong> <?php echo htmlentities($error); ?>
                                    </div>
                                <?php } ?>

                                <!-- Formulario para cambiar contraseña -->
                                <div class="panel-body">
                                    <!-- Al enviar el formulario se ejecuta la función valid() para verificar que las contraseñas coincidan -->
                                    <form name="chngpwd" method="post" onSubmit="return valid();">
                                        <div class="form-group has-success">
                                            <label for="success" class="control-label">Contraseña actual</label>
                                            <input type="password" name="password" class="form-control" required id="success">
                                        </div>
                                        <div class="form-group has-success">
                                            <label for="success" class="control-label">Nueva Contraseña</label>
                                            <input type="password" name="newpassword" required class="form-control" id="success">
                                        </div>
                                        <div class="form-group has-success">
                                            <label for="success" class="control-label">Confirmar Contraseña</label>
                                            <input type="password" name="confirmpassword" class="form-control" required id="success">
                                        </div>
                                        <div class="form-group has-success">
                                            <button type="submit" name="submit" class="btn btn-success">Cambiar</button>
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

<!-- Incluye pie de página -->
<?php include('includes/footer.php'); ?>

<?php } // cierre del else ?>

