<?php
require 'vendor/autoload.php'; // Autoload para Dompdf y otras librerías
use Dompdf\Dompdf;
use Dompdf\Options;

include(__DIR__ . '/includes/config.php'); // Configuración y conexión a base de datos

// Validar que se reciba el parámetro classid por GET
if (!isset($_GET['classid'])) {
    die("ID de clase requerido.");
}
$classId = intval($_GET['classid']); // Convertir a entero para evitar inyección

// Consultar todos los estudiantes de la clase seleccionada
$sql = "SELECT s.StudentId, s.StudentName, s.RollId, s.ClassId, c.ClassName, c.Section
        FROM tblstudents s
        JOIN tblclasses c ON c.id = s.ClassId
        WHERE s.ClassId = :classId
        ORDER BY s.StudentName";
$query = $dbh->prepare($sql);
$query->bindParam(':classId', $classId, PDO::PARAM_INT);
$query->execute();
$students = $query->fetchAll(PDO::FETCH_ASSOC);

// Validar si hay estudiantes en el grupo
if (!$students) {
    die("No se encontraron estudiantes en este grupo.");
}

ob_start(); // Iniciar buffer de salida para capturar el HTML generado

// Recorrer cada estudiante para generar su boleta
foreach ($students as $student):
    $studentId = $student['StudentId'];

    // Obtener calificaciones del estudiante para los tres términos (periodos)
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
    /* Estilos generales para la boleta */
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #000;
    }
    .boleta {
        page-break-after: always; /* Cada boleta inicia en página nueva */
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
    <!-- Logo del instituto; ruta relativa para portabilidad en local, servidor Linux, etc. -->
    <img src="file://<?= str_replace(chr(92), '/', __DIR__) ?>/assets/images/IPT.jpeg" height="100" width="auto" alt="Logo IPT" />
    
    <div class="header">
        <h2>INSTITUTO PANAMERICANO DE TAMPICO</h2>
        <h3>BOLETA DE EVALUACIÓN</h3>
    </div>

    <table class="student-info">
        <tr>
            <td><b>Nombre del Estudiante: </b> <?= htmlentities($student['StudentName']) ?></td>
            <td><b>Grado: </b> <?= htmlentities($student['ClassName']) ?></td>
        </tr>
        <tr>
            <td><b>Grupo: </b> <?= htmlentities($student['Section']) ?></td>
            <td><b>Ciclo Escolar: </b> 2025</td> <!-- Puedes hacerlo dinámico si tienes el dato -->
        </tr>
        <tr>
            <td><b>Turno:</b> Matutino</td> <!-- Igual, puedes hacerlo dinámico -->
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
                // Convertir las notas a float si son numéricas, sino null
                $term1 = is_numeric($row['term1']) ? floatval($row['term1']) : null;
                $term2 = is_numeric($row['term2']) ? floatval($row['term2']) : null;
                $term3 = is_numeric($row['term3']) ? floatval($row['term3']) : null;

                // Calcular promedio solo si tiene las tres notas
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

    <!-- Tabla vacía para otras evaluaciones o áreas, se puede completar según necesidad -->
    <table class="grades" style="margin-top: 30px;">
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

    <!-- Espacios vacíos para formato o firmas (puedes ajustar o eliminar si no se requiere) -->
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
            <td>_________________________<br>Lic. Myrna Estela Moreno Murillo</td>
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

<?php endforeach; // Fin ciclo estudiantes ?>

<?php
// Captura el contenido HTML generado en el buffer
$html = ob_get_clean();

// Configuración de Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true); // Permite mejor manejo de HTML5
$options->set('isRemoteEnabled', true); // Permite cargar imágenes remotas (logo)

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait'); // Papel tamaño A4, orientación vertical
$dompdf->render();

// Enviar PDF generado al navegador para mostrarlo inline (no descargar directamente)
$dompdf->stream("boletas_grupo_{$classId}.pdf", ["Attachment" => false]);
exit;
?>

