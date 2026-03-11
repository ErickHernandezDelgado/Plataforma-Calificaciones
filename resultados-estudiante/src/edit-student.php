<?php
// Inicia la sesión
session_start();

// Desactiva los mensajes de error (no recomendado en producción, es mejor manejar errores de forma controlada)
error_reporting(0);

// Incluye el archivo de configuración (conexión a la base de datos, entre otros)
include(__DIR__ . '/includes/config.php');

// Verifica si el usuario administrador ha iniciado sesión
if (strlen($_SESSION['alogin']) == "") {
    // Si no ha iniciado sesión, redirige al login
    header("Location: index.php");
    exit;
} else {

    // Obtiene y convierte el ID del estudiante desde el parámetro GET
    $stid = intval($_GET['stid']);

    // Si el formulario fue enviado
    if (isset($_POST['submit'])) {
        // Recupera los valores del formulario
        $studentname = $_POST['fullanme']; // Nota: 'fullanme' está mal escrito, debería ser 'fullname'
        $roolid = $_POST['rollid'];
        $studentemail = $_POST['emailid'];
        $curp = $_POST['curp'];
        $status = $_POST['status'];

        // Consulta SQL para actualizar los datos del estudiante
        $sql = "UPDATE tblstudents 
                SET StudentName = :studentname, RollId = :roolid, StudentEmail = :studentemail, 
                    CURP = :curp, Status = :status 
                WHERE StudentId = :stid";

        // Prepara la consulta
        $query = $dbh->prepare($sql);

        // Asocia los parámetros a los valores del formulario
        $query->bindParam(':studentname', $studentname, PDO::PARAM_STR);
        $query->bindParam(':roolid', $roolid, PDO::PARAM_STR);
        $query->bindParam(':studentemail', $studentemail, PDO::PARAM_STR);
        $query->bindParam(':curp', $curp, PDO::PARAM_STR);
        $query->bindParam(':status', $status, PDO::PARAM_STR);
        $query->bindParam(':stid', $stid, PDO::PARAM_INT);

        // Ejecuta la consulta
        $query->execute();

        // Mensaje de éxito
        $msg = "Información de estudiante actualizada correctamente";
    }
?>

<!-- Incluye la barra superior -->
<?php include('includes/topbar.php'); ?>

<div class="content-wrapper">
    <div class="content-container">
        <!-- Incluye la barra lateral izquierda -->
        <?php include('includes/leftbar.php'); ?>

        <div class="main-page">
            <div class="container-fluid">
                <!-- Título de la página -->
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Editar Estudiante</h2>
                    </div>
                </div>

                <!-- Breadcrumb de navegación -->
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li class="active">Editar Estudiante</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel">
                            <div class="panel-heading">
                                <div class="panel-title">
                                    <h5>Editar información del estudiante</h5>
                                </div>
                            </div>

                            <div class="panel-body">
                                <!-- Muestra mensaje de éxito o error si existe -->
                                <?php if ($msg) { ?>
                                    <div class="alert alert-success left-icon-alert" role="alert">
                                        <strong>Éxito! </strong><?php echo htmlentities($msg); ?>
                                    </div>
                                <?php } elseif ($error) { ?>
                                    <div class="alert alert-danger left-icon-alert" role="alert">
                                        <strong>Error! </strong><?php echo htmlentities($error); ?>
                                    </div>
                                <?php } ?>

                                <!-- Formulario de edición del estudiante -->
                                <form class="form-horizontal" method="post">
                                    <?php
                                    // Consulta para obtener los datos actuales del estudiante
                                    $sql = "SELECT StudentName, RollId, RegDate, StudentId, Status, StudentEmail, CURP, ClassId, ClassName, Section 
                                            FROM tblstudents 
                                            JOIN tblclasses ON tblclasses.id = tblstudents.ClassId 
                                            WHERE StudentId = :stid";

                                    // Prepara y ejecuta la consulta
                                    $query = $dbh->prepare($sql);
                                    $query->bindParam(':stid', $stid, PDO::PARAM_INT);
                                    $query->execute();

                                    // Obtiene el resultado como objeto
                                    $result = $query->fetch(PDO::FETCH_OBJ);

                                    // Si se encontró el estudiante, muestra el formulario con sus datos
                                    if ($result) {
                                    ?>
                                        <!-- Campo: Nombre completo -->
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Nombre Completo</label>
                                            <div class="col-sm-10">
                                                <input type="text" name="fullanme" class="form-control" value="<?php echo htmlentities($result->StudentName); ?>" required>
                                            </div>
                                        </div>

                                        <!-- Campo: ID Rol -->
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">ID Rol</label>
                                            <div class="col-sm-10">
                                                <input type="text" name="rollid" class="form-control" maxlength="5" value="<?php echo htmlentities($result->RollId); ?>" required>
                                            </div>
                                        </div>

                                        <!-- Campo: Correo -->
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Correo</label>
                                            <div class="col-sm-10">
                                                <input type="email" name="emailid" class="form-control" value="<?php echo htmlentities($result->StudentEmail); ?>" required>
                                            </div>
                                        </div>

                                        <!-- Campo: CURP -->
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">CURP</label>
                                            <div class="col-sm-10">
                                                <input type="text" name="curp" class="form-control" maxlength="18" value="<?php echo htmlentities($result->CURP); ?>" required>
                                            </div>
                                        </div>

                                        <!-- Campo: Año y Sección -->
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Año</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" value="<?php echo htmlentities($result->ClassName) . " - Sección " . htmlentities($result->Section); ?>" readonly>
                                            </div>
                                        </div>

                                        <!-- Campo: Fecha de Registro -->
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Fecha de Registro</label>
                                            <div class="col-sm-10">
                                                <p class="form-control-static"><?php echo htmlentities($result->RegDate); ?></p>
                                            </div>
                                        </div>

                                        <!-- Campo: Estado del estudiante (Activo/Inactivo) -->
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Estado</label>
                                            <div class="col-sm-10">
                                                <label><input type="radio" name="status" value="1" <?php if ($result->Status == 1) echo 'checked'; ?>> Activo</label>
                                                <label><input type="radio" name="status" value="0" <?php if ($result->Status == 0) echo 'checked'; ?>> Inactivo</label>
                                            </div>
                                        </div>

                                        <!-- Botón para enviar el formulario -->
                                        <div class="form-group">
                                            <div class="col-sm-offset-2 col-sm-10">
                                                <button type="submit" name="submit" class="btn btn-primary">Actualizar</button>
                                            </div>
                                        </div>
                                    <?php } else {
                                        // Si no se encuentra el estudiante, muestra un mensaje
                                        echo "<p>Estudiante no encontrado.</p>";
                                    } ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<!-- Incluye el pie de página -->
<?php include('includes/footer.php'); ?>

<?php } ?>

