<?php
/**
 * index.php - Login System
 * 
 * Sistema de autenticación unificado para:
 * - Admin: Gestión completa
 * - Teacher: Sin solo notas
 * - Tutor: Acceso a calificaciones de hijos
 */

session_start();
error_reporting(0);
include(__DIR__ . '/includes/config.php');

$msg = "";

// Verificar si se envió el formulario de login
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // PASO 1: Intentar login normal (admin, maestro)
    $sql = "SELECT id, UserName, Password, role, teacher_id 
            FROM admin 
            WHERE UserName = :username
            LIMIT 1";
    $query = $dbh->prepare($sql);
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->execute();

    $user = $query->fetch(PDO::FETCH_OBJ);

    // PASO 2: Si no encontró en admin, buscar en vw_tutor_login (login con email del alumno)
    if (!$user) {
        $sql_tutor = "SELECT TutorId as id, TutorEmail as UserName, Password, 'tutor' as role, NULL as teacher_id
                      FROM vw_tutor_login 
                      WHERE StudentEmail = :email
                      LIMIT 1";
        $query_tutor = $dbh->prepare($sql_tutor);
        $query_tutor->bindParam(':email', $username, PDO::PARAM_STR);
        $query_tutor->execute();
        
        $user = $query_tutor->fetch(PDO::FETCH_OBJ);
    }

    // PASO 3: Verificar contraseña (TEXTO PLANO, sin hash)
    if ($user && ($password === $user->Password)) {
        // Autenticación exitosa
        $_SESSION['alogin'] = $user->UserName;
        $_SESSION['role'] = $user->role;
        $_SESSION['id'] = $user->id;

        // Redirección según rol
        switch($user->role) {
            case 'admin':
                header("Location: dashboard.php");
                exit;
                
            case 'director':
                header("Location: dashboard.php");
                exit;
                
            case 'teacher':
                if (!is_null($user->teacher_id)) {
                    $_SESSION['teacherid'] = $user->teacher_id;
                    header("Location: dashboard-teacher.php");
                    exit;
                } else {
                    $msg = "Error: Este usuario maestro no tiene asignado un ID de maestro.";
                }
                break;
                
            case 'tutor':
                $_SESSION['tutorid'] = $user->id;
                header("Location: portal-tutor.php");
                exit;
                
            default:
                $msg = "Rol no válido en el sistema.";
        }
    } else {
        $msg = "Usuario o contraseña incorrectos. Intenta de nuevo.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instituto Panamericano - Acceso al Sistema</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 450px;
        }

        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: linear-gradient(135deg, #238D15 0%, #1a6b0f 100%);
            padding: 50px 30px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -20%;
            width: 250px;
            height: 250px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }

        .login-header-content {
            position: relative;
            z-index: 1;
        }

        .school-icon {
            font-size: 60px;
            margin-bottom: 15px;
            display: block;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .school-name {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }

        .school-tagline {
            font-size: 12px;
            opacity: 0.9;
            font-weight: 300;
        }

        .login-body {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f9f9f9;
        }

        .form-control:focus {
            outline: none;
            border-color: #238D15;
            background: white;
            box-shadow: 0 0 0 3px rgba(35, 141, 21, 0.1);
        }

        .form-control::placeholder {
            color: #aaa;
        }

        .input-group-text {
            background: transparent;
            border: 2px solid #e0e0e0;
            border-left: none;
            color: #238D15;
            padding: 0 15px;
        }

        .form-control:focus + .input-group-text {
            border-color: #238D15;
        }

        .input-group > .form-control {
            border-right: none;
        }

        .alert-message {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 13px;
            animation: slideIn 0.3s ease-out;
        }

        .alert-danger {
            background: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }

        .alert-success {
            background: #efe;
            color: #3a3;
            border-left: 4px solid #3a3;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #238D15 0%, #1a6b0f 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(35, 141, 21, 0.3);
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(35, 141, 21, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .form-check {
            display: flex;
            align-items: center;
        }

        .form-check-input {
            margin-right: 8px;
        }

        .remember-forgot a {
            color: #238D15;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .remember-forgot a:hover {
            color: #1a6b0f;
        }

        .login-footer {
            text-align: center;
            padding: 20px 30px;
            background: #f9f9f9;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #666;
        }

        .demo-credentials {
            background: #f0f8ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 12px;
        }

        .demo-credentials h6 {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 12px;
        }

        .demo-row {
            margin-bottom: 8px;
            padding: 5px 0;
        }

        .demo-row strong {
            color: #333;
            display: inline-block;
            min-width: 70px;
        }

        .demo-row code {
            background: white;
            padding: 2px 6px;
            border-radius: 3px;
            color: #238D15;
            font-family: 'Courier New', monospace;
            font-size: 11px;
        }

        @media (max-width: 576px) {
            .login-wrapper {
                max-width: 100%;
            }

            .login-header {
                padding: 40px 20px;
            }

            .login-body, .login-footer {
                padding: 30px 20px;
            }

            .school-icon {
                font-size: 45px;
            }

            .school-name {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <!-- Header -->
        <div class="login-header">
            <div class="login-header-content">
                <i class="fas fa-graduation-cap school-icon"></i>
                <div class="school-name">Instituto Panamericano</div>
                <div class="school-tagline">Sistema de Calificaciones</div>
            </div>
        </div>

        <!-- Body -->
        <div class="login-body">
            <!-- Mensajes -->
            <?php if ($msg): ?>
                <div class="alert-message alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlentities($msg); ?>
                </div>
            <?php endif; ?>

            <!-- Formulario de Login -->
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="username">
                        <i class="fas fa-user"></i> Usuario
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        placeholder="Correo o nombre de usuario"
                        required 
                        autofocus
                    />
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fas fa-lock"></i> Contraseña
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Ingresa tu contraseña"
                        required
                    />
                </div>

                <div class="remember-forgot">
                    <label class="form-check">
                        <input type="checkbox" name="remember" class="form-check-input">
                        Recuérdame
                    </label>
                    <a href="#">¿Olvidaste la contraseña?</a>
                </div>

                <button type="submit" name="login" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>

            <!-- Credenciales de Prueba -->
            <div class="demo-credentials">
                <h6><i class="fas fa-info-circle"></i> Credenciales de Prueba</h6>
                <div class="demo-row">
                    <strong>Admin:</strong>
                    <code>admin</code> / <code>admin123</code>
                </div>
                <div class="demo-row">
                    <strong>Docente:</strong>
                    <code>Brenda.Vazquez@ipt.edu.mx</code> / <code>brenda2025</code>
                </div>
                <div class="demo-row">
                    <strong>Tutor:</strong>
                    <code>Farah.Balderas@ipt.edu.mx</code> / <code>tutor123</code>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <p>&copy; 2026 Instituto Panamericano de Tampico</p>
            <p style="margin-top: 5px; opacity: 0.7;">Sistema de Gestión de Calificaciones v2.0</p>
        </div>
    </div>
</div>

<script>
    // Efecto enfoque en inputs
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.opacity = '1';
        });
    });
</script>

</body>
</html>
