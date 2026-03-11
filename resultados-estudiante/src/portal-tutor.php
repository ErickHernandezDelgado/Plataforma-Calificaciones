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
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #238D15;
            --secondary-color: #FFB81C;
            --light-bg: #F5F7FA;
            --border-color: #E0E0E0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }

        .navbar {
            background: linear-gradient(90deg, var(--primary-color) 0%, #1a6b0f 100%);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            border-bottom: 4px solid var(--secondary-color);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.3em;
            color: white !important;
        }

        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: var(--secondary-color) !important;
        }

        .container-main {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            overflow: hidden;
            margin-bottom: 40px;
        }

        .header-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1a6b0f 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header-section h1 {
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header-section p {
            font-size: 1.1em;
            opacity: 0.95;
        }

        .student-selector {
            background: var(--light-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 20px;
        }

        .student-card {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            margin: 10px 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .student-card:hover,
        .student-card.active {
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(35, 141, 21, 0.2);
            transform: translateY(-2px);
        }

        .student-card.active {
            background: #e8f5e9;
            font-weight: 600;
        }

        .student-card h5 {
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .student-card .small {
            color: #666;
        }

        .content-section {
            padding: 30px;
        }

        .student-info-box {
            background: var(--light-bg);
            border-left: 4px solid var(--primary-color);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .student-info-box h4 {
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #333;
        }

        .info-value {
            color: #666;
        }

        .grades-section {
            margin-top: 30px;
        }

        .grades-section h4 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 700;
        }

        .table {
            border-collapse: collapse;
            width: 100%;
        }

        .table thead {
            background: var(--light-bg);
            border-top: 2px solid var(--primary-color);
        }

        .table th {
            color: var(--primary-color);
            font-weight: 600;
            padding: 15px;
            text-align: left;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .table tbody tr:hover {
            background: #f9f9f9;
        }

        .grade {
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 4px;
            text-align: center;
            min-width: 50px;
        }

        .grade.excellent {
            background: #d4edda;
            color: #155724;
        }

        .grade.good {
            background: #d1ecf1;
            color: #0c5460;
        }

        .grade.fair {
            background: #fff3cd;
            color: #856404;
        }

        .grade.low {
            background: #f8d7da;
            color: #721c24;
        }

        .no-grades {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn-custom {
            padding: 12px 24px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-download {
            background: var(--primary-color);
            color: white;
            border: none;
        }

        .btn-download:hover {
            background: #1a6b0f;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(35, 141, 21, 0.3);
        }

        .btn-logout {
            background: #dc3545;
            color: white;
            border: none;
        }

        .btn-logout:hover {
            background: #a71d2a;
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: white;
            background: rgba(0,0,0,0.1);
            margin-top: 40px;
            font-size: 0.9em;
        }

        .no-students {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-students i {
            font-size: 3em;
            color: #ddd;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .header-section h1 {
                font-size: 1.5em;
            }

            .student-card {
                flex: 1 1 100%;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-custom {
                width: 100%;
                justify-content: center;
            }

            .table {
                font-size: 0.9em;
            }

            .table th, .table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fas fa-graduation-cap"></i> Portal de Tutores
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <span class="d-flex align-items-center me-3" style="color: white;">
                <i class="fas fa-user-circle me-2"></i>
                <?php echo htmlentities($_SESSION['alogin']); ?>
            </span>
            <a href="logout.php" class="btn btn-logout btn-custom">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="container-main">
        <!-- Header -->
        <div class="header-section">
            <h1>Bienvenido al Portal de Tutores</h1>
            <p>Instituto Panamericano de Tampico</p>
        </div>

        <!-- Selector de Estudiante -->
        <?php if (count($students) > 0): ?>
            <div class="student-selector">
                <h5 style="margin-bottom: 15px; color: #333;">Selecciona a tu hijo/a:</h5>
                <div style="display: flex; flex-wrap: wrap;">
                    <?php foreach ($students as $student): ?>
                        <a href="?student_id=<?php echo $student->StudentId; ?>" style="flex: 0 1 calc(50% - 10px);">
                            <div class="student-card <?php echo ($student->StudentId == $selected_student_id) ? 'active' : ''; ?>">
                                <h5><i class="fas fa-user-tie"></i> <?php echo htmlentities($student->StudentName); ?></h5>
                                <small><?php echo htmlentities($student->ClassName); ?> - Sección <?php echo htmlentities($student->Section); ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Contenido Principal -->
            <?php if ($selected_student): ?>
                <div class="content-section">
                    <!-- Información del Estudiante -->
                    <div class="student-info-box">
                        <h4><i class="fas fa-id-card"></i> Información del Estudiante</h4>
                        <div class="info-row">
                            <span class="info-label">Nombre:</span>
                            <span class="info-value"><?php echo htmlentities($selected_student->StudentName); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Matrícula (Roll ID):</span>
                            <span class="info-value"><?php echo htmlentities($selected_student->RollId); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Grado:</span>
                            <span class="info-value"><?php echo htmlentities($selected_student->ClassName); ?> - Sección <?php echo htmlentities($selected_student->Section); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Correo Electrónico:</span>
                            <span class="info-value"><?php echo htmlentities($selected_student->StudentEmail); ?></span>
                        </div>
                    </div>

                    <!-- Calificaciones -->
                    <div class="grades-section">
                        <h4><i class="fas fa-chart-bar"></i> Calificaciones Actuales</h4>
                        
                        <?php if (count($student_grades) > 0): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Materia</th>
                                        <th>I Período</th>
                                        <th>II Período</th>
                                        <th>III Período</th>
                                        <th>Promedio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_avg = 0;
                                    $subject_count = 0;
                                    
                                    foreach ($student_grades as $grade): 
                                        $term1 = $grade->term1 ?? 0;
                                        $term2 = $grade->term2 ?? 0;
                                        $term3 = $grade->term3 ?? 0;
                                        $avg = ($term1 + $term2 + $term3) / 3;
                                        $total_avg += $avg;
                                        $subject_count++;
                                        
                                        // Determinar clase según calificación
                                        $class = '';
                                        if ($avg >= 90) $class = 'excellent';
                                        elseif ($avg >= 80) $class = 'good';
                                        elseif ($avg >= 70) $class = 'fair';
                                        else $class = 'low';
                                    ?>
                                        <tr>
                                            <td><strong><?php echo htmlentities($grade->SubjectName); ?></strong></td>
                                            <td>
                                                <?php if ($term1): ?>
                                                    <span class="grade <?php echo ($term1 >= 80) ? 'good' : 'fair'; ?>">
                                                        <?php echo number_format($term1, 1); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #ccc;">--</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($term2): ?>
                                                    <span class="grade <?php echo ($term2 >= 80) ? 'good' : 'fair'; ?>">
                                                        <?php echo number_format($term2, 1); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #ccc;">--</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($term3): ?>
                                                    <span class="grade <?php echo ($term3 >= 80) ? 'good' : 'fair'; ?>">
                                                        <?php echo number_format($term3, 1); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #ccc;">--</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="grade <?php echo $class; ?>">
                                                    <?php echo number_format($avg, 1); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Promedio General -->
                                    <tr style="background: var(--light-bg); font-weight: 700;">
                                        <td colspan="4" style="text-align: right;">Promedio General:</td>
                                        <td>
                                            <span class="grade <?php 
                                                $general_avg = $total_avg / $subject_count;
                                                if ($general_avg >= 90) echo 'excellent';
                                                elseif ($general_avg >= 80) echo 'good';
                                                elseif ($general_avg >= 70) echo 'fair';
                                                else echo 'low';
                                            ?>">
                                                <?php echo number_format($general_avg, 1); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-grades">
                                <i class="fas fa-file-slash"></i>
                                <p>No hay calificaciones registradas aún.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="action-buttons">
                        <a href="generate-report-cards.php?classid=<?php echo $selected_student->ClassId; ?>" 
                           target="_blank" 
                           class="btn-custom btn-download">
                            <i class="fas fa-file-pdf"></i> Descargar Boleta PDF
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-students">
                <i class="fas fa-inbox"></i>
                <h4>No hay estudiantes asignados</h4>
                <p>Contacta al administrador del sistema para obtener acceso a los registros de tus hijos.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Footer -->
<div class="footer">
    <p>&copy; 2026 Instituto Panamericano de Tampico. Todos los derechos reservados.</p>
    <small>Portal de Tutores v1.0</small>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
