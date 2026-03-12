<?php
/**
 * CONFIGURACIÓN PORTÁTIL PARA DESARROLLO Y PRODUCCIÓN
 * 
 * Este archivo soporta override por:
 * 1. Variables de entorno (.env o ENV del sistema)
 * 2. Constantes PHP predefinidas
 * 3. Valores por defecto para desarrollo local
 * 
 * PARA USAR EN SERVIDOR LINUX:
 * Definir variables de entorno antes de inicializar PHP:
 *   export DB_HOST="mysql.tuservidor.com"
 *   export DB_PORT="3306"
 *   export DB_USER="usuario_db"
 *   export DB_PASS="contraseña_segura"
 *   export DB_NAME="resultados-estudiante"
 */

// Cargar archivo .env si existe (para desarrollo local)
if (file_exists(__DIR__ . '/../../.env')) {
    $env_values = parse_ini_file(__DIR__ . '/../../.env');
    foreach ($env_values as $key => $value) {
        putenv("$key=$value");
    }
}

// CONFIGURACIÓN: Credenciales de conexión a la base de datos
// Permite override por variables de entorno (getenv) o constantes predefinidas
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: '3306');  // Puerto MySQL estándar (no XAMPP 3307)
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'resultados-estudiante');

// CONFIGURACIÓN: Rutas base para archivos estáticos
if (!defined('BASE_PATH')) define('BASE_PATH', realpath(__DIR__ . '/../../'));
if (!defined('SRC_PATH'))  define('SRC_PATH', realpath(__DIR__ . '/../'));
if (!defined('ASSETS_PATH')) define('ASSETS_PATH', realpath(__DIR__ . '/../assets'));

// CONFIGURACIÓN: Establecer la conexión con la base de datos usando PDO (PHP Data Objects)
try {
    // Construcción del DSN (Data Source Name) - Compatible con MySQL y MariaDB
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    $dbh = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // Lanzar excepciones en lugar de warnings
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'"
        )
    );
} catch (PDOException $e) {
    // Si ocurre un error en la conexión, se detiene la ejecución y se muestra el mensaje de error
    // En producción, considera registrar esto en logs en lugar de mostrar el mensaje
    error_log("Database Connection Error: " . $e->getMessage());
    exit("❌ Error de conexión a la base de datos. Contacta al administrador.");
}

