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

    // 1) Buscar primero en admin (admin, docentes y tutores con su correo personal).
    $sql = "SELECT id, UserName, Password, role, teacher_id
            FROM admin
            WHERE UserName = :username
            LIMIT 1";
    $query = $dbh->prepare($sql);
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->execute();

    $user = $query->fetch(PDO::FETCH_OBJ);

    // 2) Si no está en admin, intentar como CORREO DE ALUMNO: el tutor entra con SOLO el
    //    correo institucional de su hijo, SIN contraseña (decisión del usuario 2026-07-09).
    //    Solo admin/docentes (rama 1, arriba) siguen exigiendo contraseña. Se resuelve un
    //    tutor del alumno (CanViewGrades=1) y la sesión se abre como ese tutor, así el
    //    portal muestra TODOS sus hijos (no solo el del correo usado). El acceso sin clave
    //    es una exposición aceptada: quien conozca el correo del alumno ve sus calificaciones.
    if (!$user) {
        $sqlStu = "SELECT a.id, a.UserName, a.Password, a.role, a.teacher_id
                   FROM tblstudents s
                   JOIN student_tutor st ON st.StudentId = s.StudentId AND st.CanViewGrades = 1
                   JOIN admin a ON a.id = st.TutorId AND a.role = 'tutor'
                   WHERE s.StudentEmail = :email AND s.Status = 1
                   LIMIT 1";
        $qStu = $dbh->prepare($sqlStu);
        $qStu->bindParam(':email', $username, PDO::PARAM_STR);
        $qStu->execute();
        $tutorCand = $qStu->fetch(PDO::FETCH_OBJ);

        // Si el correo pertenece a un alumno activo con tutor autorizado, entra SIN validar
        // contraseña (el campo de clave se ignora para padres/tutores).
        if ($tutorCand) {
            $_SESSION['alogin'] = $tutorCand->UserName;
            $_SESSION['role'] = 'tutor';
            $_SESSION['id'] = $tutorCand->id;
            $_SESSION['tutorid'] = $tutorCand->id;
            header("Location: portal-tutor.php");
            exit;
        }
    }

    // Verificar contraseña (password_hash + md5 legacy)
    if ($user && (password_verify($password, $user->Password) || md5($password) === $user->Password)) {
        // Autenticación exitosa
        $_SESSION['alogin'] = $user->UserName;
        $_SESSION['role'] = $user->role;
        $_SESSION['id'] = $user->id;

        // Redirección según rol
        switch ($user->role) {
            case 'admin':
                header("Location: dashboard.php");
                exit;

            case 'director':
                header("Location: dashboard.php");
                exit;

            case 'teacher':
                if (!is_null($user->teacher_id)) {
                    // Bloquea el acceso si la cuenta del docente está desactivada (Status=0).
                    // Permite dar de baja a un docente sin borrarlo (conserva su historial).
                    $chkStatus = $dbh->prepare("SELECT Status FROM tblteachers WHERE Id = :tid");
                    $chkStatus->execute([':tid' => $user->teacher_id]);
                    $teacherStatus = $chkStatus->fetchColumn();
                    if ($teacherStatus !== false && (int)$teacherStatus === 0) {
                        // Limpia las variables de sesión ya asignadas arriba y no redirige.
                        $_SESSION = [];
                        $msg = "Tu cuenta está desactivada. Contacta al instituto.";
                        break;
                    }
                    $_SESSION['teacherid'] = $user->teacher_id;
                    $_SESSION['role'] = 'teacher';
                    $_SESSION['id'] = $user->id;
                    header("Location: dashboard-teacher.php");
                    exit;
                } else {
                    $msg = "Error: Este usuario maestro no tiene asignado un ID de maestro.";
                }
                break;

            case 'tutor':
                $_SESSION['tutorid'] = $user->id;
                $_SESSION['role'] = 'tutor';
                $_SESSION['id'] = $user->id;
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            /* Verdes Institucionales */
            --color-primario: #0F9B3A;
            /* Color 1: Verde brillante */
            --color-secundario: #065D21;
            /* Color 2: Verde oscuro */

            /* Identidad Visual / Fondos Oscuros */
            --color-acento: #32344B;
            /* Color 3: Azul oscuro/Grisáceo */

            /* Variantes de Blanco / Fondos Claros */
            --blanco-fondo: #F0F7F3;
            /* Blanco 1: Fondo general */
            --blanco-suave: #E4F6EA;
            /* Blanco 2: Contenedores/Inputs */

            /* Opcionales útiles */
            --texto-blanco: #FFFFFF;
            --sombra-suave: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body,
        .main-container,
        .content-section {
            font-family: 'Poppins', sans-serif;
            background-color: var(--blanco-fondo);
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            width: 100%;
        }

        .portada {
            display: flex;
            flex-direction: row;
            align-items: stretch;
            justify-content: center;
            width: 100%;
            min-height: 100vh;
        }

        .container-img {
            width: 50%;
            background: linear-gradient(rgba(255, 255, 255, 0.53), rgba(255, 255, 255, 0.53)),
                url('assets/images/indexbg.png');
            background-size: cover;
            background-position: center;
            overflow: hidden;
        }

        .container-form {
            width: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0px 70px 0px 70px;
            gap: 32px;
        }

        .container-title {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .container-title-logo {
            width: 140px;
            height: auto;
        }

        .container-title-nombre {
            font-size: 15px;
            font-weight: 600;
            color: var(--color-secundario);
            margin: 0;
            letter-spacing: 0.3px;
        }

        .container-title-divider {
            width: 40px;
            height: 3px;
            background-color: var(--color-primario);
            border-radius: 2px;
        }

        .container-title h3 {
            font-size: 22px;
            font-weight: 700;
            color: var(--color-acento);
            margin: 0;
            letter-spacing: 0.3px;
        }

        .form-group {
            width: 100%;
            height: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container-input {
            width: 100%;
            height: 100%;
            padding: 9px 0px;
        }

        .input-login {
            width: 100%;
            height: 40px;
            padding: 18px 25px;
            background-color: var(--blanco-suave);
            color: var(--color-acento);
            border: none;
            border-radius: 8px;
            outline: none;
            box-sizing: border-box;
        }

        input::placeholder {
            color: var(--color-acento);
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 16px;
        }

        .container-bottom {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container-button {
            width: 100%;
        }

        .login-button {
            width: 100%;
            height: 44px;
            background-color: var(--color-primario);
            color: var(--texto-blanco);
            border: none;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.2s ease, opacity 0.2s ease;
        }

        .login-button:hover {
            background-color: var(--color-secundario);
        }

        .login-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .a-password {
            color: var(--color-primario);
            font-size: 14px;
            font-weight: 500;
        }

        /* Mensaje de error */
        .login-error {
            width: 100%;
            padding: 12px 18px;
            background-color: #FDE8E8;
            border-left: 4px solid #E53E3E;
            border-radius: 6px;
            color: #C53030;
            font-size: 14px;
            font-weight: 500;
            box-sizing: border-box;
        }

        /* Wrapper password con ojo */
        .input-password-wrapper {
            position: relative;
            width: 100%;
        }

        .input-password-wrapper .input-login {
            padding-right: 48px;
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--color-acento);
            padding: 0;
            display: flex;
            align-items: center;
            font-size: 16px;
        }

        .toggle-password:focus {
            outline: none;
        }

        /* Visually hidden pero accesible para lectores de pantalla */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .footer {
            width: 100%;
            padding: 20px 0;
            background-color: var(--color-acento);
            color: var(--texto-blanco);
            text-align: center;
            font-family: 'Poppins', sans-serif;
        }

        /* Estilos del Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
            background-color: var(--texto-blanco);
            margin: 10% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease-out;
            text-align: center;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            font-size: 20px;
            font-weight: 700;
            color: var(--color-acento);
            margin-bottom: 20px;
        }

        .modal-body {
            font-size: 16px;
            color: var(--color-acento);
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .modal-phone {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-primario);
            margin: 15px 0;
        }

        .modal-button {
            background-color: var(--color-primario);
            color: var(--texto-blanco);
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: background-color 0.3s ease;
        }

        .modal-button:hover {
            background-color: var(--color-secundario);
        }

        .close-modal {
            float: right;
            font-size: 28px;
            font-weight: bold;
            color: var(--color-acento);
            cursor: pointer;
            line-height: 20px;
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: var(--color-primario);
        }

        /* ==========================================================================
   MEDIA QUERY: ADAPTACIÓN A DISPOSITIVOS MÓVILES (FIGMA CORES EXACTOS)
   ========================================================================== */
        @media (max-width: 768px) {
            .portada {
                flex-direction: column;
                background: linear-gradient(rgba(255, 255, 255, 0.53), rgba(255, 255, 255, 0.53)),
                    url('assets/images/indexbg.png');
                background-size: cover;
                background-position: center;
                min-height: 100vh;
            }

            .container-img {
                display: none;
            }

            .container-form {
                width: 100%;
                padding: 60px 30px;
                gap: 28px;
                box-sizing: border-box;
                justify-content: center;
                flex-grow: 1;
            }

            .container-title-logo {
                width: 110px;
            }

            .container-title h3 {
                font-size: 20px;
            }

            .input-login {
                background-color: var(--color-acento);
                color: var(--texto-blanco);
            }

            .input-login::placeholder {
                color: rgba(255, 255, 255, 0.7);
                font-weight: 600;
            }

            .toggle-password {
                color: rgba(255, 255, 255, 0.7);
            }

            .container-bottom p {
                margin-top: 16px;
            }

            .a-password {
                color: var(--color-acento);
            }
        }
    </style>
</head>

<body>

    <section class="portada">
        <!-- Panel izquierdo: imagen de fondo institucional -->
        <div class="container-img"></div>

        <!-- Panel derecho: identidad + formulario -->
        <div class="container-form">
            <div class="container-title">
                <img src="assets/images/logo_no_bg.png" alt="Logo Instituto Panamericano" class="container-title-logo">
                <p class="container-title-nombre">Instituto Panamericano de Tampico</p>
                <div class="container-title-divider"></div>
                <h3>Inicio de Sesión</h3>
            </div>

            <?php if ($msg): ?>
                <div class="login-error" role="alert">
                    <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                    <?php echo htmlentities($msg); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="form-group" id="loginForm">
                <div class="container-input">
                    <label for="username" class="sr-only">Correo electrónico</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Correo electrónico"
                        required
                        autofocus
                        autocomplete="username"
                        class="input-login" />
                </div>

                <div class="container-input">
                    <label for="password" class="sr-only">Contraseña</label>
                    <div class="input-password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Contraseña (solo personal)"
                            autocomplete="current-password"
                            class="input-login" />
                        <button type="button" class="toggle-password" onclick="togglePassword()" aria-label="Mostrar u ocultar contraseña">
                            <i class="fa fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="container-bottom">
                    <div class="container-button">
                        <button type="submit" name="login" id="loginBtn" class="login-button">Iniciar Sesión</button>
                    </div>
                    <p><a href="#" class="a-password" onclick="openPasswordModal(event)">Recuperar contraseña</a></p>
                </div>
            </form>
        </div>
    </section>


    <!-- Modal para Recuperar Contraseña -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closePasswordModal()">&times;</span>
            <div class="modal-header">Recuperar Contraseña</div>
            <div class="modal-body">
                Para recuperar tu contraseña, comunícate con el Instituto:
                <div class="modal-phone">833 3095749</div>
                <p>Nuestro equipo te asistirá en el proceso de recuperación.</p>
            </div>
            <button class="modal-button" onclick="closePasswordModal()">Entendido</button>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2026 Instituto Panamericano de Tampico</p>
        <p>Sistema de Gestión de Calificaciones v2.0</p>
    </footer>

    <script>
        function openPasswordModal(event) {
            event.preventDefault();
            document.getElementById('passwordModal').style.display = 'block';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
        }

        window.onclick = function(event) {
            var modal = document.getElementById('passwordModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        function togglePassword() {
            var input = document.getElementById('password');
            var icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function() {
            var btn = document.getElementById('loginBtn');
            setTimeout(function() {
                btn.disabled = true;
                btn.textContent = 'Verificando...';
            }, 0);
        });
    </script>

</body>

</html>