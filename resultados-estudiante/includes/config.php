<?php
// Definir credenciales de conexión a la base de datos solo si no han sido definidas previamente
if (!defined('DB_HOST')) define('DB_HOST', 'localhost'); // Dirección del servidor de base de datos (por lo general 'localhost')
if (!defined('DB_USER')) define('DB_USER', 'root');       // Usuario de la base de datos (por defecto 'root' en entornos locales)
if (!defined('DB_PASS')) define('DB_PASS', '');           // Contraseña del usuario de la base de datos (vacía por defecto en XAMPP)
if (!defined('DB_NAME')) define('DB_NAME', 'resultados-estudiante'); // Nombre de la base de datos a la que se desea conectar

// Establecer la conexión con la base de datos usando PDO (PHP Data Objects)
try {
    $dbh = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, // Construcción del DSN (Data Source Name)
        DB_USER, // Usuario de la base de datos
        DB_PASS, // Contraseña del usuario
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'") // Configurar juego de caracteres a UTF-8 para evitar problemas con acentos y caracteres especiales
    );
} catch (PDOException $e) {
    // Si ocurre un error en la conexión, se detiene la ejecución y se muestra el mensaje de error
    exit("Error: " . $e->getMessage());
}

