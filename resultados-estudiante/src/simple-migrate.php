<?php
/**
 * MIGRACIÓN SIMPLE - Sistema de Calificaciones
 * URL: http://localhost/resultados-estudiante/simple-migrate.php
 */

echo "<h1>Migración de Base de Datos</h1>";
echo "<pre>";

// Conectar con mysqli (más simple)
$conn = mysqli_connect('localhost', 'root', '', 'resultados-estudiante', 3307);

if (!$conn) {
    die("ERROR: No se pudo conectar a BD: " . mysqli_connect_error());
}

echo "✓ Conexión exitosa\n";

// Ejecutar migraciones
$queries = [
    "CREATE TABLE IF NOT EXISTS `tbltutor` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `TutorName` varchar(100),
        `TutorEmail` varchar(100),
        `Relationship` varchar(50),
        `PhoneNumber` varchar(20),
        `CreationDate` timestamp DEFAULT CURRENT_TIMESTAMP,
        `Status` int(1) DEFAULT 1,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1",

    "CREATE TABLE IF NOT EXISTS `tbltutor_students` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `TutorId` int(11),
        `StudentId` int(11),
        `Relationship` varchar(50),
        `CreationDate` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1",

    "ALTER TABLE `admin` MODIFY `role` ENUM('admin','director','teacher','tutor') NOT NULL DEFAULT 'teacher'"
];

foreach ($queries as $i => $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "✓ Query " . ($i+1) . " ejecutada\n";
    } else {
        echo "⚠ Query " . ($i+1) . ": " . mysqli_error($conn) . "\n";
    }
}

// Crear usuario tutor
$result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM admin WHERE UserName = 'tutor@ipt.edu.mx'");
$row = mysqli_fetch_assoc($result);

if ($row['cnt'] == 0) {
    $pass_hash = md5('tutor123');
    $insert = "INSERT INTO `admin` (`UserName`, `Password`, `role`, `teacher_id`) VALUES ('tutor@ipt.edu.mx', '$pass_hash', 'tutor', NULL)";
    if (mysqli_query($conn, $insert)) {
        echo "✓ Usuario tutor creado\n";
    } else {
        echo "⚠ Error creando usuario: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "⚠ Usuario tutor ya existe\n";
}

// Listar usuarios
$result = mysqli_query($conn, "SELECT id, UserName, role FROM admin ORDER BY role");
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);
echo "\n📌 Usuarios en el sistema (" . count($users) . "):\n";
foreach ($users as $user) {
    echo "  - {$user['UserName']} ({$user['role']})\n";
}

mysqli_close($conn);

echo "\n✅ MIGRACIÓN COMPLETADA\n";
echo "\n🔑 Credenciales de prueba:\n";
echo "  - tutor@ipt.edu.mx / tutor123 (Tutor)\n";
echo "  - admin / (tu contraseña actual)\n";
echo "  - Brenda.Vazquez@ipt.edu.mx / (tu contraseña actual)\n";

echo "\n<a href='index.php'>Ir al Login</a>";
?>