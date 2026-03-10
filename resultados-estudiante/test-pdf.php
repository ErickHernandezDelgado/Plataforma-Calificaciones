<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;

// Crear instancia de Dompdf
$dompdf = new Dompdf();

// Cargar contenido HTML para el PDF
$dompdf->loadHtml('<h1>Hola mundo, este es un PDF generado con Dompdf</h1>');

// Establecer tamaño y orientación de la página
$dompdf->setPaper('A4', 'portrait');

// Renderizar el PDF
$dompdf->render();

// Enviar PDF al navegador sin forzar descarga (se abre en navegador)
$dompdf->stream("archivo.pdf", ["Attachment" => false]);
?>
