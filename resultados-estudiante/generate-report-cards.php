<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

require_once('includes/config.php');

if (!isset($_GET['classid'])) {
    die("Falta el ID del grupo.");
}

$classId = intval($_GET['classid']);

// Obtener alumnos
$sql = "SELECT StudentId FROM tblstudents WHERE ClassId = :classId ORDER BY StudentName ASC";
$query = $dbh->prepare($sql);
$query->bindParam(':classId', $classId, PDO::PARAM_INT);
$query->execute();
$students = $query->fetchAll(PDO::FETCH_ASSOC);
if (!$students) die("No hay alumnos en este grupo.");

// Preparar Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);

$html = '';
ob_start(); // para capturar el HTML generado

foreach ($students as $student) {
    $_GET['id'] = $student['StudentId'];

    // Boleta en español
    include('results-spanish.php');
    $html .= ob_get_clean();
    ob_start();

    // Boleta secundaria (results-sec.php)
    include('results-sec.php');
    $html .= ob_get_clean();
    ob_start();

    // Boleta en inglés
    include('results-english.php');
    $html .= ob_get_clean();
    ob_start();

    // Boleta pre-español
    include('results-pre-esp.php');
    $html .= ob_get_clean();
    ob_start();

    // Boleta pre-inglés
    include('results-pre-en.php');
    $html .= ob_get_clean();

    $html .= '<div style="page-break-after: always;"></div>';
}


$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Boletas_Grupo_$classId.pdf", ['Attachment' => false]);
exit;
