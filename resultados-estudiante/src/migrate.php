<?php
/**
 * SCRIPT DE MIGRACIÓN - Sistema de Calificaciones
 * Ejecuta en: http://localhost/resultados-estudiante/migrate.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$db = 'resultados-estudiante';
$user = 'root';
$pass = '';
$port = 3307;

$results = [];
$errors = [];

// Conectar a BD
try {
    $dbh = new PDO(
        "mysql:host=$host;dbname=$db;port=$port;charset=utf8",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $results[] = "✓ Conexión a BD exitosa";
} catch (PDOException $e) {
    $errors[] = "ERROR: No se pudo conectar a BD. " . $e->getMessage();
    $dbh = null;
}

// Si la conexión fue exitosa, ejecutar migraciones
if ($dbh) {
    try {
        // 1. Crear tabla tbltutor
        $sql1 = "CREATE TABLE IF NOT EXISTS `tbltutor` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `TutorName` varchar(100),
            `TutorEmail` varchar(100),
            `Relationship` varchar(50),
            `PhoneNumber` varchar(20),
            `CreationDate` timestamp DEFAULT CURRENT_TIMESTAMP,
            `Status` int(1) DEFAULT 1,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
        $dbh->exec($sql1);
        $results[] = "✓ Tabla 'tbltutor' creada";
    } catch (Exception $e) {
        $results[] = "⚠ Tabla 'tbltutor': " . $e->getMessage();
    }
    
    try {
        // 2. Crear tabla tbltutor_students
        $sql2 = "CREATE TABLE IF NOT EXISTS `tbltutor_students` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `TutorId` int(11),
            `StudentId` int(11),
            `Relationship` varchar(50),
            `CreationDate` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
        $dbh->exec($sql2);
        $results[] = "✓ Tabla 'tbltutor_students' creada";
    } catch (Exception $e) {
        $results[] = "⚠ Tabla 'tbltutor_students': " . $e->getMessage();
    }
    
    try {
        // 3. Modificar tabla admin
        $sql3 = "ALTER TABLE `admin` MODIFY `role` ENUM('admin','director','teacher','tutor') NOT NULL DEFAULT 'teacher'";
        $dbh->exec($sql3);
        $results[] = "✓ Rol 'tutor' agregado";
    } catch (Exception $e) {
        $results[] = "⚠ Rol 'tutor': " . $e->getMessage();
    }
    
    try {
        // 4. Crear usuario tutor de prueba
        $check = $dbh->prepare("SELECT COUNT(*) as cnt FROM admin WHERE UserName = ?");
        $check->execute(['tutor@ipt.edu.mx']);
        $row = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($row['cnt'] == 0) {
            $pass_hash = md5('tutor123');
            $insert = $dbh->prepare("INSERT INTO `admin` (`UserName`, `Password`, `role`, `teacher_id`) VALUES (?, ?, ?, ?)");
            $insert->execute(['tutor@ipt.edu.mx', $pass_hash, 'tutor', null]);
            $results[] = "✓ Usuario 'tutor@ipt.edu.mx' creado (contraseña: tutor123)";
        } else {
            $results[] = "⚠ Usuario 'tutor@ipt.edu.mx' ya existe";
        }
    } catch (Exception $e) {
        $errors[] = "Error al crear usuario: " . $e->getMessage();
    }
    
    try {
        // 5. Listar usuarios
        $list = $dbh->query("SELECT id, UserName, role FROM admin ORDER BY role");
        $users = $list->fetchAll(PDO::FETCH_ASSOC);
        $results[] = "📌 Total usuarios: " . count($users);
    } catch (Exception $e) {
        $errors[] = "Error al listar: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migración - Sistema de Calificaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 600px; margin-top: 40px; }
        .card { border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); border: none; }
        .card-header { background: linear-gradient(135deg, #238D15 0%, #1A6B0D 100%); color: white; padding: 20px; border-radius: 12px 12px 0 0; }
        .card-body { padding: 30px; }
        .result-item { padding: 12px; margin-bottom: 10px; border-radius: 6px; border-left: 4px solid; }
        .result-success { background: #eefce0; border-color: #238D15; color: #1b5e20; }
        .result-error { background: #ffebee; border-color: #d32f2f; color: #d32f2f; }
        .btn-back { margin-top: 20px; }
        h3 { margin: 0; font-size: 20px; }
        small { opacity: 0.9; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-database"></i> Migración de Roles</h3>
                <small>Base de datos actualizada</small>
            </div>
            <div class="card-body">
                <h5 style="margin-bottom: 15px;">📊 Resultado:</h5>
                
                <?php foreach ($results as $msg): ?>
                    <div class="result-item result-success"><?php echo $msg; ?></div>
                <?php endforeach; ?>
                
                <?php foreach ($errors as $err): ?>
                    <div class="result-item result-error">❌ <?php echo $err; ?></div>
                <?php endforeach; ?>
                
                <div style="background: #e3f2fd; border-left: 4px solid #1976d2; padding: 15px; margin: 20px 0; border-radius: 6px;">
                    <h6 style="color: #1976d2; margin-bottom: 10px;"><i class="fas fa-key"></i> Credenciales de Prueba:</h6>
                    <ul style="margin: 0; font-size: 13px;">
                        <li><strong>tutor@ipt.edu.mx</strong> / <strong>tutor123</strong> → Portal de Tutores</li>
                        <li><strong>Brenda.Vazquez@ipt.edu.mx</strong> → Dashboard de Maestro</li>
                        <li><strong>admin</strong> → Dashboard Admin</li>
                    </ul>
                </div>
                
                <div class="btn-back text-center">
                    <a href="index.php" class="btn btn-success"><i class="fas fa-sign-in-alt"></i> Ir al Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
