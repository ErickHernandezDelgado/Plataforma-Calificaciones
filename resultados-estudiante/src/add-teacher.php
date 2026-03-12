<?php
// Iniciar sesión
session_start();

// Desactiva la notificación de errores en producción
error_reporting(0);

// Conexión a la base de datos
include(__DIR__ . '/includes/config.php');

// Verifica si hay sesión activa del administrador
if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php"); // Si no hay sesión, redirige al login
} else {
    // Si el formulario fue enviado
    if (isset($_POST['submit'])) {
        $teachername = $_POST['fullname'];        // Nombre del docente
        $teacheremail = $_POST['emailid'];        // Correo electrónico
        $gender = $_POST['gender'];               // Género seleccionado
        $dob = $_POST['dob'];                     // Fecha de nacimiento
        $joiningdate = date('Y-m-d H:i:s');       // Fecha actual como fecha de ingreso
        $status = 1;                              // Activo por defecto
        
        // Generar contraseña en TEXTO PLANO (8 caracteres)
        // Nota: Las contraseñas de docentes se guardan en TEXTO PLANO para auditoría administrativa
        $password = generatePassword(8);

        // Consulta para insertar nuevo docente
        $sql = "INSERT INTO tblteachers(TeacherName, TeacherEmail, Gender, DOB, JoiningDate, Status, Password)
                VALUES(:teachername, :teacheremail, :gender, :dob, :joiningdate, :status, :password)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':teachername', $teachername, PDO::PARAM_STR);
        $query->bindParam(':teacheremail', $teacheremail, PDO::PARAM_STR);
        $query->bindParam(':gender', $gender, PDO::PARAM_STR);
        $query->bindParam(':dob', $dob, PDO::PARAM_STR);
        $query->bindParam(':joiningdate', $joiningdate, PDO::PARAM_STR);
        $query->bindParam(':status', $status, PDO::PARAM_INT);
        $query->bindParam(':password', $password, PDO::PARAM_STR);
        $query->execute();

        // Verificar si la inserción fue exitosa
        $lastInsertId = $dbh->lastInsertId();
        if ($lastInsertId) {
            $msg_teacher_name = $teachername;
            $msg_teacher_email = $teacheremail;
            $msg_teacher_password = $password;
            $msg = "Docente agregado correctamente.";
        } else {
            $error = "Algo salió mal. Inténtalo de nuevo.";
        }
    }
?>

<!-- Incluye la barra superior -->
<?php include('includes/topbar.php'); ?>

<!-- Contenedor principal -->
<div class="content-wrapper">
    <div class="content-container">

        <!-- Menú lateral -->
        <?php include('includes/leftbar.php'); ?>

        <!-- Contenido principal -->
        <div class="main-page">
            <div class="container-fluid">

                <!-- Título de la página -->
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Agregar Docente</h2>
                    </div>
                </div>

                <!-- Breadcrumb -->
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li class="active">Agregar Docente</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sección del formulario -->
            <section class="section">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Completa la información del docente</h5>
                                    </div>
                                </div>

                                <div class="panel-body">
                                    <!-- Mensajes de éxito o error -->
                                    <?php if ($msg) { ?>
                                        <div class="alert alert-success left-icon-alert" role="alert">
                                            <strong>Bien hecho! </strong><?php echo htmlentities($msg); ?>
                                        </div>
                                        <!-- Mostrar credenciales de acceso del docente -->
                                        <div class="alert alert-info" role="alert" style="margin-top: 15px;">
                                            <h5><strong>Credenciales de Acceso del Docente</strong></h5>
                                            <p style="margin: 10px 0;">
                                                <strong>Nombre:</strong> <?php echo htmlentities($msg_teacher_name); ?><br>
                                                <strong>Correo:</strong> <?php echo htmlentities($msg_teacher_email); ?><br>
                                                <strong>Contraseña (Temporal):</strong><br>
                                                <code style="display: inline-block; background-color: #fff3cd; padding: 8px 12px; border-radius: 4px; font-size: 14px; font-weight: bold; margin-top: 5px;">
                                                    <?php echo htmlentities($msg_teacher_password); ?>
                                                </code>
                                            </p>
                                            <p style="margin: 10px 0; font-size: 13px; color: #666;">
                                                <em>⚠ Nota: Comparte estas credenciales con el docente. Pídele que cambie la contraseña en su primer acceso.</em>
                                            </p>
                                        </div>
                                        <!-- Botones de acción post-registro -->
                                        <div style="margin-top: 15px;">
                                            <a href="manage-teacher.php" class="btn btn-primary">
                                                <i class="fa fa-list"></i> Ver todos los docentes
                                            </a>
                                            <a href="add-teacher.php" class="btn btn-success">
                                                <i class="fa fa-plus"></i> Agregar otro docente
                                            </a>
                                            <a href="assign-teacher-subject.php?teacherid=<?php echo htmlentities($lastInsertId); ?>" class="btn btn-warning">
                                                <i class="fa fa-book"></i> Asignar materias
                                            </a>
                                        </div>
                                    <?php } else if ($error) { ?>
                                        <div class="alert alert-danger left-icon-alert" role="alert">
                                            <strong>Algo salió mal!</strong> <?php echo htmlentities($error); ?>
                                        </div>
                                    <?php } ?>

                                    <!-- Formulario para agregar docente -->
                                    <form class="row" method="post">

                                        <!-- Nombre completo -->
                                        <div class="form-group col-md-6">
                                            <label for="fullname" class="control-label">Nombre Completo</label>
                                            <input type="text" name="fullname" class="form-control" id="fullname" required>
                                        </div>

                                        <!-- Correo electrónico -->
                                        <div class="form-group col-md-6">
                                            <label for="emailid" class="control-label">Correo</label>
                                            <input type="email" name="emailid" class="form-control" id="emailid" required>
                                        </div>

                                        <!-- Género -->
                                        <div class="form-group col-md-6">
                                            <label class="control-label">Género</label><br>
                                            <label><input type="radio" name="gender" value="Male" checked> Masculino</label>
                                            <label><input type="radio" name="gender" value="Female"> Femenino</label>
                                            <label><input type="radio" name="gender" value="Other"> Otro</label>
                                        </div>

                                        <!-- Fecha de nacimiento -->
                                        <div class="form-group col-md-6">
                                            <label for="dob" class="control-label">Fecha de Nacimiento</label>
                                            <input type="date" name="dob" class="form-control" id="dob" required>
                                        </div>

                                        <!-- Botón de enviar -->
                                        <div class="form-group col-md-12">
                                            <button type="submit" name="submit" class="btn btn-success">Agregar</button>
                                        </div>

                                    </form>
                                </div> <!-- /.panel-body -->
                            </div> <!-- /.panel -->
                        </div> <!-- /.col -->
                    </div> <!-- /.row -->
                </div> <!-- /.container-fluid -->
            </section> <!-- /.section -->
        </div> <!-- /.main-page -->
    </div> <!-- /.content-container -->
</div> <!-- /.content-wrapper -->

<!-- Pie de página -->
<?php include('includes/footer.php'); ?>

<?php } // Cierre del else ?>

