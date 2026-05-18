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

    // Buscar al usuario
    $sql = "SELECT id, UserName, Password, role, teacher_id 
            FROM admin 
            WHERE UserName = :username
            LIMIT 1";
    $query = $dbh->prepare($sql);
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->execute();

    $user = $query->fetch(PDO::FETCH_OBJ);

    // Verificar contraseña (password_hash + md5 legacy)
    if ($user && (password_verify($password, $user->Password) || md5($password) === $user->Password)) {
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
     <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            /* Verdes Institucionales */
            --color-primario: #0F9B3A;   /* Color 1: Verde brillante */
            --color-secundario: #065D21; /* Color 2: Verde oscuro */
            
            /* Identidad Visual / Fondos Oscuros */
            --color-acento: #32344B;     /* Color 3: Azul oscuro/Grisáceo */
            
            /* Variantes de Blanco / Fondos Claros */
            --blanco-fondo: #F0F7F3;     /* Blanco 1: Fondo general */
            --blanco-suave: #E4F6EA;     /* Blanco 2: Contenedores/Inputs */
            
            /* Opcionales útiles */
            --texto-blanco: #FFFFFF;
            --sombra-suave: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        body, .main-container, .content-section{
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
        .portada{
            display: flex;
            flex-direction: row;
            align-items: stretch;
            justify-content: center;
            width: 100%;
            min-height: 100vh; 
        }
        .container-img {
            width: 50%;
            justify-content: space-between; /* Para empujar la barra bienvenida al fondo */
            
            /* Configuración del Fondo (Imagen al 47% de opacidad) */
            background: linear-gradient(rgba(255,255,255,0.53), rgba(255,255,255,0.53)), 
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
            gap: 48px;
        }

        .container-title {
            width: 100%;
            text-align: center;
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
            border:none;
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
            height: 40px;
            background-color: var(--color-acento);
            color: var(--texto-blanco);
            border: none;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
        }
        
        .a-password {
            color: var(--color-primario);
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
            from { opacity: 0; }
            to { opacity: 1; }
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
        /* Fondo con la imagen de la escuela detrás de todo */
        background: linear-gradient(rgba(255,255,255,0.53), rgba(255,255,255,0.53)), 
                    url('assets/images/indexbg.png');
        background-size: cover;
        background-position: center;
        min-height: 100vh;
    }

    .container-img {
        display: none; /* Ocultamos la mitad de escritorio */
    }

    .container-form {
        width: 100%;
        padding: 60px 30px;
        gap: 32px;
        box-sizing: border-box;
        justify-content: center;
        flex-grow: 1;
    }

    .container-title h3 {
        font-size: 22px;
        letter-spacing: 0.5px;
        margin: 0;
        color: var(--color-acento); /* Manteniendo el título oscuro */
    }

    /* INPUTS EN MÓVIL: Color Oscuro de Figma (#32344B) */
    .input-login {
        background-color: var(--color-acento); 
        color: var(--texto-blanco);
        text-transform: lowercase;
    }

    .input-login::placeholder {
        color: rgba(255, 255, 255, 0.7); /* Texto del placeholder en blanco suave */
        font-weight: 700;
        text-transform: lowercase;
    }

    /* BOTÓN EN MÓVIL: Verde Brillante de Figma (#0F9B3A) */
    .login-button {
        background-color: var(--color-primario); 
        color: var(--texto-blanco);
        text-transform: uppercase; /* Fuerza el "ENTRAR" en mayúsculas */
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(15, 155, 58, 0.2); /* Sutil destello verde abajo */
    }

    /* ENLACE RECUPERAR: Mismo tono oscuro del input */
    .a-password {
        color: var(--color-acento);
        text-decoration: underline;
        font-size: 14px;
        font-weight: 600;
    }
    
    .container-bottom p {
        margin-top: 20px;
    }
}

    </style>
</head>
<body>

    <!-- Mensajes -->
    <?php if ($msg): ?>
        <div id="message">
            <?php echo htmlentities($msg); ?>
        </div>
    <?php endif; ?>

    <section class="portada">
        <div class="container-img">
        </div>
        <!-- Formulario de Login -->
        <div class="container-form">
            <div class="container-title">
                <h3>INICIO DE SESION</h3>
            </div>
            <form method="POST" action="" class = "form-group">
                <div class="container-input">
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="Correo electronico"
                        required 
                        autofocus
                        class="input-login"
                    />
                </div>

                <div class="container-input">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="contraseña"
                        required
                        class="input-login"
                    />
                </div>

                <div class="container-bottom">
                    <div class="container-button">
                        <button type="submit" name="login" class="login-button">Iniciar Sesión</button>
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
        // Funciones para manejar el modal de recuperar contraseña
        function openPasswordModal(event) {
            event.preventDefault();
            document.getElementById('passwordModal').style.display = 'block';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera de él
        window.onclick = function(event) {
            var modal = document.getElementById('passwordModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>

</body>
</html>
