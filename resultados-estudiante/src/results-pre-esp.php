<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

include(__DIR__ . '/includes/config.php');

// Validar que se reciba el ID de clase
if (!isset($_GET['classid'])) {
    die("ID de clase requerido.");
}
$classId = intval($_GET['classid']);

// Obtener todos los estudiantes del grupo, ordenados por nombre
$sql = "SELECT s.StudentId, s.Curp, s.StudentName, s.RollId, s.ClassId, c.ClassName, c.Section
        FROM tblstudents s
        JOIN tblclasses c ON c.id = s.ClassId
        WHERE s.ClassId = :classId
        ORDER BY s.StudentName";
$query = $dbh->prepare($sql);
$query->bindParam(':classId', $classId, PDO::PARAM_INT);
$query->execute();
$students = $query->fetchAll(PDO::FETCH_ASSOC);

if (!$students) {
    die("No se encontraron estudiantes en este grupo.");
}

ob_start(); // Inicia buffer de salida para capturar todo el HTML generado

foreach ($students as $student):
    $studentId = $student['StudentId'];

    // Obtener calificaciones por materia y término para el estudiante actual
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

<style>
    /* Estilos para la boleta */
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #000;
    }
    .boleta {
        page-break-after: always; /* Nueva página en impresión para cada boleta */
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
    <!-- Logo, puede ser ruta local o remota. isRemoteEnabled debe estar activado -->
    <img src="https://evalua-ipt/spacecare.center/assets/images/favicon.png" height="100" width="auto" alt="Logo IPT" />
    <div class="header">
        <h2>INSTITUTO PANAMERICANO DE TAMPICO</h2>
        <h3>BOLETA DE EVALUACIÓN</h3>
    </div>

    <table class="student-info">
        <tr>
            <td><b>Nombre del (de la) Alumno: </b> <?= htmlentities($student['StudentName']) ?></td>
            <td><b>Curp: </b> <?= htmlentities($student['Curp']) ?></td>
            <td><b>Grado: </b> <?= htmlentities($student['ClassName']) ?></td>
        </tr>
        <tr>
            <td><b>Grupo: </b> <?= htmlentities($student['Section']) ?></td>
            <!-- TODO: Hacer dinámico el ciclo escolar si cambia -->
            <td><b>Ciclo Escolar: </b> 2025</td>
        </tr>
        <tr>
            <!-- TODO: Hacer dinámico el turno y CCT -->
            <td><b>Turno:</b> Matutino</td>
            <td><b>CCT:</b> 28PPR0008Z</td>
        </tr>
    </table>

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
                $term1 = is_numeric($row['term1']) ? floatval($row['term1']) : null;
                $term2 = is_numeric($row['term2']) ? floatval($row['term2']) : null;
                $term3 = is_numeric($row['term3']) ? floatval($row['term3']) : null;
                $term4 = is_numeric($row['term4']) ? floatval($row['term4']) : null;
                $term5 = is_numeric($row['term5']) ? floatval($row['term5']) : null;

                // Mejora: Calcular promedio solo con los términos que tiene calificación
                $terms = array_filter([$term1, $term2, $term3, $term4, $term5], function($v) { return $v !== null; });
                $avg = count($terms) > 0 ? round(array_sum($terms) / count($terms)) : '';
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

    <!--
    Tablas adicionales con Trabajo en Plataforma y Conducta,
    actualmente vacías. Agrega lógica para llenarlas si tienes datos.
    -->
    <table class="grades" style="margin-top: 30px;">
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
                <th></th>
            </tr>
            <tr>
                <th>#</th>
                <th>Conducta</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
        </thead>
    </table>

    <!-- Espacios vacíos para formato o firmas -->
    <table class="student-info" style="margin-top: 40px;">
        <?php
        // 20 filas vacías para formato, puedes ajustar o eliminar si no se requieren
        for ($i=0; $i<20; $i++): ?>
            <tr>
                <td><b></b></td>
                <td><b></b></td>
            </tr>
        <?php endfor; ?>
    </table>
    
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
            <td><i>*Este es un documento provisional sin validez oficial.*</i></td>
        </tr>
    </table>
</div>

<?php endforeach; // Fin del ciclo foreach estudiantes ?>

<?php
$html = ob_get_clean(); // Obtiene todo el HTML generado

// Configuración de Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true); // Para buen manejo HTML5
$options->set('isRemoteEnabled', true);      // Para permitir cargar imágenes remotas

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait'); // Tamaño carta, orientación vertical
$dompdf->render();

// Mostrar el PDF en navegador sin descargar automáticamente
$dompdf->stream("boletas_grupo_{$classId}.pdf", ["Attachment" => false]);
exit;
?>

