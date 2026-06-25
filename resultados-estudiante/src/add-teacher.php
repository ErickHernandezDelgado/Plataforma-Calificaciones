<?php
/**
 * add-teacher.php
 * Agregar nuevo docente al sistema
 */
include(__DIR__ . '/includes/check-login.php');

// Verificación de rol admin (check-login.php solo valida sesión, no rol)
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

error_reporting(0);
ini_set('display_errors', 0);
$msg = ""; $error = "";
$msg_teacher_email = ""; $msg_teacher_password = "";

function generatePassword($length = 8) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

// Procesar envío de formulario
if (isset($_POST['submit'])) {
    $teachername = trim($_POST['fullname'] ?? '');
    $teacheremail = trim($_POST['emailid'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $status = 1;

    // Validación del lado servidor
    if ($teachername === '' || $teacheremail === '' || $dob === '') {
        $error = "Completa todos los campos obligatorios.";
    } elseif (!filter_var($teacheremail, FILTER_VALIDATE_EMAIL)) {
        $error = "El correo electrónico no es válido.";
    } elseif (!in_array($gender, ['Male', 'Female'], true)) {
        $error = "Selecciona un género válido.";
    } else {
        // Genera la contraseña temporal y la cifra con bcrypt (password_hash).
        // El login (index.php) acepta bcrypt y MD5 legacy, así que esto no afecta cuentas existentes.
        $password_plana = generatePassword(8);
        $password_hash = password_hash($password_plana, PASSWORD_DEFAULT);

        try {
            $dbh->beginTransaction();
            $sql = "INSERT INTO tblteachers(TeacherName, TeacherEmail, Gender, DOB, Status) VALUES(:teachername, :teacheremail, :gender, :dob, :status)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':teachername', $teachername, PDO::PARAM_STR);
            $query->bindParam(':teacheremail', $teacheremail, PDO::PARAM_STR);
            $query->bindParam(':gender', $gender, PDO::PARAM_STR);
            $query->bindParam(':dob', $dob, PDO::PARAM_STR);
            $query->bindParam(':status', $status, PDO::PARAM_INT);
            $query->execute();

            $lastInsertId = $dbh->lastInsertId();

            if ($lastInsertId) {
                $sql_admin = "INSERT INTO admin (UserName, Password, role, teacher_id) VALUES(:username, :password, :role, :teacher_id)";
                $query_admin = $dbh->prepare($sql_admin);
                $query_admin->bindParam(':username', $teacheremail, PDO::PARAM_STR);
                $query_admin->bindParam(':password', $password_hash, PDO::PARAM_STR);
                $query_admin->bindValue(':role', 'teacher', PDO::PARAM_STR);
                $query_admin->bindParam(':teacher_id', $lastInsertId, PDO::PARAM_INT);

                if ($query_admin->execute()) {
                    $dbh->commit();
                    $msg_teacher_email = $teacheremail;
                    $msg_teacher_password = $password_plana;
                    $msg = "Docente agregado correctamente.";
                } else {
                    $dbh->rollBack();
                    $error = "Error al crear la cuenta.";
                }
            }
        } catch (PDOException $e) {
            $dbh->rollBack();
            // 23000 = violación de integridad (email duplicado en tblteachers o admin)
            if ($e->getCode() == 23000) {
                $error = "Ya existe un docente o usuario registrado con ese correo electrónico.";
            } else {
                $error = "No se pudo agregar el docente. Intenta de nuevo.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SGC | Agregar Docente</title>
    <link rel="stylesheet" href="css/bootstrap.min.css" media="screen" >
    <link rel="stylesheet" href="css/font-awesome.min.css" media="screen" >
    <link rel="stylesheet" href="css/main.css" media="screen" >
    
    <style>
        /* 1. Forzar el color verde en el encabezado del panel */
        .panel-verde .panel-heading {
            background-color: #0F9B3A !important;
            color: white !important;
            border: none !important;
        }

        /* 2. Ocultar textos basura o marcas de agua del fondo */
        body {
            background-color: #f5f5f5 !important;
        }
        
        /* Esto intentará ocultar el texto de GitHub que mencionas */
        .main-page {
            background: white !important;
            position: relative;
            z-index: 1;
        }

        /* 3. Estilo del botón */
        .btn-verde {
            background-color: #0F9B3A !important;
            border-color: #0F9B3A !important;
            color: white !important;
        }

        /* 4. Limpiar el fondo del contenedor principal */
        .content-container {
            background-color: white !important;
        }

        /* Ajustes de espaciado */
        .panel { border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0; }
        label { font-weight: bold; margin-bottom: 5px; color: #333; }
    </style>
</head>
<body class="top-navbar-fixed">
    <div class="main-wrapper">
        <?php include('includes/topbar.php'); ?>
        <div class="content-wrapper">
            <div class="content-container">
                <?php include('includes/leftbar.php'); ?>
                <div class="main-page">
                    <div class="container-fluid">
                        <div class="row page-title-div">
                            <div class="col-md-6">
                                <h2 class="title">Administración de Docentes</h2>
                            </div>
                        </div>
                    </div>

                    <section class="section">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-md-10 col-md-offset-1">
                                    <div class="panel panel-verde">
                                        <div class="panel-heading">
                                            <div class="panel-title">
                                                <h5>COMPLETAR INFORMACIÓN DEL NUEVO DOCENTE</h5>
                                            </div>
                                        </div>
                                        <div class="panel-body p-20">
                                            <?php if($msg){ ?>
                                                <div class="alert alert-success"><strong>Éxito:</strong> <?php echo htmlentities($msg); ?></div>
                                                <div class="well">
                                                    <strong>Usuario:</strong> <?php echo htmlentities($msg_teacher_email); ?><br>
                                                    <strong>Contraseña:</strong> <code><?php echo htmlentities($msg_teacher_password); ?></code>
                                                </div>
                                            <?php } elseif($error){ ?>
                                                <div class="alert alert-danger"><strong>Error:</strong> <?php echo htmlentities($error); ?></div>
                                            <?php } ?>

                                            <form method="post" class="row">
                                                <div class="form-group col-md-6">
                                                    <label>Nombre Completo</label>
                                                    <input type="text" name="fullname" class="form-control" placeholder="Ej: Juan Pérez" required>
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Correo Electrónico</label>
                                                    <input type="email" name="emailid" class="form-control" placeholder="correo@institucion.edu.mx" required>
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Género</label><br>
                                                    <input type="radio" name="gender" value="Male" checked> Masculino &nbsp;
                                                    <input type="radio" name="gender" value="Female"> Femenino
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Fecha de Nacimiento</label>
                                                    <input type="date" name="dob" class="form-control" required>
                                                </div>
                                                <div class="col-md-12">
                                                    <button type="submit" name="submit" class="btn btn-verde">
                                                        <i class="fa fa-check"></i> Guardar Docente
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
    <?php include('includes/footer.php'); ?>
</body>
</html>