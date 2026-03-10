<?php
// Incluye la configuración y conexión a la base de datos
include('includes/config.php');

// Verifica que se haya recibido el parámetro 'classid' por GET
if (!isset($_GET['classid'])) {
    die("Falta el ID de la clase (classid).");  // Termina el script si no hay classid
}

// Convierte el parámetro classid a entero para seguridad y uso en consulta
$classId = intval($_GET['classid']);

// Consulta para obtener todos los estudiantes de la clase indicada, ordenados alfabéticamente
$sql = "SELECT * FROM tblstudents WHERE ClassId = :classId ORDER BY StudentName ASC";
$query = $dbh->prepare($sql);
$query->bindParam(':classId', $classId, PDO::PARAM_INT);
$query->execute();
$students = $query->fetchAll(PDO::FETCH_ASSOC);

// Si no hay estudiantes, detiene el script mostrando un mensaje
if (!$students) {
    die("No se encontraron alumnos para esta clase.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boletas del Grupo</title>
    <style>
        /* Estilos para imprimir:
           - Oculta elementos con clase 'no-print' (ej. botón)
           - Cada boleta en página nueva al imprimir */
        @media print {
            .no-print { display: none; }
            .boleta { page-break-after: always; }
        }
        /* Margen inferior para separar boletas visualmente */
        .boleta { margin-bottom: 60px; }
    </style>
</head>
<body>
    <!-- Botón para imprimir todas las boletas, no aparece al imprimir -->
    <div class="no-print" style="margin: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px;">Imprimir todas las boletas</button>
    </div>

    <!-- Recorre cada estudiante para generar sus boletas -->
    <?php foreach ($students as $student): ?>
        <div class="boleta">
            <?php
                // Establece $_GET['id'] para que la boleta pueda identificar al estudiante
                $_GET['id'] = $student['StudentId'];

                // Incluye la boleta en español (debe estar preparada para recibir $_GET['id'])
                include('boleta_espanol.php');
            ?>
        </div>

        <div class="boleta">
            <?php
                // De nuevo define $_GET['id'] para el siguiente include
                $_GET['id'] = $student['StudentId'];

                // Incluye la boleta en inglés
                include('boleta_ingles.php');
            ?>
        </div>
    <?php endforeach; ?>
</body>
</html>
