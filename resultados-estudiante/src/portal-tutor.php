<?php

/**
 * portal-tutor.php - Panel exclusivo para Tutores (Padres de Familia)
 */

session_start();
error_reporting(0);
include(__DIR__ . '/includes/config.php');

if (!isset($_SESSION['alogin']) || $_SESSION['role'] !== 'tutor') {
    header("Location: index.php");
    exit;
}

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
        WHERE st.TutorId = :tutor_id AND st.CanViewGrades = 1
        ORDER BY s.StudentName ASC";
$query = $dbh->prepare($sql);
$query->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
$query->execute();
$students = $query->fetchAll(PDO::FETCH_OBJ);

$selected_student_id = $_GET['student_id'] ?? ($students[0]->StudentId ?? null);
$selected_student    = null;
$grades_data            = null;
$student_notices        = [];
$pending_notices_count  = 0;
$current_student_index  = 0;
$total_students         = count($students);

if ($selected_student_id) {
    $sql   = "SELECT * FROM student_tutor WHERE StudentId = :sid AND TutorId = :tid";
    $check = $dbh->prepare($sql);
    $check->bindParam(':sid', $selected_student_id, PDO::PARAM_INT);
    $check->bindParam(':tid', $tutor_id, PDO::PARAM_INT);
    $check->execute();

    if ($check->rowCount() > 0) {
        $sql = "SELECT s.*, c.ClassName, c.Section FROM tblstudents s
                JOIN tblclasses c ON s.ClassId = c.id
                WHERE s.StudentId = :sid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':sid', $selected_student_id, PDO::PARAM_INT);
        $query->execute();
        $selected_student = $query->fetch(PDO::FETCH_OBJ);

        // Calificaciones estructuradas como en la boleta (agrupadas por tipo, con rubros,
        // periodos por nivel). Pivot por Trimestre texto, no por term.
        require_once(__DIR__ . '/includes/student-grades-data.php');
        $grades_data = get_student_grades_data($dbh, (int) $selected_student_id);

        // Notificaciones
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

        foreach ($student_notices as $notice) {
            if ($notice->is_viewed == 0) $pending_notices_count++;
        }

        // Índice del estudiante actual para el indicador de paginación
        foreach ($students as $i => $s) {
            if ($s->StudentId == $selected_student_id) {
                $current_student_index = $i;
                break;
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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* =====================================================
           VARIABLES
        ===================================================== */
        :root {
            --verde:       #0F9B3A;
            --verde-dark:  #065D21;
            --acento:      #32344B;
            --fondo:       #F0F7F3;
            --fondo-card:  #E4F6EA;
            --blanco:      #FFFFFF;
            --rojo:        #E84545;
            --rojo-suave:  #FFF0F0;
            --sombra:      0 4px 16px rgba(0,0,0,0.08);
            --sombra-lg:   0 8px 32px rgba(0,0,0,0.13);
            --radius:      16px;
            --radius-sm:   8px;
        }

        /* =====================================================
           RESET Y BASE
        ===================================================== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--fondo);
            color: var(--acento);
            overflow-x: hidden;
        }

        /* =====================================================
           HERO / CABECERA
        ===================================================== */
        .hero-section {
            width: 100%;
            min-height: 42vh;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background:
                linear-gradient(rgba(255,255,255,0.52), rgba(255,255,255,0.52)),
                url('assets/images/pexels-pixabay-2166.jpg') center/cover no-repeat;
            overflow: hidden;
        }

        .logo-container {
            padding: 32px 36px 0;
            z-index: 2;
        }

        .school-logo {
            width: clamp(160px, 22vw, 240px);
            height: auto;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,0.12));
        }

        /* =====================================================
           USER CARD
        ===================================================== */
        .user-card {
            position: absolute;
            top: 0;
            right: 24px;
            width: 152px;
            background: var(--verde);
            border-radius: 0 0 24px 24px;
            padding: 16px 12px 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            color: var(--blanco);
            box-shadow: var(--sombra-lg);
            z-index: 10;
        }

        .user-carousel {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .nav-arrow {
            color: rgba(255,255,255,0.7);
            font-size: 17px;
            text-decoration: none;
            transition: color .2s, transform .2s;
            padding: 4px 6px;
            border-radius: 50%;
        }
        .nav-arrow:hover { color: var(--blanco); transform: scale(1.15); }
        .nav-arrow.disabled { opacity: .25; pointer-events: none; cursor: default; }

        .user-icon { font-size: 40px; }

        .user-name {
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            line-height: 1.35;
            padding: 0 4px;
        }

        /* Indicador de paginación de hijos */
        .student-counter {
            font-size: 10px;
            opacity: .75;
            letter-spacing: .5px;
        }

        .logout-link {
            font-size: 10px;
            color: var(--fondo-card);
            text-decoration: underline;
            margin-top: 2px;
        }

        /* =====================================================
           WELCOME BAR
        ===================================================== */
        .welcome-bar {
            width: 100%;
            background: var(--verde);
            padding: 24px 28px 30px;
            color: var(--blanco);
            text-align: center;
            z-index: 2;
        }

        .welcome-bar h1 {
            font-size: clamp(22px, 6vw, 34px);
            font-weight: 800;
            letter-spacing: 1.5px;
        }

        .welcome-bar p {
            margin-top: 8px;
            font-size: clamp(13px, 3.2vw, 15px);
            opacity: .9;
            line-height: 1.5;
        }

        /* =====================================================
           LAYOUT PRINCIPAL
        ===================================================== */
        .page-content {
            width: 100%;
            max-width: 1040px;
            margin: 0 auto;
            padding: 0 24px 60px;
            display: flex;
            flex-direction: column;
            gap: 48px;
        }

        /* Orden de secciones: datos -> calificaciones -> notificaciones.
           El tutor entra principalmente a ver notas, así que van antes que los avisos.
           Se reordena con flexbox sin mover el HTML. */
        .student-info-section { order: 1; }
        .grades-section        { order: 2; }
        .action-bar            { order: 3; }
        .notices-section       { order: 4; }

        /* =====================================================
           DATOS DEL ALUMNO
        ===================================================== */
        .student-info-section {
            display: flex;
            justify-content: center;
            padding-top: 48px;
        }

        .student-info-box {
            background: var(--fondo-card);
            border-radius: var(--radius);
            padding: 32px 48px;
            box-shadow: var(--sombra);
            width: 100%;
            max-width: 680px;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--acento);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title::before {
            content: '';
            display: inline-block;
            width: 5px;
            height: 1.2em;
            background: var(--verde);
            border-radius: 4px;
            flex-shrink: 0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: auto 1fr;
            row-gap: 16px;
            column-gap: 32px;
        }

        .info-label {
            font-weight: 700;
            font-size: .95rem;
            color: var(--acento);
            white-space: nowrap;
        }

        .info-value {
            font-weight: 400;
            font-size: .95rem;
            color: var(--acento);
            word-break: break-word;
        }

        /* =====================================================
           SECCIÓN DE CALIFICACIONES
        ===================================================== */
        .grades-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .grades-header .section-title { margin-bottom: 0; }

        .promedio-badge {
            background: var(--acento);
            color: var(--blanco);
            font-size: .85rem;
            font-weight: 700;
            padding: 6px 18px;
            border-radius: 999px;
            white-space: nowrap;
        }

        /* Tabla desktop */
        .grades-table-wrapper {
            width: 100%;
            overflow: hidden;
            border-radius: 12px;
            box-shadow: var(--sombra);
            margin-bottom: 8px;
        }

        .grades-table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Tabla con fondo claro: más legible para consultar muchas materias.
           El color institucional se usa como acento (cabecera, promedio), no como
           fondo de cada celda. */
        .grades-table { border-spacing: 0; }

        .grades-table thead th {
            color: var(--blanco);
            background: var(--acento);
            font-weight: 700;
            font-size: .78rem;
            padding: 12px 14px;
            text-transform: uppercase;
            letter-spacing: .5px;
            text-align: center;
        }
        .grades-table thead th:first-child { text-align: left; border-radius: 10px 0 0 0; }
        .grades-table thead th:last-child  { border-radius: 0 10px 0 0; }

        .grade-item-row td {
            padding: 13px 14px;
            color: var(--acento);
            background: var(--blanco);
            font-size: .95rem;
            text-align: center;
            border-bottom: 1px solid #E6EFE9;
            transition: background .15s;
        }

        .grade-item-row:hover td { background: var(--fondo-card); }

        .grade-item-row td:first-child {
            padding-left: 20px;
            text-align: left;
            width: 38%;
            font-weight: 600;
        }

        /* Celda de promedio final destacada con el verde institucional */
        .grade-item-row td:last-child {
            font-weight: 800;
            color: var(--verde-dark);
            background: var(--fondo-card);
        }

        /* Periodos sin nota: gris tenue para no competir con los datos reales */
        .grade-empty { color: #B8C4BD; }

        /* Mobile cards de calificaciones */
        .mobile-grades-list { display: none; }

        /* Toggle colapsable para móvil */
        .grades-toggle-btn {
            display: none; /* visible solo en mobile via media query */
        }

        /* =====================================================
           BOTONES DE ACCIÓN
        ===================================================== */
        .action-bar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 16px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .btn-pdf {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--acento);
            color: var(--blanco);
            padding: 12px 28px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 700;
            font-size: .88rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            transition: transform .2s, box-shadow .2s;
            box-shadow: 0 4px 12px rgba(50,52,75,.25);
        }

        .btn-pdf:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(50,52,75,.3);
        }

        .btn-pdf-large {
            padding: 15px 40px;
            font-size: 1rem;
            letter-spacing: .8px;
            background: var(--verde);
            box-shadow: 0 6px 20px rgba(15,155,58,.3);
        }
        .btn-pdf-large:hover { background: var(--verde-dark); box-shadow: 0 10px 28px rgba(15,155,58,.35); }

        /* =====================================================
           NOTIFICACIONES
        ===================================================== */
        .notices-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .notices-header .section-title { margin-bottom: 0; flex: 1; }

        .notices-badge {
            background: var(--rojo);
            color: var(--blanco);
            border-radius: 999px;
            min-width: 24px;
            height: 24px;
            padding: 0 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .72rem;
        }

        .notices-icon {
            font-size: 1.3rem;
            color: var(--verde);
        }

        .notices-container {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .notice-item {
            background: var(--blanco);
            border-left: 5px solid var(--verde);
            border-radius: var(--radius-sm);
            padding: 20px 24px;
            box-shadow: var(--sombra);
            transition: transform .25s, box-shadow .25s;
        }

        .notice-item.unread {
            border-left-color: var(--rojo);
            background: var(--rojo-suave);
        }

        .notice-item:hover {
            transform: translateX(4px);
            box-shadow: var(--sombra-lg);
        }

        .notice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 10px;
        }

        .notice-title {
            font-weight: 700;
            font-size: 1rem;
            color: var(--acento);
            flex: 1;
        }

        .notice-status {
            flex-shrink: 0;
            padding: 3px 12px;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .notice-status.pending { background: #FFE0E0; color: var(--rojo); }
        .notice-status.viewed  { background: #D4EDDA; color: var(--verde-dark); }

        .notice-details {
            color: #555;
            font-size: .9rem;
            line-height: 1.55;
            margin-bottom: 14px;
            position: relative;
            max-height: 72px;
            overflow: hidden;
        }

        /* Degradado de corte suave */
        .notice-details::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 28px;
            background: linear-gradient(transparent, #fff8f8);
            pointer-events: none;
        }

        .notice-item:not(.unread) .notice-details::after {
            background: linear-gradient(transparent, var(--blanco));
        }

        .notice-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            font-size: .82rem;
            color: var(--acento);
            flex-wrap: wrap;
        }

        .notice-date {
            display: flex;
            align-items: center;
            gap: 6px;
            opacity: .65;
        }

        .notice-actions { display: flex; gap: 8px; flex-wrap: wrap; }

        .btn-archive {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(50,52,75,.25);
            background: transparent;
            color: var(--acento);
            padding: 7px 14px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: .82rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            transition: background .2s, border-color .2s;
        }
        .btn-archive:hover { background: rgba(50,52,75,.08); border-color: var(--acento); }

        /* Sección de anuncios archivados */
        .archived-section {
            margin-top: 24px;
        }

        .archived-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            background: none;
            border: none;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: .88rem;
            font-weight: 600;
            color: var(--acento);
            opacity: .55;
            padding: 8px 0;
            transition: opacity .2s;
        }
        .archived-toggle:hover { opacity: .9; }
        .archived-toggle i { transition: transform .25s; }
        .archived-toggle.open i.chevron { transform: rotate(180deg); }

        .archived-list {
            display: none;
            flex-direction: column;
            gap: 10px;
            margin-top: 12px;
        }
        .archived-list.open { display: flex; }

        .notice-item.archived {
            opacity: .55;
            border-left-color: #aaa;
            background: #fafafa;
        }
        .notice-item.archived:hover { opacity: .85; transform: none; }

        .btn-view-notice,
        .btn-mark-read {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            padding: 7px 16px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: .82rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            transition: background .25s, transform .15s;
        }

        .btn-view-notice {
            background: var(--verde);
            color: var(--blanco);
        }
        .btn-view-notice:hover { background: var(--verde-dark); transform: translateY(-1px); }

        .btn-mark-read {
            background: var(--acento);
            color: var(--blanco);
        }
        .btn-mark-read:hover { background: #1a1c2b; transform: translateY(-1px); }
        .btn-mark-read:disabled { opacity: .5; cursor: default; transform: none; }

        .no-notices {
            text-align: center;
            padding: 48px 20px;
            color: var(--acento);
            opacity: .6;
        }
        .no-notices i { font-size: 3rem; display: block; margin-bottom: 12px; }

        /* =====================================================
           EMPTY STATE (sin estudiantes)
        ===================================================== */
        .no-students {
            text-align: center;
            padding: 80px 24px;
            color: var(--acento);
        }
        .no-students i { font-size: 4rem; opacity: .35; display: block; margin-bottom: 16px; }
        .no-students h3 { font-size: 1.3rem; margin-bottom: 8px; }
        .no-students p  { opacity: .65; font-size: .95rem; }

        /* =====================================================
           MODAL
        ===================================================== */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 200;
            animation: fadeIn .25s ease;
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .modal-box {
            background: var(--blanco);
            margin: 6vh auto;
            padding: 32px;
            border-radius: var(--radius);
            width: 90%;
            max-width: 580px;
            box-shadow: 0 16px 48px rgba(0,0,0,.28);
            animation: slideUp .28s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(40px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            border-bottom: 2px solid var(--verde);
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            font-size: 1.2rem;
            color: var(--acento);
            line-height: 1.4;
        }

        .close-modal {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--acento);
            cursor: pointer;
            background: none;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .2s;
            flex-shrink: 0;
        }
        .close-modal:hover { background: #f0f0f0; }

        .modal-body {
            color: var(--acento);
            line-height: 1.8;
            font-size: .95rem;
            margin-bottom: 20px;
            white-space: pre-wrap;
        }

        .modal-meta {
            background: var(--fondo-card);
            padding: 14px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-size: .88rem;
        }

        .modal-meta-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 6px;
        }
        .modal-meta-row:last-child { margin-bottom: 0; }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
        }

        /* =====================================================
           TOAST
        ===================================================== */
        #toast {
            position: fixed;
            bottom: 28px;
            right: 28px;
            background: var(--acento);
            color: var(--blanco);
            padding: 13px 22px;
            border-radius: var(--radius-sm);
            font-size: .9rem;
            font-weight: 600;
            box-shadow: var(--sombra-lg);
            z-index: 300;
            display: none;
            align-items: center;
            gap: 10px;
            animation: fadeIn .3s ease;
        }

        #toast.show { display: flex; }
        #toast.success { background: var(--verde-dark); }
        #toast.error   { background: var(--rojo); }

        /* =====================================================
           FOOTER
        ===================================================== */
        .site-footer {
            width: 100%;
            background: var(--acento);
            color: rgba(255,255,255,.8);
            text-align: center;
            padding: 24px 16px;
            font-size: .85rem;
            line-height: 1.6;
        }

        .footer-line {
            width: 60px;
            height: 4px;
            background: var(--verde);
            border-radius: 4px;
            margin: 10px auto 0;
        }

        /* =====================================================
           MOBILE
        ===================================================== */
        @media (max-width: 768px) {
            .page-content { padding: 0 16px 48px; gap: 36px; }

            .student-info-section { padding-top: 32px; }
            .student-info-box { padding: 24px 20px; }

            .section-title { font-size: .92rem; }

            .info-grid {
                grid-template-columns: 1fr;
                row-gap: 14px;
            }
            .info-label { font-size: .9rem; }
            .info-value { font-size: .88rem; padding-left: 0; }

            /* Ocultar tabla, mostrar cards */
            .grades-table-wrapper { display: none; }

            /* --- Toggle colapsable de sección de calificaciones --- */
            .grades-toggle-btn {
                display: flex;
                align-items: center;
                gap: 8px;
                background: none;
                border: none;
                cursor: pointer;
                font-family: 'Poppins', sans-serif;
                font-size: .82rem;
                font-weight: 600;
                color: var(--acento);
                opacity: .6;
                padding: 4px 0;
                transition: opacity .2s;
            }
            .grades-toggle-btn:hover { opacity: 1; }
            .grades-toggle-btn i.chevron { transition: transform .25s; }
            .grades-toggle-btn.open i.chevron { transform: rotate(180deg); }

            /* En móvil las calificaciones se muestran ABIERTAS por defecto (es el dato
               principal). El botón permite colapsarlas si el tutor quiere. */
            .grades-collapsible {
                display: block;
            }
            .grades-collapsible.collapsed {
                display: none;
            }

            .mobile-grades-list {
                display: flex;
                flex-direction: column;
                gap: 14px;
                margin-top: 12px;
            }

            .mobile-grade-card { display: flex; flex-direction: column; gap: 6px; }

            .mobile-subject {
                background: var(--verde);
                color: var(--blanco);
                padding: 14px 18px;
                border-radius: 12px 12px 0 0;
                font-weight: 700;
                font-size: .95rem;
            }

            .mobile-grades-row {
                background: var(--acento);
                border-radius: 0 0 12px 12px;
                padding: 12px 10px;
                display: flex;
                justify-content: space-around;
                align-items: center;
                color: var(--blanco);
                font-weight: 600;
                font-size: .85rem;
                gap: 4px;
            }

            .mobile-grade-unit {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 2px;
                flex: 1;
            }

            .mobile-grade-unit span:first-child {
                font-size: .65rem;
                opacity: .6;
                text-transform: uppercase;
            }

            .action-bar { justify-content: center; }

            .notice-header { flex-direction: column; gap: 8px; }
            .notice-status { align-self: flex-start; }

            .btn-view-notice, .btn-mark-read, .btn-archive { width: 100%; justify-content: center; }

            .modal-box { padding: 22px 18px; margin: 15vh auto; }
            .modal-header h2 { font-size: 1.05rem; }

            #toast { bottom: 16px; right: 16px; left: 16px; }
        }
    </style>
</head>

<body>

    <!-- ===================== HERO ===================== -->
    <section class="hero-section">
        <div class="logo-container">
            <img src="assets/images/logo_no_bg.png" alt="Logo Instituto Panamericano" class="school-logo">
        </div>

        <div class="user-card">
            <div class="user-carousel">
                <a href="#" class="nav-arrow <?php echo ($total_students <= 1) ? 'disabled' : ''; ?>"
                   id="prevStudent" aria-label="Estudiante anterior">
                    <i class="fa-solid fa-angle-left"></i>
                </a>
                <i class="fa-solid fa-circle-user user-icon" aria-hidden="true"></i>
                <a href="#" class="nav-arrow <?php echo ($total_students <= 1) ? 'disabled' : ''; ?>"
                   id="nextStudent" aria-label="Siguiente estudiante">
                    <i class="fa-solid fa-angle-right"></i>
                </a>
            </div>

            <?php if ($selected_student): ?>
                <span class="user-name"><?php echo htmlentities($selected_student->StudentName); ?></span>
            <?php else: ?>
                <span class="user-name">Sin estudiante</span>
            <?php endif; ?>

            <?php if ($total_students > 1): ?>
                <span class="student-counter"><?php echo ($current_student_index + 1) . ' / ' . $total_students; ?></span>
            <?php endif; ?>

            <?php /* Contraseña del tutor deshabilitada (2026-07-09): el tutor entra solo con el
                     correo del alumno, sin clave. Enlace oculto; reactivar quitando el if(false). */ ?>
            <?php if (false): ?>
            <a href="tutor-change-password.php" class="logout-link" style="margin-top:0;">Cambiar contraseña</a>
            <?php endif; ?>
            <a href="logout.php" class="logout-link">Cerrar sesión</a>
        </div>

        <div class="welcome-bar">
            <h1>BIENVENIDO</h1>
            <p>Aquí podrás revisar las calificaciones y notificaciones de tu hijo/a</p>
        </div>
    </section>

    <!-- ===================== CONTENIDO PRINCIPAL ===================== -->
    <?php if ($selected_student): ?>

        <div class="page-content">

            <!-- DATOS DEL ALUMNO -->
            <div class="student-info-section">
                <div class="student-info-box">
                    <h3 class="section-title">Datos del Alumno</h3>
                    <div class="info-grid">
                        <span class="info-label">Nombre</span>
                        <span class="info-value"><?php echo htmlentities($selected_student->StudentName); ?></span>

                        <span class="info-label">Grado</span>
                        <span class="info-value"><?php echo htmlentities($selected_student->ClassName); ?></span>

                        <span class="info-label">Grupo</span>
                        <span class="info-value"><?php echo htmlentities($selected_student->Section); ?></span>

                        <span class="info-label">Correo</span>
                        <span class="info-value"><?php echo htmlentities($selected_student->StudentEmail); ?></span>
                    </div>
                </div>
            </div>

            <!-- NOTIFICACIONES -->
            <div class="notices-section">
                <div class="notices-header">
                    <h3 class="section-title">
                        <i class="fa-solid fa-bell notices-icon" aria-hidden="true"></i>
                        Notificaciones y Anuncios
                    </h3>
                    <?php if ($pending_notices_count > 0): ?>
                        <span class="notices-badge" aria-label="<?php echo $pending_notices_count; ?> notificaciones pendientes">
                            <?php echo $pending_notices_count; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (count($student_notices) > 0): ?>
                    <div class="notices-container" id="noticesActive">
                        <?php foreach ($student_notices as $notice): ?>
                            <div class="notice-item <?php echo ($notice->is_viewed == 0) ? 'unread' : ''; ?>"
                                 id="notice-item-<?php echo $notice->notice_student_id; ?>"
                                 data-notice-id="<?php echo $notice->notice_id; ?>"
                                 data-title="<?php echo htmlentities($notice->noticeTitle, ENT_QUOTES); ?>"
                                 data-details="<?php echo htmlentities($notice->noticeDetails, ENT_QUOTES); ?>"
                                 data-date="<?php echo htmlentities($notice->postingDate, ENT_QUOTES); ?>">
                                <div class="notice-header">
                                    <h4 class="notice-title"><?php echo htmlentities($notice->noticeTitle); ?></h4>
                                    <span class="notice-status <?php echo ($notice->is_viewed == 0) ? 'pending' : 'viewed'; ?>">
                                        <?php echo ($notice->is_viewed == 0) ? 'Pendiente' : 'Leído'; ?>
                                    </span>
                                </div>

                                <div class="notice-details">
                                    <?php echo htmlentities(substr($notice->noticeDetails, 0, 150)); ?>
                                    <?php if (strlen($notice->noticeDetails) > 150): ?>…<?php endif; ?>
                                </div>

                                <div class="notice-footer">
                                    <div class="notice-date">
                                        <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($notice->postingDate)); ?>
                                    </div>
                                    <div class="notice-actions">
                                        <button class="btn-view-notice"
                                                aria-label="Ver detalle: <?php echo htmlentities($notice->noticeTitle, ENT_QUOTES); ?>"
                                                data-action="view-notice">
                                            <i class="fa-solid fa-eye" aria-hidden="true"></i> Ver
                                        </button>
                                        <?php if ($notice->is_viewed == 0): ?>
                                            <button class="btn-mark-read"
                                                    aria-label="Marcar como leída: <?php echo htmlentities($notice->noticeTitle, ENT_QUOTES); ?>"
                                                    data-action="mark-read"
                                                    data-nsi="<?php echo $notice->notice_student_id; ?>">
                                                <i class="fa-solid fa-check" aria-hidden="true"></i> Marcar leído
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn-archive"
                                                aria-label="Archivar: <?php echo htmlentities($notice->noticeTitle, ENT_QUOTES); ?>"
                                                data-action="archive">
                                            <i class="fa-solid fa-box-archive" aria-hidden="true"></i> Archivar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Sección de archivados -->
                    <div class="archived-section" id="archivedSection" style="display:none;">
                        <button class="archived-toggle" id="archivedToggle" aria-expanded="false">
                            <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
                            <span id="archivedLabel">Anteriores (0)</span>
                            <i class="fa-solid fa-chevron-down chevron" aria-hidden="true"></i>
                        </button>
                        <div class="archived-list" id="archivedList" role="list"></div>
                    </div>

                <?php else: ?>
                    <div class="no-notices">
                        <i class="fa-solid fa-inbox" aria-hidden="true"></i>
                        <h4>Sin notificaciones</h4>
                        <p>No tienes notificaciones en este momento.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- CALIFICACIONES (agrupadas como la boleta, por nivel) -->
            <?php
            // Renderiza una tabla de notas (desktop + cards móvil) para un grupo de materias.
            // $useLetters: true para materias de conducta (E/VG/G/S/N).
            $np = $grades_data['periodos'] ?? 5;
            $plabels = $grades_data['period_labels'] ?? ['I','II','III','IV','V'];

            // Promedio general de un conjunto de materias numéricas = promedio de sus
            // promedios finales (solo materias con al menos una nota). null si ninguna.
            $generalAvg = function (array $groups): ?string {
                $finals = [];
                foreach ($groups as $subjects) {
                    foreach ($subjects as $s) {
                        $f = sg_avg_numeric($s['marks']);
                        if ($f !== '') $finals[] = (float) $f;
                    }
                }
                return count($finals) ? number_format(array_sum($finals) / count($finals), 1) : null;
            };
            $prom_es = $generalAvg([$grades_data['es_report']]);              // extras no promedian
            $prom_en = $generalAvg([$grades_data['en_report']]);             // behavior no promedia

            $renderGradeGroup = function (array $subjects, bool $useLetters, string $tableLabel) use ($np, $plabels) {
                ?>
                <div class="grades-table-wrapper">
                    <table class="grades-table" aria-label="<?php echo htmlentities($tableLabel); ?>">
                        <thead>
                            <tr>
                                <th scope="col">Asignatura</th>
                                <?php for ($i = 1; $i <= $np; $i++): ?>
                                    <th scope="col" title="<?php echo $i; ?>° periodo"><?php echo htmlentities($plabels[$i-1]); ?></th>
                                <?php endfor; ?>
                                <th scope="col">Prom.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects as $g):
                                $final = $useLetters ? sg_mode_letter($g['letters']) : sg_avg_numeric($g['marks']);
                            ?>
                                <tr class="grade-item-row">
                                    <td><?php echo htmlentities($g['SubjectName']); ?></td>
                                    <?php for ($i = 1; $i <= $np; $i++):
                                        $val = $useLetters ? $g['letters'][$i] : $g['marks'][$i];
                                        $empty = ($val === null || $val === '');
                                    ?>
                                        <td<?php echo $empty ? ' class="grade-empty"' : ''; ?>><?php echo $empty ? '—' : htmlentities((string)$val); ?></td>
                                    <?php endfor; ?>
                                    <td><?php echo $final !== '' ? htmlentities($final) : '—'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Cards Mobile -->
                <div class="mobile-grades-list">
                    <?php foreach ($subjects as $g): ?>
                        <div class="mobile-grade-card">
                            <div class="mobile-subject"><?php echo htmlentities($g['SubjectName']); ?></div>
                            <div class="mobile-grades-row">
                                <?php for ($i = 1; $i <= $np; $i++):
                                    $val = $useLetters ? $g['letters'][$i] : $g['marks'][$i];
                                ?>
                                    <div class="mobile-grade-unit">
                                        <span><?php echo htmlentities($plabels[$i-1]); ?></span>
                                        <span><?php echo ($val !== null && $val !== '') ? htmlentities((string)$val) : '—'; ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php
            };

            // Encabezado de subgrupo dentro de una sección (ej. "Report Card", "Conducta").
            $groupSubheader = function (string $text) {
                echo '<p style="margin:20px 0 8px; font-weight:800; font-size:.8rem; letter-spacing:.6px;'
                   . ' text-transform:uppercase; color:var(--acento); opacity:.7;">' . htmlentities($text) . '</p>';
            };

            $has_es = !empty($grades_data['es_report']) || !empty($grades_data['es_extra']);
            $has_en = !empty($grades_data['en_report']) || !empty($grades_data['en_behavior']);
            // En maternal TODAS las materias se califican con letra (no número), así que
            // las tablas de español e inglés report card se renderizan con letras.
            $isMaternal = (($grades_data['level'] ?? '') === 'maternal');
            ?>

            <!-- ===== ESPAÑOL ===== -->
            <?php if ($has_es): ?>
            <div class="grades-section">
                <div class="grades-header">
                    <h3 class="section-title">Calificaciones — Español</h3>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <?php if ($prom_es !== null): ?>
                            <span class="promedio-badge">Promedio general: <?php echo $prom_es; ?></span>
                        <?php endif; ?>
                        <button class="grades-toggle-btn" data-target="grades-collapsible-es"
                                aria-expanded="false" aria-controls="grades-collapsible-es">
                            <span class="toggle-label">Ver materias</span>
                            <i class="fa-solid fa-chevron-down chevron" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="grades-collapsible" id="grades-collapsible-es">
                    <?php
                    if (!empty($grades_data['es_report'])) {
                        if (!empty($grades_data['es_extra'])) $groupSubheader('Asignaturas');
                        $renderGradeGroup($grades_data['es_report'], $isMaternal, 'Asignaturas Español');
                    }
                    if (!empty($grades_data['es_extra'])) {
                        $groupSubheader('Asignaturas adicionales');
                        // Las asignaturas extra (Trabajo en plataforma, Conducta) van SIEMPRE en
                        // letra (escala E/MB/B/S/I), en cualquier nivel — no en número.
                        $renderGradeGroup($grades_data['es_extra'], true, 'Asignaturas adicionales');
                    }
                    ?>

                    <?php if ($grades_data['has_rubros']): ?>
                    <!-- RUBROS OFICIALES -->
                    <?php $groupSubheader('Rubros oficiales'); ?>
                    <div class="grades-table-wrapper">
                        <table class="grades-table" aria-label="Rubros oficiales">
                            <thead>
                                <tr>
                                    <th scope="col">Periodo</th>
                                    <?php foreach ($grades_data['rubros'] as $nombre => $r): ?>
                                        <th scope="col"><?php echo htmlentities($nombre); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rubroFinals = [];
                                for ($i = 1; $i <= $np; $i++): ?>
                                    <tr class="grade-item-row">
                                        <td><?php echo htmlentities($plabels[$i-1]); ?></td>
                                        <?php foreach ($grades_data['rubros'] as $nombre => $r):
                                            $avg = sg_rubro_period_avg($r['subjects'], $i);
                                            if ($avg !== '') $rubroFinals[$nombre][] = $avg;
                                        ?>
                                            <td><?php echo $avg !== '' ? htmlentities($avg) : '—'; ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endfor; ?>
                                <tr class="grade-item-row" style="font-weight:800;">
                                    <td>Promedio</td>
                                    <?php foreach ($grades_data['rubros'] as $nombre => $r):
                                        $a = $rubroFinals[$nombre] ?? [];
                                        echo '<td>' . (count($a) ? round(array_sum($a)/count($a)) : '—') . '</td>';
                                    endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ===== INGLÉS ===== -->
            <?php if ($has_en): ?>
            <div class="grades-section">
                <div class="grades-header">
                    <h3 class="section-title">Calificaciones — Inglés</h3>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <?php if ($prom_en !== null): ?>
                            <span class="promedio-badge">Promedio general: <?php echo $prom_en; ?></span>
                        <?php endif; ?>
                        <button class="grades-toggle-btn" data-target="grades-collapsible-en"
                                aria-expanded="false" aria-controls="grades-collapsible-en">
                            <span class="toggle-label">Ver materias</span>
                            <i class="fa-solid fa-chevron-down chevron" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="grades-collapsible" id="grades-collapsible-en">
                    <?php
                    if (!empty($grades_data['en_report'])) {
                        if (!empty($grades_data['en_behavior'])) $groupSubheader('Report Card');
                        $renderGradeGroup($grades_data['en_report'], $isMaternal, 'Report Card');
                    }
                    if (!empty($grades_data['en_behavior'])) {
                        $groupSubheader('Behavior Observations (E / VG / G / S / N)');
                        $renderGradeGroup($grades_data['en_behavior'], true, 'Behavior Observations');
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- BOTÓN ÚNICO DE BOLETA COMPLETA -->
            <!--
                OCULTO (decisión 2026-07-06): el tutor NO puede generar boletas; solo el admin.
                El código y generate-student-pdf.php se conservan intactos por si se reactiva.
                Para reactivarlo, cambia `false` por la condición original `($has_es || $has_en)`.
            -->
            <?php if (false && ($has_es || $has_en)): ?>
            <div class="action-bar" style="justify-content:center; padding-bottom: 8px;">
                <a href="generate-student-pdf.php?student_id=<?php echo $selected_student_id; ?>"
                   target="_blank" class="btn-pdf btn-pdf-large" aria-label="Descargar boleta completa en PDF">
                    <i class="fas fa-file-pdf" aria-hidden="true"></i> Descargar Boleta Completa
                </a>
            </div>
            <?php endif; ?>

        </div><!-- /.page-content -->

    <?php else: ?>

        <div class="page-content">
            <div class="no-students">
                <i class="fas fa-inbox" aria-hidden="true"></i>
                <h3>Sin estudiantes asignados</h3>
                <p>Contacta al administrador del sistema para obtener acceso a los registros de tus hijos.</p>
            </div>
        </div>

    <?php endif; ?>

    <!-- MODAL DETALLES DE NOTIFICACIÓN -->
    <div id="noticeModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalNoticeTitle">
        <div class="modal-box">
            <div class="modal-header">
                <h2 id="modalNoticeTitle"></h2>
                <button class="close-modal" onclick="closeNoticeModal()" aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-body" id="modalNoticeBody"></div>
            <div class="modal-meta" id="modalNoticeMeta"></div>
            <div class="modal-footer">
                <button class="btn-pdf" onclick="closeNoticeModal()" style="background:var(--acento);">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <!-- TOAST -->
    <div id="toast" role="status" aria-live="polite">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        <span id="toastMsg"></span>
    </div>

    <!-- FOOTER -->
    <footer class="site-footer">
        <p>&copy; 2026 Instituto Panamericano de Tampico. Todos los derechos reservados.</p>
        <p><small>Portal de Tutores v1.0</small></p>
        <div class="footer-line"></div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        /* =============================================================
           TOAST
        ============================================================= */
        function showToast(msg, type) {
            var t = document.getElementById('toast');
            var m = document.getElementById('toastMsg');
            t.className = 'show ' + (type || 'success');
            m.textContent = msg;
            clearTimeout(t._timer);
            t._timer = setTimeout(function () { t.className = ''; }, 3200);
        }

        /* =============================================================
           MODAL NOTIFICACIONES
        ============================================================= */
        var modal = document.getElementById('noticeModal');

        function openModal(title, details, date) {
            document.getElementById('modalNoticeTitle').textContent = title;
            document.getElementById('modalNoticeBody').textContent  = details;

            var d   = new Date(date);
            var fmt = d.toLocaleString('es-MX', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });

            document.getElementById('modalNoticeMeta').innerHTML =
                '<div class="modal-meta-row">' +
                    '<strong>Publicado:</strong><span>' + fmt + '</span>' +
                '</div>';

            modal.style.display = 'block';
        }

        function closeNoticeModal() {
            modal.style.display = 'none';
        }

        // Exponer para el botón inline del modal
        window.closeNoticeModal = closeNoticeModal;

        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeNoticeModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeNoticeModal();
        });

        /* =============================================================
           MARCAR COMO LEÍDO (XHR)
        ============================================================= */
        function markAsRead(btn) {
            var nsi = btn.dataset.nsi;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando…';

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'mark-notice-viewed.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function () {
                if (xhr.status === 200) {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            var item   = btn.closest('.notice-item');
                            var status = item.querySelector('.notice-status');
                            item.classList.remove('unread');
                            status.textContent = 'Leído';
                            status.className   = 'notice-status viewed';
                            btn.style.display  = 'none';

                            var badge = document.querySelector('.notices-badge');
                            if (badge) {
                                var n = parseInt(badge.textContent) - 1;
                                if (n > 0) { badge.textContent = n; }
                                else       { badge.style.display = 'none'; }
                            }
                            showToast('Notificación marcada como leída', 'success');
                        }
                    } catch (err) { showToast('Respuesta inesperada del servidor', 'error'); }
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-check"></i> Marcar leído';
                    showToast('Error al conectar con el servidor', 'error');
                }
            };

            xhr.onerror = function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Marcar leído';
                showToast('Error de conexión', 'error');
            };

            xhr.send('notice_student_id=' + nsi);
        }

        /* =============================================================
           ARCHIVAR ANUNCIO
        ============================================================= */
        var archivedSection = document.getElementById('archivedSection');
        var archivedList    = document.getElementById('archivedList');
        var archivedLabel   = document.getElementById('archivedLabel');
        var archivedToggle  = document.getElementById('archivedToggle');
        var archivedCount   = 0;

        if (archivedToggle) {
            archivedToggle.addEventListener('click', function () {
                var open = archivedList.classList.toggle('open');
                archivedToggle.classList.toggle('open', open);
                archivedToggle.setAttribute('aria-expanded', open);
            });
        }

        function archiveNotice(item) {
            // Clonar el card, añadir clase archived y moverlo a la lista de archivados
            var clone = item.cloneNode(true);
            clone.classList.add('archived');
            clone.classList.remove('unread');

            // Quitar el botón archivar del clon para no archivar dos veces
            var archBtn = clone.querySelector('[data-action="archive"]');
            if (archBtn) archBtn.remove();

            archivedList.appendChild(clone);
            archivedCount++;

            // Actualizar label y mostrar sección
            archivedLabel.textContent = 'Anteriores (' + archivedCount + ')';
            archivedSection.style.display = 'block';

            // Animar y eliminar el original
            item.style.transition = 'opacity .3s, transform .3s';
            item.style.opacity    = '0';
            item.style.transform  = 'translateX(20px)';
            setTimeout(function () { item.remove(); }, 300);

            showToast('Anuncio movido a Anteriores', 'success');
        }

        /* =============================================================
           EVENT DELEGATION — un solo listener para todos los botones
        ============================================================= */
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-action]');
            if (!btn) return;

            var action = btn.dataset.action;
            var item   = btn.closest('.notice-item');

            if (action === 'view-notice') {
                openModal(
                    item.dataset.title,
                    item.dataset.details,
                    item.dataset.date
                );
            } else if (action === 'mark-read') {
                markAsRead(btn);
            } else if (action === 'archive') {
                archiveNotice(item);
            }
        });

        /* =============================================================
           TOGGLE COLAPSABLE DE CALIFICACIONES (solo activo en móvil)
        ============================================================= */
        document.querySelectorAll('.grades-toggle-btn').forEach(function (btn) {
            // Estado inicial: abierto (las calificaciones se muestran por defecto).
            btn.classList.add('open');
            btn.setAttribute('aria-expanded', true);
            var lbl0 = btn.querySelector('.toggle-label');
            if (lbl0) lbl0.textContent = 'Ocultar';

            btn.addEventListener('click', function () {
                var targetId   = btn.dataset.target;
                var collapsible = document.getElementById(targetId);
                if (!collapsible) return;

                var isCollapsed = collapsible.classList.toggle('collapsed');
                var isOpen = !isCollapsed;
                btn.classList.toggle('open', isOpen);
                btn.setAttribute('aria-expanded', isOpen);
                var lbl = btn.querySelector('.toggle-label');
                if (lbl) lbl.textContent = isOpen ? 'Ocultar' : 'Ver materias';
            });
        });

        /* =============================================================
           CARRUSEL DE ESTUDIANTES
        ============================================================= */
        var students  = <?php echo json_encode(array_map(fn($s) => ['id' => $s->StudentId, 'name' => $s->StudentName], $students)); ?>;
        var currentId = <?php echo json_encode($selected_student_id); ?>;
        var idx       = students.findIndex(function (s) { return s.id == currentId; });

        var prev = document.getElementById('prevStudent');
        var next = document.getElementById('nextStudent');

        if (students.length > 1) {
            if (prev) {
                prev.addEventListener('click', function (e) {
                    e.preventDefault();
                    idx = (idx - 1 + students.length) % students.length;
                    window.location.href = '?student_id=' + students[idx].id;
                });
            }
            if (next) {
                next.addEventListener('click', function (e) {
                    e.preventDefault();
                    idx = (idx + 1) % students.length;
                    window.location.href = '?student_id=' + students[idx].id;
                });
            }
        }

    }); // DOMContentLoaded
    </script>

</body>
</html>
