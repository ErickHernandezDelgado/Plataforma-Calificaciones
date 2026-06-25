<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
error_reporting(E_ALL & ~E_NOTICE); 
include(__DIR__ . '/includes/config.php');

if (!isset($_SESSION['alogin']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$tid = intval($_GET['tid'] ?? 0);
$msg = ""; $error = ""; $nueva_clave_generada = "";

// Genera un token CSRF para proteger las acciones POST (actualizar / resetear clave)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verifica que el docente exista antes de continuar
$chkTeacher = $dbh->prepare("SELECT id FROM tblteachers WHERE id = :tid");
$chkTeacher->execute([':tid' => $tid]);
if (!$tid || !$chkTeacher->fetch()) {
    header("Location: manage-teacher.php");
    exit();
}

// 1. LÓGICA DE ACTUALIZACIÓN (Perfil y Credenciales)
if (isset($_POST['update']) || isset($_POST['reset_pass'])) {
    // Validación CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Solicitud no válida. Recarga la página e inténtalo de nuevo.";
    } else {
    try {
        $dbh->beginTransaction();

        if (isset($_POST['update'])) {
            // Actualizar perfil en tblteachers
            $sql = "UPDATE tblteachers SET TeacherName = :name, TeacherEmail = :email, Gender = :gender, Status = :status WHERE id = :tid";
            $query = $dbh->prepare($sql);
            $query->execute([
                ':name'   => $_POST['teachername'], 
                ':email'  => $_POST['teacheremail'], 
                ':gender' => $_POST['teachergender'], 
                ':status' => (isset($_POST['status']) ? 1 : 0), 
                ':tid'    => $tid
            ]);

            // Sincronizar el UserName en la tabla admin
            $sql_admin = "UPDATE admin SET UserName = :email WHERE teacher_id = :tid";
            $query_admin = $dbh->prepare($sql_admin);
            $query_admin->execute([':email' => $_POST['teacheremail'], ':tid' => $tid]);
            
            $msg = "Datos actualizados correctamente.";
        }

        if (isset($_POST['reset_pass'])) {
            // Generar nueva clave temporal
            $new_password = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 8);

            // Actualizar en la tabla admin con bcrypt (password_hash).
            // El login acepta bcrypt y MD5 legacy, así que no afecta cuentas existentes.
            $sql_pw = "UPDATE admin SET Password = :pass WHERE teacher_id = :tid";
            $query_pw = $dbh->prepare($sql_pw);
            $query_pw->execute([':pass' => password_hash($new_password, PASSWORD_DEFAULT), ':tid' => $tid]);

            // Se guarda la clave en texto plano solo para mostrarla una vez al admin
            $nueva_clave_generada = $new_password;
            $msg = "Contraseña reseteada con éxito.";
        }

        $dbh->commit();
    } catch (Exception $e) {
        $dbh->rollBack();
        $error = "No se pudo completar la operación. Intenta de nuevo.";
    }
    } // Fin de la validación CSRF
}

// 2. CONSULTA CON JOIN PARA OBTENER TODO EL CONTEXTO
// Unimos tblteachers con admin para saber si el usuario tiene cuenta activa
$sql = "SELECT t.*, a.UserName as LoginUser 
        FROM tblteachers t 
        LEFT JOIN admin a ON t.id = a.teacher_id 
        WHERE t.id = :tid";
$query = $dbh->prepare($sql);
$query->execute([':tid' => $tid]);
$result = $query->fetch(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SGC | Editar Docente</title>
    <style>
        .main-page { padding: 25px; background: #f8f9fa; }
        .panel { border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-radius: 8px; }
        
        /* SOLUCIÓN VISUAL: Ajuste para el selector de género */
        .form-control { 
            height: 45px !important; 
            padding: 8px 15px !important; 
            font-size: 14px !important;
            line-height: 1.5 !important;
            display: block !important;
            width: 100%;
        }
        
        .security-box {
            background: #fffbe6;
            border: 1px solid #ffe58f;
            padding: 15px;
            border-radius: 6px;
        }
    </style>
</head>
<body class="top-navbar-fixed">
    <div class="main-wrapper">
        <?php include('includes/topbar.php'); ?>
        <div class="content-wrapper">
            <div class="content-container">
                <?php include('includes/leftbar.php'); ?>

                <div class="main-page">
                    <h2 class="title">Editar Perfil Docente</h2>
                    
                    <div class="panel">
                        <div class="panel-body">
                            <?php if($msg){ ?>
                                <div class="alert alert-success"><?php echo htmlentities($msg); ?></div>
                            <?php } ?>
                            <?php if($nueva_clave_generada){ ?>
                                <div class="alert alert-info">
                                    <strong>Nueva contraseña:</strong> <code><?php echo htmlentities($nueva_clave_generada); ?></code>
                                    <br><small>Anótala y entrégala al docente. No se volverá a mostrar.</small>
                                </div>
                            <?php } ?>
                            <?php if($error){ ?>
                                <div class="alert alert-danger"><?php echo htmlentities($error); ?></div>
                            <?php } ?>

                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>Nombre Completo</label>
                                        <input type="text" name="teachername" class="form-control" value="<?php echo htmlentities($result->TeacherName); ?>" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Correo Electrónico</label>
                                        <input type="email" name="teacheremail" class="form-control" value="<?php echo htmlentities($result->TeacherEmail); ?>" required>
                                    </div>
                                </div>

                                <div class="row" style="margin-top:15px;">
                                    <div class="col-md-4 form-group">
                                        <label>Género</label>
                                        <select name="teachergender" class="form-control" required>
                                            <option value="Male" <?php if($result->Gender=="Male") echo "selected";?>>Masculino</option>
                                            <option value="Female" <?php if($result->Gender=="Female") echo "selected";?>>Femenino</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-8">
                                        <label>Seguridad y Acceso</label>
                                        <div class="security-box">
                                            <p style="margin-bottom: 5px;"><strong>Usuario:</strong> <?php echo htmlentities($result->LoginUser); ?></p>
                                            <button type="submit" name="reset_pass" class="btn btn-warning btn-xs" onclick="return confirm('¿Generar nueva contraseña?')">
                                                Generar Nueva Contraseña
                                            </button>
                                            <p style="font-size: 0.85em; color: #856404; margin-top: 5px;">
                                                * La clave se guarda cifrada (bcrypt) en la tabla 'admin'.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" style="margin-top:20px;">
                                    <div class="col-md-12">
                                        <input type="checkbox" name="status" <?php if($result->Status==1) echo "checked";?>> Cuenta Activa
                                    </div>
                                </div>

                                <hr>
                                <button type="submit" name="update" class="btn btn-primary">Guardar Cambios</button>
                                <a href="manage-teacher.php" class="btn btn-default">Volver</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('includes/footer.php'); ?>
</body>
</html>