<?php
// Autoload para usar Dompdf (librería para generar PDFs)
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Incluir configuración y conexión a base de datos
include(__DIR__ . '/includes/config.php');

// Verificar que se reciba el parámetro classid
if (!isset($_GET['classid'])) {
    die("ID de clase requerido.");  // Finaliza si no existe classid
}
$classId = intval($_GET['classid']);  // Sanitiza el parámetro

// Consulta para obtener datos básicos de todos los estudiantes de la clase
$sql = "SELECT s.StudentId, s.Curp, s.StudentName, s.RollId, s.ClassId, c.ClassName, c.Section
        FROM tblstudents s
        JOIN tblclasses c ON c.id = s.ClassId
        WHERE s.ClassId = :classId
        ORDER BY s.StudentName";
$query = $dbh->prepare($sql);
$query->bindParam(':classId', $classId, PDO::PARAM_INT);
$query->execute();
$students = $query->fetchAll(PDO::FETCH_ASSOC);

// Si no hay estudiantes, detener el proceso
if (!$students) {
    die("No se encontraron estudiantes en este grupo.");
}

// Inicia el buffer para capturar la salida HTML que luego convertiremos a PDF
ob_start();

// Iterar por cada estudiante para generar su boleta
foreach ($students as $student):
    $studentId = $student['StudentId'];

    // Obtener las calificaciones por asignatura y periodo (hasta 5 periodos)
    // Aquí se usa un pivot con CASE para cada periodo, tomando el máximo (en caso de haber más de un registro)
    $sql = "SELECT subj.SubjectName, subj.id AS SubjectId,
                   MAX(CASE WHEN r.term = 1 THEN r.marks END) AS term1,
                   MAX(CASE WHEN r.term = 2 THEN r.marks END) AS term2,
                   MAX(CASE WHEN r.term = 3 THEN r.marks END) AS term3,
                   MAX(CASE WHEN r.term = 4 THEN r.marks END) AS term4,
                   MAX(CASE WHEN r.term = 5 THEN r.marks END) AS term5
            FROM tblresult r
            JOIN tblsubjects subj ON subj.id = r.SubjectId
            WHERE r.StudentId = :studentId
            GROUP BY subj.id, subj.SubjectName";
    $query = $dbh->prepare($sql);
    $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    
?>

<!-- Estilos para la boleta -->
<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #000;
    }
    .boleta {
        page-break-after: always; /* Salto de página para imprimir */
        padding: 10px;
    }
    .header {
        text-align: center;
    }
    .header h2, .header h3 {
        margin: 2px;
    }
    .student-info {
        margin-top: 15px;
    }
    .student-info td {
        padding: 4px 8px;
    }
    table.grades {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .grades th, .grades td {
        border: 1px solid #000;
        text-align: center;
        padding: 5px;
    }
    .scale {
        margin-top: 30px;
    }
    .footer-table {
        width: 100%;
        margin-top: 30px;
    }
    .footer-table td {
        padding-top: 30px;
        text-align: center;
    }
</style>

<div class="boleta">
    <!-- Logo con ruta relativa para portabilidad en cualquier servidor -->
    <img src="file://<?= str_replace(chr(92), '/', __DIR__) ?>/assets/images/IPT.jpeg" height="100" width="auto" alt="Logo IPT" />
    <div class="header">
        <h2>INSTITUTO PANAMERICANO DE TAMPICO</h2>
        <h3>BOLETA DE EVALUACIÓN</h3>
    </div>

    <!-- Información básica del estudiante -->
    <table class="student-info">
        <tr>
            <td><b>Nombre del (de la) Alumno: </b> <?= htmlentities($student['StudentName']) ?></td>
            <td><b>Curp: </b> <?= htmlentities($student['Curp']) ?></td>
            <td><b>Grado: </b> <?= htmlentities($student['ClassName']) ?></td>
        </tr>
        <tr>
            <td><b>Grupo: </b> <?= htmlentities($student['Section']) ?></td>
            <td><b>Ciclo Escolar: </b> 2025</td> <!-- Esto es hardcoded, podría ser dinámico -->
        </tr>
        <tr>
            <td><b>Turno:</b> Matutino</td>  <!-- Hardcoded, podrías obtenerlo de BD -->
            <td><b>CCT:</b> 28PPR0008Z</td> <!-- Hardcoded, igual que arriba -->
        </tr>
    </table>

    <!-- Tabla de calificaciones -->
    <table class="grades">
        <thead>
            <tr>
                <th>#</th>
                <th>Asignatura / Subject</th>
                <th>I Periodo<br>(1st Term)</th>
                <th>II Periodo<br>(2nd Term)</th>
                <th>III Periodo<br>(3rd Term)</th>
                <th>IV Periodo<br>(4th Term)</th>
                <th>V Periodo<br>(5th Term)</th>
                <th>Promedio Final</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $cnt = 1;
            foreach ($results as $row):
                // Validar y convertir calificaciones a float o null si no hay dato
                $term1 = is_numeric($row['term1']) ? floatval($row['term1']) : null;
                $term2 = is_numeric($row['term2']) ? floatval($row['term2']) : null;
                $term3 = is_numeric($row['term3']) ? floatval($row['term3']) : null;
                $term4 = is_numeric($row['term4']) ? floatval($row['term4']) : null;
                $term5 = is_numeric($row['term5']) ? floatval($row['term5']) : null;

                // Promedio solo si hay todas las notas de los 5 periodos
                $avg = ($term1 !== null && $term2 !== null && $term3 !== null && $term4 !== null && $term5 !== null)
                    ? round(($term1 + $term2 + $term3 + $term4 + $term5) / 5)
                    : '';
            ?>
            <tr>
                <td><?= $cnt++ ?></td>
                <td><?= htmlentities($row['SubjectName']) ?></td>
                <td><?= $term1 !== null ? $term1 : '' ?></td>
                <td><?= $term2 !== null ? $term2 : '' ?></td>
                <td><?= $term3 !== null ? $term3 : '' ?></td>
                <td><?= $term4 !== null ? $term4 : '' ?></td>
                <td><?= $term5 !== null ? $term5 : '' ?></td>
                <td><?= $avg ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Aquí hay tablas vacías para trabajo en plataforma, conducta, etc.
         No tienen contenido, falta lógica para llenarlas o son placeholders -->

    <table class="grades">
        <thead>
            <tr>
                <th>#</th>
                <th>Asignaturas</th>
                <th>1er</th>
                <th>2do</th>
                <th>3er</th>
                <th>4to</th>
                <th>5to</th>
            </tr>
            <tr>
                <th>#</th>
                <th>Trabajo en Plataforma</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
            <tr>
                <th>#</th>
                <th>Conducta</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
        </thead>
    </table>

    
    <table class="student-info">
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
        <tr>
            <td><b></b></td>
            <td><b></b></td>
        </tr>
    </table>

    <!-- Tabla vacía con varias filas sin contenido visible
         No se especifica para qué sirve ni cómo llenar -->

    <table class="student-info">
        <!-- Filas vacías para espaciado o firmas? -->
        <?php for ($i=0; $i<20; $i++): ?>
        <tr><td><b></b></td><td><b></b></td></tr>
        <?php endfor; ?>
    </table>
    
    <!-- Firmas y aclaraciones -->
    <table class="footer-table">
        <tr>
            <td>_________________________<br>Profesor</td>
        </tr>
        <tr>
            <td>_________________________<br>Firma del padre, madre o tutor</td>
            <td>Tampico, Tamaulipas<br>_________________________</td>
        </tr>
        <tr>
            <td></td>
            <td>*Este es un documento provisional sin validez oficial.*</td>
        </tr>
    </table>
</div>

<?php endforeach; // Fin foreach de estudiantes ?>

<?php
// Captura todo el contenido HTML generado
$html = ob_get_clean();

// Configuración y opciones para Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);  // Habilita soporte HTML5
$options->set('isRemoteEnabled', true);       // Permite cargar imágenes externas

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);                     // Carga el contenido HTML
$dompdf->setPaper('A4', 'portrait');           // Papel tamaño A4 vertical
$dompdf->render();                             // Renderiza el PDF

// Envia el PDF al navegador para visualizar, sin forzar descarga
$dompdf->stream("boletas_grupo_{$classId}.pdf", ["Attachment" => false]);
exit;
?>

