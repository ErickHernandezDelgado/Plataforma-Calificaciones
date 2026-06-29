<?php
/**
 * generate-report-cards.php
 * Genera las boletas de TODO un grupo (uso admin).
 *
 * Por cada alumno selecciona la plantilla de boleta según su nivel educativo
 * (educationLevel) y concatena el resultado en un solo PDF. La plantilla por nivel
 * es la ÚNICA fuente de verdad del formato y la comparte con el portal del tutor
 * (generate-student-pdf.php).
 */

require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

require_once(__DIR__ . '/includes/config.php');
session_start();

// Solo admin (esta vía imprime el grupo completo).
if (!isset($_SESSION['alogin']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['classid'])) {
    die("Falta el ID del grupo.");
}
$classId = intval($_GET['classid']);

// Datos del grupo (para conocer el nivel y elegir plantilla).
$cls = $dbh->prepare("SELECT id, ClassName, Section, educationLevel FROM tblclasses WHERE id = :cid");
$cls->execute([':cid' => $classId]);
$class = $cls->fetch(PDO::FETCH_ASSOC);
if (!$class) {
    die("Grupo no encontrado.");
}

// Alumnos del grupo.
$stmt = $dbh->prepare("SELECT StudentId FROM tblstudents WHERE ClassId = :cid ORDER BY StudentName ASC");
$stmt->execute([':cid' => $classId]);
$students = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (!$students) {
    die("No hay alumnos en este grupo.");
}

/**
 * Mapa nivel -> archivo de plantilla + función que genera el HTML de un alumno.
 * Al añadir kinder/primaria/secundaria, registrar aquí su plantilla.
 */
$templates = [
    'maternal'    => ['file' => __DIR__ . '/boleta-maternal.php', 'fn' => 'render_boleta_maternal'],
    'kinder'      => ['file' => __DIR__ . '/boleta-kinder.php',   'fn' => 'render_boleta_kinder'],
    'preprimaria' => ['file' => __DIR__ . '/boleta-kinder.php',   'fn' => 'render_boleta_kinder'], // mismo formato que kinder
    'primaria'    => ['file' => __DIR__ . '/boleta-primaria.php', 'fn' => 'render_boleta_primaria'],
    'secundaria'  => ['file' => __DIR__ . '/boleta-primaria.php', 'fn' => 'render_boleta_primaria'], // misma estructura (asignaturas+rubros)
];

$level = $class['educationLevel'];
$html = '';

if (isset($templates[$level])) {
    require_once($templates[$level]['file']);
    $renderFn = $templates[$level]['fn'];

    $first = true;
    foreach ($students as $sid) {
        if (!$first) {
            $html .= '<div style="page-break-after: always;"></div>';
        }
        $html .= $renderFn($dbh, (int) $sid);
        $first = false;
    }
} else {
    // Nivel aún sin plantilla oficial implementada.
    $html = '<div style="font-family: Arial, sans-serif; padding: 40px;">'
          . '<h2>Boleta no disponible</h2>'
          . '<p>El formato de boleta para el nivel <b>' . htmlentities($level) . '</b> aún no está configurado.</p>'
          . '</div>';
}

// Render PDF.
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $class['ClassName'] . '_' . $class['Section']);
$dompdf->stream("Boletas_{$safeName}.pdf", ['Attachment' => false]);
exit;
