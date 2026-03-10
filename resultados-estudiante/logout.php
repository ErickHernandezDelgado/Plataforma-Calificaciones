<?php
// Inicia la sesión actual
session_start();

// Vacía el arreglo $_SESSION para eliminar todas las variables de sesión
$_SESSION = array();

// Verifica si la sesión usa cookies
if (ini_get("session.use_cookies")) {
    // Obtiene los parámetros de la cookie de sesión actual
    $params = session_get_cookie_params();
    
    // Borra la cookie de sesión estableciendo su expiración en tiempo pasado
    setcookie(session_name(), '', time() - 60*60,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Elimina específicamente la variable de sesión 'login' si existe
unset($_SESSION['login']);

// Destruye la sesión completamente
session_destroy();

// Redirige al usuario a la página de inicio (index.php)
header("location:index.php");
?>
