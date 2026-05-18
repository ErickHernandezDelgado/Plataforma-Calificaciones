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
$student_grades = [];

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

        // Obtener calificaciones del estudiante
        $sql = "SELECT 
                    subj.SubjectName,
                    subj.id AS SubjectId,
                    MAX(CASE WHEN r.term = 1 THEN r.marks END) AS term1,
                    MAX(CASE WHEN r.term = 2 THEN r.marks END) AS term2,
                    MAX(CASE WHEN r.term = 3 THEN r.marks END) AS term3
                FROM tblresult r
                JOIN tblsubjects subj ON r.SubjectId = subj.id
                WHERE r.StudentId = :sid
                GROUP BY subj.id, subj.SubjectName
                ORDER BY subj.SubjectName ASC";
        $query = $dbh->prepare($sql);
        $query->bindParam(':sid', $selected_student_id, PDO::PARAM_INT);
        $query->execute();
        $student_grades = $query->fetchAll(PDO::FETCH_OBJ);
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

        <!-- SECCIÓN DE CALIFICACIONES -->
        <div class="grades-section">
            <h3>CALIFICACIÓN</h3>

            <?php if (count($student_grades) > 0): ?>
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

                            <?php foreach ($student_grades as $grade):

                                $terms = array_filter([
                                    $grade->term1,
                                    $grade->term2,
                                    $grade->term3
                                ]);

                                $avg = count($terms) > 0
                                    ? array_sum($terms) / count($terms)
                                    : 0;
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

                    <?php foreach ($student_grades as $grade):

                        $terms = array_filter([
                            $grade->term1,
                            $grade->term2,
                            $grade->term3
                        ]);

                        $avg = count($terms) > 0
                            ? array_sum($terms) / count($terms)
                            : 0;
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
                    <a href="generate-report-cards.php?student_id=<?php echo $selected_student_id; ?>"
                        target="_blank"
                        class="btn-primary">
                        <i class="fas fa-file-pdf"></i> imprimir boleta
                    </a>
                    <div style="font-weight: 800" class="promedio-container">
                        <p><?php echo number_format(array_sum(array_column($student_grades, 'avg')) / count($student_grades), 1); ?></p>
                    </div>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--color-acento);">No hay calificaciones registradas aún.</p>
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