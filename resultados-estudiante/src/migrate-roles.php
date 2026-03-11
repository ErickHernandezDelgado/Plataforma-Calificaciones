<?php
/**
 * SCRIPT DE MIGRACIÓN - Actualizar estructura de roles
 * Este script actualiza la tabla 'admin' y agrega soporte para tutores.
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Incluir configuración
if (!file_exists('includes/config.php')) {
    die("ERROR: No se encuentra config.php. Verifica que estés en la carpeta correcta.");
}

include(__DIR__ . '/includes/config.php');

$results = [];
$errors = [];

// ===== PASO 1: Verificar conexión =====
try {
    $test = $dbh->query("SELECT 1");
    $results[] = "✓ Conexión a BD exitosa";
} catch (Exception $e) {
    die("ERROR DE CONEXIÓN: " . $e->getMessage() . 
        "<br><br>Verifica que:<br>
        1. MySQL esté ejecutándose en puerto 3307<br>
        2. La BD 'resultados-estudiante' exista<br>
        3. Usuario: root, Contraseña: vacía");
}
    
    // ===== PASO 2: Crear tabla de tutores =====
    $sql_tutores = "
    CREATE TABLE IF NOT EXISTS `tbltutor` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `TutorName` varchar(100) DEFAULT NULL,
      `TutorEmail` varchar(100) DEFAULT NULL,
      `Relationship` varchar(50) DEFAULT NULL,
      `PhoneNumber` varchar(20) DEFAULT NULL,
      `CreationDate` timestamp DEFAULT CURRENT_TIMESTAMP,
      `Status` int(1) DEFAULT 1,
      PRIMARY KEY (`id`),
      UNIQUE KEY `email_unique` (`TutorEmail`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
    ";
    
    try {
        $dbh->exec($sql_tutores);
        $results[] = "✓ Tabla 'tbltutor' creada/verificada";
    } catch (Exception $e) {
        $results[] = "⚠ Tabla 'tbltutor' ya existe: " . $e->getMessage();
    }
    
    // ===== PASO 3: Crear tabla vinculante tutor-estudiantes =====
    $sql_tutor_students = "
    CREATE TABLE IF NOT EXISTS `tbltutor_students` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `TutorId` int(11) NOT NULL,
      `StudentId` int(11) NOT NULL,
      `Relationship` varchar(50) DEFAULT NULL,
      `CreationDate` timestamp DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `tutor_student` (`TutorId`, `StudentId`),
      FOREIGN KEY (`TutorId`) REFERENCES `tbltutor`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`StudentId`) REFERENCES `tblstudents`(`StudentId`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
    ";
    
    try {
        $dbh->exec($sql_tutor_students);
        $results[] = "✓ Tabla 'tbltutor_students' creada/verificada";
    } catch (Exception $e) {
        $results[] = "⚠ Tabla 'tbltutor_students' ya existe: " . $e->getMessage();
    }
    
    // ===== PASO 4: Modificar tabla admin para soportar 'tutor' y 'director' =====
    // Primero verificar si la columna 'role' ya tiene el tipo correcto
    $sql_check = "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_NAME='admin' AND COLUMN_NAME='role' AND TABLE_SCHEMA=DATABASE()";
    $query = $dbh->prepare($sql_check);
    $query->execute();
    $column_info = $query->fetch(PDO::FETCH_OBJ);
    
    if ($column_info) {
        $current_type = $column_info->COLUMN_TYPE;
        
        // Si no incluye 'tutor', actualizar
        if (strpos($current_type, 'tutor') === false) {
            $sql_alter = "ALTER TABLE `admin` 
                         MODIFY `role` ENUM('admin','director','teacher','tutor') NOT NULL DEFAULT 'teacher'";
            try {
                $dbh->exec($sql_alter);
                $results[] = "✓ Columna 'role' actualizada para soportar 'tutor' y 'director'";
            } catch (Exception $e) {
                $errors[] = "✗ Error al modificar 'role': " . $e->getMessage();
            }
        } else {
            $results[] = "✓ Columna 'role' ya soporta todos los tipos";
        }
    }
    
    // ===== PASO 5: Crear un usuario tutor de prueba =====
    // Contraseña: "tutor123" (MD5 hash)
    $tutor_password = md5('tutor123');
    
    $sql_check_tutor = "SELECT * FROM admin WHERE UserName = 'tutor@ipt.edu.mx'";
    $query = $dbh->prepare($sql_check_tutor);
    $query->execute();
    
    if ($query->rowCount() === 0) {
        $sql_insert_tutor = "INSERT INTO `admin` (`UserName`, `Password`, `role`, `teacher_id`) 
                             VALUES (:username, :password, 'tutor', NULL)";
        $stmt = $dbh->prepare($sql_insert_tutor);
        $stmt->bindParam(':username', $username = 'tutor@ipt.edu.mx', PDO::PARAM_STR);
        $stmt->bindParam(':password', $tutor_password, PDO::PARAM_STR);
        
        try {
            $stmt->execute();
            $results[] = "✓ Usuario tutor de prueba creado: tutor@ipt.edu.mx / tutor123";
        } catch (Exception $e) {
            $errors[] = "✗ Error al crear usuario tutor: " . $e->getMessage();
        }
    } else {
        $results[] = "⚠ Usuario tutor ya existe";
    }
    
    // ===== PASO 6: Listar usuarios actuales en la tabla admin =====
    $sql_list = "SELECT id, UserName, role FROM admin ORDER BY role";
    $query = $dbh->prepare($sql_list);
    $query->execute();
    $users = $query->fetchAll(PDO::FETCH_OBJ);
    
    $results[] = "📋 **Usuarios actuales en el sistema:**";
    foreach ($users as $user) {
        $role_icon = '';
        switch($user->role) {
            case 'admin': $role_icon = '🔐'; break;
            case 'director': $role_icon = '👔'; break;
            case 'teacher': $role_icon = '👨‍🏫'; break;
            case 'tutor': $role_icon = '👨‍👩‍👧'; break;
        }
        $results[] = "  $role_icon ID:{$user->id} | Usuario: {$user->UserName} | Rol: {$user->role}";
    }
    
} catch (Exception $e) {
    $errors[] = "✗ Error general: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migración de Roles - Sistema de Calificaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 700px;
            margin-top: 30px;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        .card-header {
            background: linear-gradient(135deg, #238D15 0%, #1A6B0D 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            padding: 20px;
        }
        .card-body {
            padding: 30px;
        }
        .result-item {
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 6px;
            background-color: #f0f0f0;
            border-left: 4px solid #238D15;
        }
        .result-item.error {
            background-color: #ffebee;
            border-left-color: #d32f2f;
            color: #d32f2f;
        }
        .result-item.success {
            background-color: #eefce0;
            border-left-color: #238D15;
            color: #1b5e20;
        }
        .result-item strong {
            font-weight: 600;
        }
        .info-box {
            background-color: #e3f2fd;
            border-left: 4px solid #1976d2;
            padding: 15px;
            margin: 20px 0;
            border-radius: 6px;
            font-size: 13px;
        }
        .info-box h5 {
            margin-top: 0;
            color: #1976d2;
        }
        .btn-back {
            margin-top: 20px;
        }
        .user-table {
            font-size: 13px;
            margin-top: 15px;
        }
        .user-table tr {
            border-bottom: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 style="margin: 0;"><i class="fas fa-database"></i> Migración de Estructura de Roles</h3>
                <small>Actualizar base de datos - Sistema de Calificaciones</small>
            </div>
            
            <div class="card-body">
                <!-- Información de migración -->
                <div class="info-box">
                    <h5><i class="fas fa-info-circle"></i> Cambios realizados:</h5>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>✅ Tabla <strong>tbltutor</strong> creada para gestionar tutores/padres</li>
                        <li>✅ Tabla <strong>tbltutor_students</strong> para vincular tutores con estudiantes</li>
                        <li>✅ Rol <strong>'tutor'</strong> agregado a la tabla admin</li>
                        <li>✅ Usuario de prueba creado: <strong>tutor@ipt.edu.mx</strong></li>
                    </ul>
                </div>

                <!-- Resultados de la migración -->
                <h5 style="margin-top: 20px; margin-bottom: 15px;">📊 Resultados de la Ejecución:</h5>
                
                <?php if (!empty($results)): ?>
                    <?php foreach ($results as $result): ?>
                        <div class="result-item success">
                            <?php echo htmlspecialchars($result); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <h5 style="color: #d32f2f; margin-top: 20px;">⚠️ Errores encontrados:</h5>
                    <?php foreach ($errors as $error): ?>
                        <div class="result-item error">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Información de generación -->
                <div class="info-box" style="margin-top: 25px;">
                    <h5><i class="fas fa-lock"></i> Estructura de Roles (Opción A):</h5>
                    <table class="user-table" style="width: 100%;">
                        <tr style="font-weight: bold;">
                            <td>Rol</td>
                            <td>Usuario ejemplo</td>
                            <td>Acceso</td>
                        </tr>
                        <tr>
                            <td><strong>🔐 admin</strong></td>
                            <td>admin</td>
                            <td>Gestión total del sistema</td>
                        </tr>
                        <tr>
                            <td><strong>👨‍🏫 teacher</strong></td>
                            <td>Brenda.Vazquez@ipt.edu.mx</td>
                            <td>Poner notas + Dar de alta estudiantes</td>
                        </tr>
                        <tr>
                            <td><strong>👨‍👩‍👧 tutor</strong></td>
                            <td>tutor@ipt.edu.mx</td>
                            <td>Ver solo calificaciones de sus hijos</td>
                        </tr>
                    </table>
                </div>

                <!-- Contraseñas de prueba -->
                <div class="info-box">
                    <h5><i class="fas fa-key"></i> Credenciales de Prueba:</h5>
                    <ul style="margin: 0; padding-left: 20px; font-size: 12px;">
                        <li><strong>tutor@ipt.edu.mx</strong> / <strong>tutor123</strong> (Tutor/Padre)</li>
                        <li><strong>Brenda.Vazquez@ipt.edu.mx</strong> / Verificar contraseña actual (Maestro)</li>
                        <li><strong>admin</strong> / Verificar contraseña actual (Admin)</li>
                    </ul>
                </div>

                <!-- Botones de acción -->
                <div class="btn-back text-center">
                    <a href="index.php" class="btn btn-success">
                        <i class="fas fa-sign-in-alt"></i> Ir al Login
                    </a>
                    <a href="/" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Ir a Inicio
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

