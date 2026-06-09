<?php

/**
 * portal-tutor.php - Panel exclusivo para Tutores (Padres de Familia)
 * 
 * Permite al padre/tutor:
 * - Ver información de su hijo/a
 * - Ver calificaciones actuales
 * - Descargar boleta de calificaciones en PDF
 */

session_start();
error_reporting(0);
include(__DIR__ . '/includes/config.php');

// Verificar que sea tutor
if (!isset($_SESSION['alogin']) || $_SESSION['role'] !== 'tutor') {
    header("Location: index.php");
    exit;
}

// Obtener ID del tutor
$tutor_id = $_SESSION['id'] ?? null;

if (!$tutor_id) {
    die("Error: No se encontró ID de tutor");
}

// Obtener estudiante(s) del tutor
$sql = "SELECT 
            st.StudentId,
            s.StudentName,
            s.RollId,
            c.ClassName,
            c.Section,
            c.id as ClassId,
            st.RelationshipType
        FROM student_tutor st
        JOIN tblstudents s ON st.StudentId = s.StudentId
        JOIN tblclasses c ON s.ClassId = c.id
        WHERE st.TutorId = :tutor_id AND st.PrimaryContact = 1
        ORDER BY s.StudentName ASC";
$query = $dbh->prepare($sql);
$query->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
$query->execute();
$students = $query->fetchAll(PDO::FETCH_OBJ);

$selected_student_id = $_GET['student_id'] ?? ($students[0]->StudentId ?? null);
$selected_student = null;
$student_grades_spanish = [];
$student_grades_english = [];
$student_notices = [];
$pending_notices_count = 0;

if ($selected_student_id) {
    // Verificar que el tutor tenga permisos sobre este estudiante
    $sql = "SELECT * FROM student_tutor WHERE StudentId = :sid AND TutorId = :tid";
    $check = $dbh->prepare($sql);
    $check->bindParam(':sid', $selected_student_id, PDO::PARAM_INT);
    $check->bindParam(':tid', $tutor_id, PDO::PARAM_INT);
    $check->execute();

    if ($check->rowCount() > 0) {
        // Obtener datos del estudiante seleccionado
        $sql = "SELECT s.*, c.ClassName, c.Section FROM tblstudents s 
                JOIN tblclasses c ON s.ClassId = c.id 
                WHERE s.StudentId = :sid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':sid', $selected_student_id, PDO::PARAM_INT);
        $query->execute();
        $selected_student = $query->fetch(PDO::FETCH_OBJ);

        // Obtener calificaciones de ESPAÑOL del estudiante
        $sql = "SELECT 
                    subj.SubjectName,
                    subj.id AS SubjectId,
                    MAX(CASE WHEN r.term = 1 THEN r.marks END) AS term1,
                    MAX(CASE WHEN r.term = 2 THEN r.marks END) AS term2,
                    MAX(CASE WHEN r.term = 3 THEN r.marks END) AS term3
                FROM tblresult r
                JOIN tblsubjects subj ON r.SubjectId = subj.id
                WHERE r.StudentId = :sid AND subj.Language = 'es'
                GROUP BY subj.id, subj.SubjectName
                ORDER BY subj.SubjectName ASC";
        $query = $dbh->prepare($sql);
        $query->bindParam(':sid', $selected_student_id, PDO::PARAM_INT);
        $query->execute();
        $student_grades_spanish = $query->fetchAll(PDO::FETCH_OBJ);

        // Obtener calificaciones de INGLÉS del estudiante
        $sql = "SELECT 
                    subj.SubjectName,
                    subj.id AS SubjectId,
                    MAX(CASE WHEN r.term = 1 THEN r.marks END) AS term1,
                    MAX(CASE WHEN r.term = 2 THEN r.marks END) AS term2,
                    MAX(CASE WHEN r.term = 3 THEN r.marks END) AS term3
                FROM tblresult r
                JOIN tblsubjects subj ON r.SubjectId = subj.id
                WHERE r.StudentId = :sid AND subj.Language = 'en'
                GROUP BY subj.id, subj.SubjectName
                ORDER BY subj.SubjectName ASC";
        $query = $dbh->prepare($sql);
        $query->bindParam(':sid', $selected_student_id, PDO::PARAM_INT);
        $query->execute();
        $student_grades_english = $query->fetchAll(PDO::FETCH_OBJ);

        // Obtener notificaciones/anuncios del estudiante
        $sql = "SELECT 
                    ns.id as notice_student_id,
                    tn.id as notice_id,
                    tn.noticeTitle,
                    tn.noticeDetails,
                    tn.postingDate,
                    tn.audience_type,
                    tc.ClassName,
                    tc.Section,
                    ns.is_viewed,
                    ns.viewed_date
                FROM notice_student ns
                JOIN tblnotice tn ON ns.notice_id = tn.id
                LEFT JOIN tblclasses tc ON tn.class_id = tc.id
                WHERE ns.student_id = :student_id AND tn.is_active = 1
                ORDER BY tn.postingDate DESC";
        $query_notices = $dbh->prepare($sql);
        $query_notices->bindParam(':student_id', $selected_student_id, PDO::PARAM_INT);
        $query_notices->execute();
        $student_notices = $query_notices->fetchAll(PDO::FETCH_OBJ);

        // Contar notificaciones pendientes
        $pending_notices_count = 0;
        foreach ($student_notices as $notice) {
            if ($notice->is_viewed == 0) {
                $pending_notices_count++;
            }
        }
    }
}

?>



<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Tutores - Instituto Panamericano</title>

    <!-- Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            /* Verdes Institucionales */
            --color-primario: #0F9B3A;
            /* Color 1: Verde brillante */
            --color-secundario: #065D21;
            /* Color 2: Verde oscuro */

            /* Identidad Visual / Fondos Oscuros */
            --color-acento: #32344B;
            /* Color 3: Azul oscuro/Grisáceo */

            /* Variantes de Blanco / Fondos Claros */
            --blanco-fondo: #F0F7F3;
            /* Blanco 1: Fondo general */
            --blanco-suave: #E4F6EA;
            /* Blanco 2: Contenedores/Inputs */

            /* Opcionales útiles */
            --texto-blanco: #FFFFFF;
            --sombra-suave: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            overflow-x: hidden;
        }

        body,
        .main-container,
        .content-section {
            font-family: 'Poppins', sans-serif;
            background-color: var(--blanco-fondo);
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            width: 100%;
        }

        .hero-section {
            width: 100%;
            min-height: 100vh;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;

            background:
                linear-gradient(rgba(255, 255, 255, 0.55),
                    rgba(255, 255, 255, 0.55)),
                url('assets/images/pexels-pixabay-2166.jpg');

            background-size: cover;
            background-position: center;
            overflow: hidden;
        }

        /* LOGO */

        .logo-container {
            padding: 24px 20px 0 20px;
            z-index: 2;
        }

        .school-logo {
            width: clamp(120px, 35vw, 170px);
            height: auto;
        }

        /* USER CARD */

        .user-card {
            position: absolute;
            top: 20px;
            right: 20px;

            width: 145px;

            background-color: var(--color-primario);

            border-radius: 0 0 22px 22px;

            padding: 14px 10px;

            display: flex;
            flex-direction: column;
            align-items: center;

            color: white;

            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.18);

            z-index: 3;
        }

        .user-carousel {
            width: 100%;

            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-bottom: 8px;
        }

        .nav-arrow {
            color: rgba(255, 255, 255, 0.75);
            font-size: 18px;
            text-decoration: none;
            transition: 0.2s ease;
        }

        .nav-arrow:hover {
            color: white;
            transform: scale(1.1);
        }

        .user-icon {
            font-size: 42px;
            color: white;
        }

        .user-name {
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            line-height: 1.3;
        }

        .logout-link {
            margin-top: 6px;

            font-size: 10px;
            color: var(--blanco-suave);

            text-decoration: underline;
        }

        /* WELCOME BAR */

        .welcome-bar {
            width: 100%;

            background-color: var(--color-primario);

            padding: 22px 24px 28px 24px;

            color: white;

            text-align: center;

            z-index: 2;
        }

        .welcome-bar h1 {
            margin: 0;

            font-size: clamp(24px, 7vw, 34px);

            font-weight: 800;

            letter-spacing: 1px;
        }

        .welcome-bar p {
            margin-top: 8px;

            font-size: clamp(13px, 3.5vw, 16px);

            line-height: 1.5;

            opacity: 0.95;
        }


        /* Contenedor Principal de Contenido */
        .student-info-section {
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 50px 71px 50px 71px;
        }

        /* Tarjeta de Datos del Alumno */
        .student-info-box {
            background-color: var(--blanco-suave);
            /* Tu Blanco 2: #E4F6EA */
            display: flex;
            flex-direction: column;
            align-self: center;
            border-radius: 20px;
            /* Radio de esquina según Figma */
            padding: 30px 86px 30px 86px;
            /* Margen interior exacto de tu captura */
            box-shadow: var(--sombra-suave);
        }

        .student-info-box h3 {
            color: var(--color-acento);
            /* Tu Color 3: #32344B */
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 30px;
            text-transform: uppercase;
        }

        /* Grid de Información */
        .info-display-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            /* Columna fija para etiquetas */
            row-gap: 20px;
        }

        .info-row {
            display: contents;
            /* Permite que los hijos se alineen al grid principal */
        }

        .info-label {
            color: var(--color-acento);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .info-value {
            color: var(--color-acento);
            font-weight: 400;
            font-size: 1.1rem;
            text-align: left;
            padding-left: 100px;
            /* Espacio visual entre etiqueta y valor */
        }





        /* Contenedor de la tabla */
        /* --- Ajustes para la sección de Calificaciones --- */

        .grades-section {
            width: 100%;
            max-width: 1000px;
            /* Ajusta según el ancho de tu diseño */
            padding: 0 20px;
        }

        .grades-section h3 {
            color: var(--color-acento);
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 25px;
            position: relative;
            padding-left: 15px;
            margin-top: 50px;
        }

        /* El indicador verde al lado del título CALIFICACIÓN */
        .grades-section h3::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 100%;
            background-color: var(--color-primario);
            border-radius: 10px;
        }

        .grades-table-wrapper {
            width: 100%;
            overflow-x: auto;
            /* Por si hay muchas columnas en móvil */
        }

        .grades-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 15px;
            /* Espacio vertical entre cada 'píldora' */
        }

        /* Cabeceras de la tabla */
        .grades-table thead th {
            color: var(--color-acento);
            opacity: 0.7;
            font-weight: 700;
            font-size: 0.9rem;
            padding: 10px;
            text-transform: uppercase;
        }

        /* Filas de materias */
        .grade-item-row td {
            padding: 20px;
            color: white;
            font-size: 1.1rem;
        }

        /* Redondear solo las esquinas exteriores de la fila */
        .grade-item-row td:first-child {
            border-radius: 20px 0 0 20px;
            padding-left: 40px;
            /* Más espacio para el nombre de la materia */
            width: 40%;
        }

        .grade-item-row td:last-child {
            border-radius: 0 20px 20px 0;
        }

        /* Colores alternados exactos */
        .grade-item-row:nth-child(odd) td {
            background-color: var(--color-primario);
            /* Verde */
        }

        .grade-item-row:nth-child(even) td {
            background-color: var(--color-acento);
            /* Gris/Azul Oscuro */
        }

        /* Estilo para el botón de PDF (centrado) */
        .action-buttons {
            padding-right: 50px;
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }

        .btn-primary {
            background-color: var(--color-acento);
            color: white;
            padding: 15px 40px;
            border-radius: 8px;
            /* Botón ovalado */
            text-decoration: none;
            font-weight: 700;
            text-transform: uppercase;
            box-shadow: 0 10px 20px rgba(15, 155, 58, 0.2);
            transition: transform 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
        }

        .promedio-container {
            display: flex;
            align-items: center;
        }

        .unidades {
            padding: 10px;

        }

        .contenedor-unidades {
            display: flex;
            flex-direction: row;
            justify-content: center;
        }

        .footer {
            width: 100%;
            padding: 20px 0;
            background-color: var(--color-acento);
            color: white;
            text-align: center;
            margin-top: 50px;
        }

        .linea-verde {
            width: max;
            height: 4px;
            background-color: var(--color-primario);
            margin: 10px auto 0 auto;
        }

        /* ========================================================
       SECCIÓN DE NOTIFICACIONES / ANUNCIOS
   ======================================================== */
        .notices-section {
            width: 100%;
            max-width: 1000px;
            padding: 0 20px;
            margin-top: 50px;
        }

        .notices-section h3 {
            color: var(--color-acento);
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 25px;
            position: relative;
            padding-left: 40px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .notices-section h3 i {
            font-size: 1.6rem;
            color: var(--color-primario);
            position: absolute;
            left: 0;
        }

        .notices-badge {
            display: inline-block;
            background-color: #FF6B6B;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
            margin-left: auto;
            margin-right: 0;
        }

        .notices-container {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .notice-item {
            background-color: var(--blanco-suave);
            border-left: 5px solid var(--color-primario);
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--sombra-suave);
            transition: all 0.3s ease;
        }

        .notice-item.unread {
            border-left: 5px solid #FF6B6B;
            background-color: #FFF9F9;
        }

        .notice-item:hover {
            transform: translateX(5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .notice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .notice-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--color-acento);
            margin: 0;
            flex: 1;
        }

        .notice-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .notice-status.pending {
            background-color: #FFE5E5;
            color: #FF6B6B;
        }

        .notice-status.viewed {
            background-color: #D4EDDA;
            color: #065D21;
        }

        .notice-details {
            color: #555;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 12px;
            max-height: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .notice-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: var(--color-acento);
            opacity: 0.7;
        }

        .notice-date {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .notice-actions {
            display: flex;
            gap: 10px;
        }

        .btn-view-notice {
            background-color: var(--color-primario);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .btn-view-notice:hover {
            background-color: var(--color-secundario);
        }

        .btn-mark-read {
            background-color: var(--color-acento);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .btn-mark-read:hover {
            background-color: #1a1c2b;
        }

        .no-notices {
            text-align: center;
            padding: 40px 20px;
            color: var(--color-acento);
            opacity: 0.7;
        }

        .no-notices i {
            font-size: 3rem;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        /* Modal para ver detalles de notificación */
        .modal-notice {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--color-primario);
            padding-bottom: 15px;
        }

        .modal-header h2 {
            margin: 0;
            color: var(--color-acento);
            font-size: 1.5rem;
        }

        .close-modal {
            font-size: 2rem;
            font-weight: bold;
            color: var(--color-acento);
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.3s ease;
        }

        .close-modal:hover {
            background-color: #f0f0f0;
        }

        .modal-body {
            color: var(--color-acento);
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .modal-meta {
            background-color: var(--blanco-suave);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .modal-meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .modal-meta-row:last-child {
            margin-bottom: 0;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        @media (max-width: 768px) {

            /* CONTENEDOR GENERAL */

            .student-info-section {
                width: 100%;

                padding: 24px 16px;
                box-sizing: border-box;
            }

            /* CARD */

            .student-info-box {
                width: 100%;

                padding: 28px 24px;

                border-radius: 18px;

                box-sizing: border-box;
            }

            /* TITULO */

            .student-info-box h3 {
                font-size: 1.5rem;

                text-align: center;

                margin-bottom: 30px;
            }

            /* GRID MOBILE */

            .info-display-grid {
                display: flex;
                flex-direction: column;
                gap: 22px;
            }

            /* CADA FILA */

            .info-row {
                display: flex;
                flex-direction: column;
                gap: 6px;
            }

            /* LABEL */

            .info-label {
                font-size: 1rem;
                font-weight: 700;

                color: var(--color-acento);
            }

            /* VALOR */

            .info-value {
                font-size: 0.95rem;

                padding-left: 0;

                word-break: break-word;

                line-height: 1.5;
            }

        }

        /* =========================================================
   MOBILE RESPONSIVE
   NO AFECTA DESKTOP
========================================================= */
        .mobile-grades-list {
            display: none;
        }

        @media (max-width: 768px) {

            /* =====================================================
       HERO SECTION
    ===================================================== */


            .grades-table-wrapper {
                display: none;
            }

            .mobile-grades-list {
                display: flex;
            }

            .hero-section {
                width: 100%;
                min-height: 100vh;
                position: relative;
                display: flex;
                flex-direction: column;
                justify-content: space-between;

                background:
                    linear-gradient(rgba(255, 255, 255, 0.55),
                        rgba(255, 255, 255, 0.55)),
                    url('assets/images/pexels-pixabay-2166.jpg');

                background-size: cover;
                background-position: center;
                overflow: hidden;
            }

            /* LOGO */

            .logo-container {
                padding: 24px 20px 0 20px;
                z-index: 2;
            }

            .school-logo {
                width: clamp(120px, 35vw, 170px);
                height: auto;
            }

            /* USER CARD */

            .user-card {
                position: absolute;
                top: 20px;
                right: 20px;

                width: 145px;

                background-color: var(--color-primario);

                border-radius: 0 0 22px 22px;

                padding: 14px 10px;

                display: flex;
                flex-direction: column;
                align-items: center;

                color: white;

                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.18);

                z-index: 3;
            }

            .user-carousel {
                width: 100%;

                display: flex;
                align-items: center;
                justify-content: space-between;

                margin-bottom: 8px;
            }

            .nav-arrow {
                color: rgba(255, 255, 255, 0.75);
                font-size: 18px;
                text-decoration: none;
                transition: 0.2s ease;
            }

            .nav-arrow:hover {
                color: white;
                transform: scale(1.1);
            }

            .user-icon {
                font-size: 42px;
                color: white;
            }

            .user-name {
                font-size: 11px;
                font-weight: 700;
                text-align: center;
                line-height: 1.3;
            }

            .logout-link {
                margin-top: 6px;

                font-size: 10px;
                color: var(--blanco-suave);

                text-decoration: underline;
            }

            /* WELCOME BAR */

            .welcome-bar {
                width: 100%;

                background-color: var(--color-primario);

                padding: 22px 24px 28px 24px;

                color: white;

                text-align: center;

                z-index: 2;
            }

            .welcome-bar h1 {
                margin: 0;

                font-size: clamp(24px, 7vw, 34px);

                font-weight: 800;

                letter-spacing: 1px;
            }

            .welcome-bar p {
                margin-top: 8px;

                font-size: clamp(13px, 3.5vw, 16px);

                line-height: 1.5;

                opacity: 0.95;
            }

            /* =====================================================
       STUDENT INFO
    ===================================================== */

            .student-info-section {
                width: 100%;

                padding: 24px 16px;
                box-sizing: border-box;
            }

            .student-info-box {
                width: 100%;

                padding: 28px 24px;

                border-radius: 18px;

                box-sizing: border-box;
            }

            .student-info-box h3 {
                font-size: 1.5rem;

                text-align: center;

                margin-bottom: 30px;
            }

            .info-display-grid {
                display: flex;
                flex-direction: column;
                gap: 22px;
            }

            .info-row {
                display: flex;
                flex-direction: column;
                gap: 6px;
            }

            .info-label {
                font-size: 1rem;
                font-weight: 700;

                color: var(--color-acento);
            }

            .info-value {
                font-size: 0.95rem;

                padding-left: 0;

                word-break: break-word;

                line-height: 1.5;
            }

            /* =====================================================
       CALIFICACIONES MOBILE
    ===================================================== */

            /* OCULTAR TABLA DESKTOP */

            .grades-table-wrapper {
                display: none;
            }

            .grades-section {
                width: 100%;

                padding: 0 16px 24px 16px;

                box-sizing: border-box;
            }

            .grades-section h3 {
                text-align: center;

                font-size: 1.5rem;

                padding-left: 0;
            }

            .grades-section h3::before {
                display: none;
            }

            /* LISTA MOBILE */

            .mobile-grades-list {
                display: flex;
                flex-direction: column;

                gap: 18px;

                margin-top: 20px;
            }

            /* CARD */

            .mobile-grade-card {
                display: flex;
                flex-direction: column;

                gap: 10px;
            }

            /* MATERIA */

            .mobile-subject {
                background-color: var(--color-primario);

                color: white;

                padding: 16px;

                border-radius: 10px;

                text-align: center;

                font-weight: 700;

                font-size: 1rem;

                box-shadow: var(--sombra-suave);
            }

            /* CALIFICACIONES */

            .mobile-grades-row {
                background-color: var(--color-acento);

                border-radius: 10px;

                padding: 14px 10px;

                display: flex;
                justify-content: space-around;
                align-items: center;

                color: white;

                font-weight: 700;

                font-size: 0.85rem;

                box-shadow: var(--sombra-suave);
            }

            /* BOTONES */

            .action-buttons {
                margin-top: 30px;

                padding: 0 16px 30px 16px;

                display: flex;
                justify-content: space-between;
                align-items: center;

                gap: 20px;
            }

            .btn-primary {
                padding: 12px 18px;

                font-size: 0.8rem;

                border-radius: 8px;
            }

            .promedio-container {
                font-size: 1rem;
            }

            /* =====================================================
       FOOTER
    ===================================================== */

            .footer {
                padding: 24px 16px;

                font-size: 0.85rem;

                line-height: 1.6;
            }

            /* =====================================================
       NOTIFICACIONES MOBILE
    ===================================================== */

            .notices-section {
                padding: 0 16px;
                margin-top: 30px;
            }

            .notices-section h3 {
                font-size: 1.3rem;
                padding-left: 35px;
            }

            .notice-item {
                padding: 16px;
            }

            .notice-title {
                font-size: 1rem;
            }

            .notice-details {
                font-size: 0.9rem;
            }

            .notice-actions {
                flex-direction: column;
                gap: 8px;
            }

            .btn-view-notice,
            .btn-mark-read {
                width: 100%;
                text-align: center;
            }

            .modal-content {
                width: 95%;
                padding: 20px;
                margin: 20% auto;
            }

            .modal-header h2 {
                font-size: 1.2rem;
            }
    </style>
</head>

<body>

    <!-- CABECERA -->
    <section class="hero-section">
        <div class="logo-container">
            <img src="assets/images/logo_no_bg.png" alt="Logo IPT" class="school-logo">
        </div>

        <div class="user-card">
            <div class="user-carousel">
                <a href="#" class="nav-arrow" id="prevStudent">
                    <i class="fa-solid fa-angle-left"></i>
                </a>

                <div class="user-icon-container">
                    <i class="fa-solid fa-circle-user user-icon"></i>
                </div>

                <a href="#" class="nav-arrow" id="nextStudent">
                    <i class="fa-solid fa-angle-right"></i>
                </a>
            </div>

            <span class="user-name"><?php echo htmlentities($selected_student->StudentName); ?></span>
            <a href="logout.php" class="logout-link">cerrar sesión</a>
        </div>

        <div class="welcome-bar">
            <h1>BIENVENIDO</h1>
            <p>aquí podrás revisar las calificaciones de cada unidad</p>
        </div>
    </section>


    <!-- MAIN CONTENT -->
    <!-- CONTENIDO PRINCIPAL -->
    <?php if ($selected_student): ?>
        <div class="content-section">

            <!-- INFO BOX DEL ESTUDIANTE -->
            <div class="student-info-section">
                <div class="student-info-box">
                    <h3>DATOS DEL ALUMNO</h3>

                    <div class="info-display-grid">
                        <div class="info-row">
                            <span class="info-label">Nombre:</span>
                            <span class="info-value"><?php echo htmlentities($selected_student->StudentName); ?></span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Grado:</span>
                            <span class="info-value"><?php echo htmlentities($selected_student->ClassName); ?></span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Grupo:</span>
                            <span class="info-value"><?php echo htmlentities($selected_student->Section); ?></span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Correo:</span>
                            <span class="info-value"><?php echo htmlentities($selected_student->StudentEmail); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DE NOTIFICACIONES / ANUNCIOS -->
        <div class="notices-section">
            <h3>
                <i class="fa-solid fa-bell"></i>
                Notificaciones y Anuncios
                <?php if ($pending_notices_count > 0): ?>
                    <span class="notices-badge"><?php echo $pending_notices_count; ?></span>
                <?php endif; ?>
            </h3>

            <?php if (count($student_notices) > 0): ?>
                <div class="notices-container">
                    <?php foreach ($student_notices as $notice): ?>
                        <div class="notice-item <?php echo ($notice->is_viewed == 0) ? 'unread' : ''; ?>">
                            <div class="notice-header">
                                <h4 class="notice-title"><?php echo htmlentities($notice->noticeTitle); ?></h4>
                                <span class="notice-status <?php echo ($notice->is_viewed == 0) ? 'pending' : 'viewed'; ?>">
                                    <?php echo ($notice->is_viewed == 0) ? 'Pendiente' : 'Leído'; ?>
                                </span>
                            </div>

                            <div class="notice-details">
                                <?php echo htmlentities(substr($notice->noticeDetails, 0, 120)); ?>
                                <?php if (strlen($notice->noticeDetails) > 120): ?>...<?php endif; ?>
                            </div>

                            <div class="notice-footer">
                                <div class="notice-date">
                                    <i class="fa-solid fa-calendar-days"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($notice->postingDate)); ?>
                                </div>
                                <div class="notice-actions">
                                    <button class="btn-view-notice" onclick="viewNoticeDetails(<?php echo $notice->notice_id; ?>, '<?php echo htmlentities($notice->noticeTitle); ?>', '<?php echo htmlentities(str_replace("'", "\\'", $notice->noticeDetails)); ?>', '<?php echo $notice->postingDate; ?>')">
                                        <i class="fa-solid fa-eye"></i> Ver
                                    </button>
                                    <?php if ($notice->is_viewed == 0): ?>
                                        <button class="btn-mark-read" onclick="markNoticeAsRead(<?php echo $notice->notice_student_id; ?>, this)">
                                            <i class="fa-solid fa-check"></i> Marcar como leído
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-notices">
                    <div>
                        <i class="fa-solid fa-inbox"></i>
                        <h4>No hay notificaciones</h4>
                        <p>No tienes notificaciones en este momento.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- MODAL PARA VER DETALLES DE NOTIFICACIÓN -->
        <div id="noticeModal" class="modal-notice">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalNoticeTitle"></h2>
                    <button class="close-modal" onclick="closeNoticeModal()">&times;</button>
                </div>
                <div class="modal-body" id="modalNoticeBody"></div>
                <div class="modal-meta" id="modalNoticeMeta"></div>
                <div class="modal-actions">
                    <button class="btn-primary" onclick="closeNoticeModal()" style="background-color: var(--color-acento);">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DE CALIFICACIONES - ESPAÑOL -->
        <div class="grades-section">
            <h3>CALIFICACIÓN - ESPAÑOL</h3>

            <?php if (count($student_grades_spanish) > 0): ?>
                <!-- TABLA DESKTOP -->
                <div class="grades-table-wrapper">

                    <table class="grades-table">
                        <thead class="unidades">
                            <tr>
                                <th>Asignatura</th>
                                <th>
                                    <div class="contenedor-unidades">1</div>
                                </th>
                                <th>
                                    <div class="contenedor-unidades">2</div>
                                </th>
                                <th>
                                    <div class="contenedor-unidades">3</div>
                                </th>
                                <th>
                                    <div class="contenedor-unidades">4</div>
                                </th>
                                <th>
                                    <div class="contenedor-unidades">5</div>
                                </th>
                                <th>PROM</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php 
                            $total_spanish = 0;
                            $count_spanish = 0;
                            foreach ($student_grades_spanish as $grade):

                                $terms = array_filter([
                                    $grade->term1,
                                    $grade->term2,
                                    $grade->term3
                                ]);

                                $avg = count($terms) > 0
                                    ? array_sum($terms) / count($terms)
                                    : 0;
                                
                                $total_spanish += $avg;
                                $count_spanish++;
                            ?>

                                <tr class="grade-item-row">

                                    <td>
                                        <?php echo htmlentities($grade->SubjectName); ?>
                                    </td>

                                    <td>
                                        <div class="contenedor-unidades">
                                            <?php echo $grade->term1 ?: '-'; ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="contenedor-unidades">
                                            <?php echo $grade->term2 ?: '-'; ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="contenedor-unidades">
                                            <?php echo $grade->term3 ?: '-'; ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="contenedor-unidades">
                                            <?php echo $grade->term4 ?: '-'; ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="contenedor-unidades">
                                            <?php echo $grade->term5 ?: '-'; ?>
                                        </div>
                                    </td>

                                    <td style="font-weight: 800;">
                                        <div class="contenedor-unidades">
                                            <?php echo number_format($avg, 1); ?>
                                        </div>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>
                    </table>

                </div>

                <!-- MOBILE CARDS -->
                <div class="mobile-grades-list">

                    <?php 
                    $total_spanish = 0;
                    $count_spanish = 0;
                    foreach ($student_grades_spanish as $grade):

                        $terms = array_filter([
                            $grade->term1,
                            $grade->term2,
                            $grade->term3
                        ]);

                        $avg = count($terms) > 0
                            ? array_sum($terms) / count($terms)
                            : 0;
                        
                        $total_spanish += $avg;
                        $count_spanish++;
                    ?>

                        <div class="mobile-grade-card">

                            <div class="mobile-subject">
                                <?php echo htmlentities($grade->SubjectName); ?>
                            </div>

                            <div class="mobile-grades-row">

                                <span><?php echo $grade->term1 ?: '-'; ?></span>
                                <span><?php echo $grade->term2 ?: '-'; ?></span>
                                <span><?php echo $grade->term3 ?: '-'; ?></span>
                                <span><?php echo $grade->term4 ?: '-'; ?></span>
                                <span><?php echo $grade->term5 ?: '-'; ?></span>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>
                <div class="action-buttons-spanish">
                    <div style="font-weight: 800" class="promedio-container">
                        <p>Promedio Español: <?php echo $count_spanish > 0 ? number_format($total_spanish / $count_spanish, 1) : '0.0'; ?></p>
                    </div>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--color-acento);">No hay calificaciones en Español registradas aún.</p>
            <?php endif; ?>
        </div>

        <!-- SECCIÓN DE CALIFICACIONES - INGLÉS -->
        <div class="grades-section">
            <h3>CALIFICACIÓN - INGLÉS</h3>

            <?php if (count($student_grades_english) > 0): ?>
                <!-- TABLA DESKTOP -->
                <div class="grades-table-wrapper">

                    <table class="grades-table">
                        <thead class="unidades">
                            <tr>
                                <th>Asignatura</th>
                                <th>
                                    <div class="contenedor-unidades">1</div>
                                </th>
                                <th>
                                    <div class="contenedor-unidades">2</div>
                                </th>
                                <th>
                                    <div class="contenedor-unidades">3</div>
                                </th>
                                <th>
                                    <div class="contenedor-unidades">4</div>
                                </th>
                                <th>
                                    <div class="contenedor-unidades">5</div>
                                </th>
                                <th>PROM</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php 
                            $total_english = 0;
                            $count_english = 0;
                            foreach ($student_grades_english as $grade):

                                $terms = array_filter([
                                    $grade->term1,
                                    $grade->term2,
                                    $grade->term3
                                ]);

                                $avg = count($terms) > 0
                                    ? array_sum($terms) / count($terms)
                                    : 0;
                                
                                $total_english += $avg;
                                $count_english++;
                            ?>

                                <tr class="grade-item-row">

                                    <td>
                                        <?php echo htmlentities($grade->SubjectName); ?>
                                    </td>

                                    <td>
                                        <div class="contenedor-unidades">
                                            <?php echo $grade->term1 ?: '-'; ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="contenedor-unidades">
                                            <?php echo $grade->term2 ?: '-'; ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="contenedor-unidades">
                                            <?php echo $grade->term3 ?: '-'; ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="contenedor-unidades">
                                            <?php echo $grade->term4 ?: '-'; ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="contenedor-unidades">
                                            <?php echo $grade->term5 ?: '-'; ?>
                                        </div>
                                    </td>

                                    <td style="font-weight: 800;">
                                        <div class="contenedor-unidades">
                                            <?php echo number_format($avg, 1); ?>
                                        </div>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>
                    </table>

                </div>

                <!-- MOBILE CARDS -->
                <div class="mobile-grades-list">

                    <?php 
                    $total_english = 0;
                    $count_english = 0;
                    foreach ($student_grades_english as $grade):

                        $terms = array_filter([
                            $grade->term1,
                            $grade->term2,
                            $grade->term3
                        ]);

                        $avg = count($terms) > 0
                            ? array_sum($terms) / count($terms)
                            : 0;
                        
                        $total_english += $avg;
                        $count_english++;
                    ?>

                        <div class="mobile-grade-card">

                            <div class="mobile-subject">
                                <?php echo htmlentities($grade->SubjectName); ?>
                            </div>

                            <div class="mobile-grades-row">

                                <span><?php echo $grade->term1 ?: '-'; ?></span>
                                <span><?php echo $grade->term2 ?: '-'; ?></span>
                                <span><?php echo $grade->term3 ?: '-'; ?></span>
                                <span><?php echo $grade->term4 ?: '-'; ?></span>
                                <span><?php echo $grade->term5 ?: '-'; ?></span>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>
                <div class="action-buttons">
                    <a href="generate-student-pdf.php?student_id=<?php echo $selected_student_id; ?>"
                        target="_blank"
                        class="btn-primary">
                        <i class="fas fa-file-pdf"></i> Imprimir Boleta
                    </a>
                    <div style="font-weight: 800" class="promedio-container">
                        <p>Promedio Inglés: <?php echo $count_english > 0 ? number_format($total_english / $count_english, 1) : '0.0'; ?></p>
                    </div>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--color-acento);">No hay calificaciones en Inglés registradas aún.</p>
            <?php endif; ?>
        </div>
        </div>
    <?php else: ?>
        <div class="no-students">
            <i class="fas fa-inbox"></i>
            <h3>No hay estudiantes asignados</h3>
            <p>Contacta al administrador del sistema para obtener acceso a los registros de tus hijos.</p>
        </div>
    <?php endif; ?>

    <!-- WRAPPER DE CONTENIDO (si es necesario cerrar algo) -->
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <p>&copy; 2026 Instituto Panamericano de Tampico. Todos los derechos reservados.</p>
        <p><small>Portal de Tutores v1.0</small></p>
        <div class="linea-verde"></div>
    </div>

    <!-- Script para la navegación del carrusel de estudiantes -->
    <script>
        // Función para ver detalles de notificación
        function viewNoticeDetails(noticeId, title, details, date) {
            document.getElementById('modalNoticeTitle').textContent = title;
            document.getElementById('modalNoticeBody').textContent = details;
            
            const formattedDate = new Date(date).toLocaleString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            document.getElementById('modalNoticeMeta').innerHTML = `
                <div class="modal-meta-row">
                    <strong>Fecha de publicación:</strong>
                    <span>${formattedDate}</span>
                </div>
            `;

            document.getElementById('noticeModal').style.display = 'block';
        }

        // Función para cerrar el modal
        function closeNoticeModal() {
            document.getElementById('noticeModal').style.display = 'none';
        }

        // Cerrar modal cuando se hace clic fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('noticeModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Función para marcar notificación como leída
        function markNoticeAsRead(noticeStudentId, buttonElement) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'mark-notice-viewed.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Actualizar visualmente
                            const noticeItem = buttonElement.closest('.notice-item');
                            noticeItem.classList.remove('unread');

                            // Actualizar estado
                            const statusBadge = noticeItem.querySelector('.notice-status');
                            statusBadge.textContent = 'Leído';
                            statusBadge.classList.remove('pending');
                            statusBadge.classList.add('viewed');

                            // Ocultar botón de marcar como leído
                            buttonElement.style.display = 'none';

                            // Actualizar contador de notificaciones pendientes
                            const badge = document.querySelector('.notices-badge');
                            if (badge) {
                                let count = parseInt(badge.textContent);
                                count--;
                                if (count > 0) {
                                    badge.textContent = count;
                                } else {
                                    badge.style.display = 'none';
                                }
                            }

                            // Mostrar mensaje de éxito
                            alert('Notificación marcada como leída');
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }
                }
            };

            xhr.onerror = function() {
                alert('Error al marcar la notificación como leída');
            };

            xhr.send('notice_student_id=' + noticeStudentId);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const students = <?php echo json_encode(array_map(function ($s) {
                                    return ['id' => $s->StudentId, 'name' => $s->StudentName];
                                }, $students)); ?>;

            const currentStudentId = <?php echo json_encode($selected_student_id); ?>;
            let currentIndex = students.findIndex(s => s.id == currentStudentId);

            const prevBtn = document.getElementById('prevStudent');
            const nextBtn = document.getElementById('nextStudent');

            if (prevBtn) {
                prevBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (students.length > 0) {
                        currentIndex = (currentIndex - 1 + students.length) % students.length;
                        window.location.href = '?student_id=' + students[currentIndex].id;
                    }
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (students.length > 0) {
                        currentIndex = (currentIndex + 1) % students.length;
                        window.location.href = '?student_id=' + students[currentIndex].id;
                    }
                });
            }
        });
    </script>

</body>

</html>