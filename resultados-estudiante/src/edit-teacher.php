<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
error_reporting(E_ALL & ~E_NOTICE); 
include(__DIR__ . '/includes/config.php');

if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php");
    exit();
}

$tid = intval($_GET['tid']);
$msg = ""; $error = "";

// 1. LÓGICA DE ACTUALIZACIÓN (Perfil y Credenciales)
if (isset($_POST['update']) || isset($_POST['reset_pass'])) {
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
            
            // Actualizar SOLO en la tabla admin usando MD5
            $sql_pw = "UPDATE admin SET Password = :pass WHERE teacher_id = :tid";
            $query_pw = $dbh->prepare($sql_pw);
            $query_pw->execute([':pass' => md5($new_password), ':tid' => $tid]);
            
            $msg = "Contraseña reseteada con éxito. La nueva clave es: <strong>$new_password</strong>";
        }

        $dbh->commit();
    } catch (Exception $e) {
        $dbh->rollBack();
        $error = "Error: " . $e->getMessage();
    }
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
                            <?php if($msg){ echo "<div class='alert alert-success'>$msg</div>"; } ?>
                            <?php if($error){ echo "<div class='alert alert-danger'>$error</div>"; } ?>

                            <form method="post">
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
                                                * La clave se guarda cifrada en MD5 en la tabla 'admin'.
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