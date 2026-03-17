<?php
/**
 * index.php - Sistema Unificado de Login
 * Soporta: Admin, Maestro, Tutor
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include(__DIR__ . '/includes/config.php');

$msg = "";
$error = "";

// Procesar login
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT id, UserName, Password, role, teacher_id FROM admin WHERE UserName = :username LIMIT 1";
    $query = $dbh->prepare($sql);
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->execute();
    $user = $query->fetch(PDO::FETCH_OBJ);

    if ($user && (password_verify($password, $user->Password) || md5($password) === $user->Password)) {
        $_SESSION['alogin'] = $user->UserName;
        $_SESSION['role'] = $user->role;
        $_SESSION['id'] = $user->id;

        if ($user->role === 'teacher') {
            if (!is_null($user->teacher_id)) {
                $_SESSION['teacherid'] = $user->teacher_id;
                header("Location: dashboard-teacher.php");
                exit;
            } else {
                $error = "❌ Este maestro no tiene asignado un ID válido.";
            }
        } elseif ($user->role === 'admin') {
            header("Location: dashboard.php");
            exit;
        } elseif ($user->role === 'tutor') {
            $_SESSION['tutorid'] = $user->id;
            header("Location: portal-tutor.php");
            exit;
        }
    } else {
        $error = "❌ Usuario o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Centro Educativo IPT</title>
    <link rel="stylesheet" href="./assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div style="text-align: center; margin-bottom: 2rem;">
            <i class="fas fa-graduation-cap" style="font-size: 3rem; color: #667eea; margin-bottom: 1rem;"></i>
            <h1 style="color: #667eea; margin: 0;">Centro Educativo IPT</h1>
            <p style="color: #666; margin: 0.5rem 0;">Sistema de Calificaciones</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($msg): ?>
            <div class="alert alert-success">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form method="post" style="padding: 0;">
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Usuario
                </label>
                <input 
                    type="text" 
                    id="username"
                    name="username" 
                    class="form-control" 
                    placeholder="Email o usuario"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Contraseña
                </label>
                <input 
                    type="password" 
                    id="password"
                    name="password" 
                    class="form-control" 
                    placeholder="Tu contraseña"
                    required
                >
            </div>

            <button type="submit" name="login" class="btn btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.85rem; color: #666;">
            <p>Credenciales de Prueba:</p>
            <p><strong>Admin:</strong> admin / (contraseña admin)</p>
            <p><strong>Maestro:</strong> (email maestro asignado)</p>
        </div>
    </div>
</body>
</html>
