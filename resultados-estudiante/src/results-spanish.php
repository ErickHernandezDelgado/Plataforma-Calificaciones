<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

include(__DIR__ . '/includes/config.php');

// Validación del parámetro classid
if (!isset($_GET['classid'])) {
    die("ID de clase requerido.");
}
$classId = intval($_GET['classid']); // Sanear valor

// Consulta estudiantes del grupo solicitado
$sql = "SELECT s.StudentId, s.StudentName, s.RollId, s.ClassId, c.ClassName, c.Section
        FROM tblstudents s
        JOIN tblclasses c ON c.id = s.ClassId
        WHERE s.ClassId = :classId
        ORDER BY s.StudentName";
$query = $dbh->prepare($sql);
$query->bindParam(':classId', $classId, PDO::PARAM_INT);
$query->execute();
$students = $query->fetchAll(PDO::FETCH_ASSOC);

// Validar que haya estudiantes en el grupo
if (!$students) {
    die("No se encontraron estudiantes en este grupo.");
}

ob_start(); // Iniciar buffer para capturar el HTML generado

// Recorrer estudiantes para generar boleta individual
foreach ($students as $student):
    $studentId = $student['StudentId'];

    // Consulta calificaciones por estudiante y materia para los 3 términos
    $sql = "SELECT subj.SubjectName, subj.id AS SubjectId,
                   MAX(CASE WHEN r.term = 1 THEN r.marks END) AS term1,
                   MAX(CASE WHEN r.term = 2 THEN r.marks END) AS term2,
                   MAX(CASE WHEN r.term = 3 THEN r.marks END) AS term3
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
    /* Estilos para la boleta PDF */
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #000;
    }
    .boleta {
        page-break-after: always; /* Nueva página para cada boleta */
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
    <img src="file://<?= str_replace(chr(92), '/', __DIR__) ?>/assets/images/IPT.jpeg" height="100" width="auto" alt="Logo IPT" />
    <div class="header">
        <h2>INSTITUTO PANAMERICANO DE TAMPICO</h2>
        <h3>BOLETA DE EVALUACIÓN / GRADE REPORT</h3>
    </div>

    <table class="student-info">
        <tr>
            <td><b>Nombre del Estudiante / Student Name:</b> <?= htmlentities($student['StudentName']) ?></td>
            <td><b>Grado / Grade:</b> <?= htmlentities($student['ClassName']) ?></td>
        </tr>
        <tr>
            <td><b>Sección / Section:</b> <?= htmlentities($student['Section']) ?></td>
            <td><b>Ciclo Escolar / School Year:</b> 2025</td>
        </tr>
        <tr>
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
                <th>Promedio Final<br>Final Average</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $cnt = 1;
            foreach ($results as $row):
                $term1 = is_numeric($row['term1']) ? floatval($row['term1']) : null;
                $term2 = is_numeric($row['term2']) ? floatval($row['term2']) : null;
                $term3 = is_numeric($row['term3']) ? floatval($row['term3']) : null;

                // Promedio solo si hay notas en los tres términos
                $avg = ($term1 !== null && $term2 !== null && $term3 !== null)
                    ? round(($term1 + $term2 + $term3) / 3)
                    : '';
            ?>
            <tr>
                <td><?= $cnt++ ?></td>
                <td><?= htmlentities($row['SubjectName']) ?></td>
                <td><?= $term1 !== null ? $term1 : '' ?></td>
                <td><?= $term2 !== null ? $term2 : '' ?></td>
                <td><?= $term3 !== null ? $term3 : '' ?></td>
                <td><?= $avg ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="grades">
        <thead>
            <tr>
                <th>#</th>
                <th>Periodo de Evaluación</th>
                <th>Lenguajes</th>
                <th>Saberes y<br>Pensamiento<br>Cientifico</th>
                <th>Ética,<br>Naturalez y<br>Sociedades</th>
                <th>De lo Humano y<br>lo Comunitario</th>
            </tr>
            <tr>
                <th>#</th>
                <th>I</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
            <tr>
                <th>#</th>
                <th>II</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
            <tr>
                <th>#</th>
                <th>III</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
        </thead>
    </table>

    <table class="student-info" style="margin-top: 40px;">
        <?php for ($i=0; $i<20; $i++): ?>
            <tr>
                <td><b></b></td>
                <td><b></b></td>
            </tr>
        <?php endfor; ?>
    </table>

    <table class="footer-table">
        <tr>
            <td>_________________________<br>Firma del Docente / Teacher's Signature</td>
            <td>_________________________<br>Firma del Director / Principal's Signature</td>
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

<?php endforeach; // Fin foreach estudiantes ?>

<?php
// Obtener el contenido HTML generado
$html = ob_get_clean();

// Configuración Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true); // Mejor soporte HTML5
$options->set('isRemoteEnabled', true); // Permitir imágenes remotas (logo)

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait'); // Papel A4 vertical
$dompdf->render();

// Enviar PDF al navegador para vista inline (no descarga)
$dompdf->stream("boletas_grupo_{$classId}.pdf", ["Attachment" => false]);
exit;
?>

