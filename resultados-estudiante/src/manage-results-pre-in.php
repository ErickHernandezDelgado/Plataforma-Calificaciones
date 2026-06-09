<?php 
/**
 * manage-results-pre-in.php
 * Gestión de calificaciones de Pre-primaria/Kinder Inglés
 * 
 * Permite al maestro de inglés:
 * - Seleccionar clase de pre-primaria
 * - Ver estudiantes
 * - Editar calificaciones para 5 bimestres
 */

// Validación de sesión
include(__DIR__ . '/includes/check-login.php');

// Procesar actualización de calificaciones
$msg = "";
$error = "";

if (isset($_POST['update_marks'])) {
    $mark_ids = $_POST['mark_id'] ?? [];
    $mark_values = $_POST['mark_value'] ?? [];
    
    try {
        $dbh->beginTransaction();
        
        foreach ($mark_ids as $idx => $mark_id) {
            $mark_value = intval($mark_values[$idx] ?? 0);
            $mark_id = intval($mark_id);
            
            // Validar que la calificación esté entre 0 y 100
            if ($mark_value < 0 || $mark_value > 100) {
                $mark_value = max(0, min(100, $mark_value));
            }
            
            $sql = "UPDATE tblresult SET marks = :marks WHERE id = :id";
            $stmt = $dbh->prepare($sql);
            $stmt->execute([':marks' => $mark_value, ':id' => $mark_id]);
        }
        
        $dbh->commit();
        $msg = "✅ Calificaciones actualizadas correctamente.";
    } catch (Exception $e) {
        $dbh->rollBack();
        $error = "❌ Error al actualizar: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IPT | Gestionar Resultados Pre-primaria Inglés</title>
    <link rel="stylesheet" href="css/bootstrap.min.css" media="screen">
    <link rel="stylesheet" href="css/font-awesome.min.css" media="screen">
    <link rel="stylesheet" href="css/main.css" media="screen">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        * { box-sizing: border-box; }
        
        .page-title-div { margin: 20px 0; }
        .page-title-div h2 { margin: 0; color: #333; font-weight: 700; }
        
        .breadcrumb-div { margin: 15px 0; }
        
        .filter-panel { 
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f5f9 100%);
            padding: 25px;
            border-radius: 12px; 
            margin-bottom: 25px; 
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        
        .filter-panel .form-group {
            margin-bottom: 15px;
        }
        
        .filter-panel label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px; 
            border: 1px solid #e2e8f0;
            font-size: 14px;
            background-color: white;
            color: #1e293b;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            outline: none;
        }
        
        .result-panel { 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 25px; 
            margin-top: 25px;
            overflow-x: auto;
        }
        
        .result-panel h4 {
            color: #1e293b;
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        table.grades-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            font-size: 13px;
        }
        
        table.grades-table thead {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }
        
        table.grades-table th {
            padding: 15px 10px;
            text-align: center;
            font-weight: 600;
            border: 1px solid #e2e8f0;
        }
        
        table.grades-table td {
            padding: 12px 10px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        
        table.grades-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        table.grades-table tbody tr:hover {
            background-color: #f1f5f9;
        }
        
        table.grades-table .student-name {
            text-align: left;
            font-weight: 500;
            color: #1e293b;
        }
        
        .mark-input {
            width: 60px;
            padding: 8px 6px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            text-align: center;
            font-size: 13px;
        }
        
        .mark-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
            outline: none;
        }
        
        .btn-update {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }
        
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }
        
        .alert-success {
            background-color: #d1fae5;
            border-color: #10b981;
            color: #065f46;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            border-color: #ef4444;
            color: #7f1d1d;
        }
        
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .info-box strong {
            color: #1e40af;
        }
        
        @media (max-width: 768px) {
            table.grades-table { font-size: 12px; }
            table.grades-table th, table.grades-table td { padding: 8px 5px; }
            .mark-input { width: 50px; padding: 6px 4px; }
        }
    </style>
</head>
<body>
    <!-- Incluye la barra superior -->
    <?php include('includes/topbar.php'); ?>

    <div class="content-wrapper">
        <div class="content-container">
            <!-- Incluye la barra lateral izquierda -->
            <?php include('includes/leftbar.php'); ?>

            <div class="main-page">
                <div class="container-fluid">
                    <!-- Título principal -->
                    <div class="row page-title-div">
                        <div class="col-md-6">
                            <h2 class="title">Gestionar Resultados - Pre-primaria Inglés</h2>
                        </div>
                    </div>

                    <!-- Breadcrumb -->
                    <div class="row breadcrumb-div">
                        <div class="col-md-12">
                            <ul class="breadcrumb">
                                <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                                <li>Resultados</li>
                                <li class="active">Pre-primaria Inglés</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Mensajes de éxito/error -->
                <div class="container-fluid">
                    <?php if (!empty($msg)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                </div>

                <!-- PANEL DE FILTROS -->
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="filter-panel">
                                <form method="GET" id="filterForm">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label for="classid"><strong>Seleccionar Clase (Pre-primaria):</strong></label>
                                                <select name="classid" id="classid" class="form-control" required onchange="document.getElementById('filterForm').submit();">
                                                    <option value="">-- Seleccionar Clase --</option>
                                                    <?php
                                                    // Obtener clases de pre-primaria/infantil
                                                    $sql = "SELECT id, ClassName, Section, educationLevel
                                                            FROM tblclasses 
                                                            WHERE educationLevel = 'infantil'
                                                            ORDER BY ClassName, Section";
                                                    $query = $dbh->prepare($sql);
                                                    $query->execute();
                                                    $classes = $query->fetchAll(PDO::FETCH_ASSOC);
                                                    
                                                    foreach ($classes as $cls) {
                                                        $selected = (isset($_GET['classid']) && $_GET['classid'] == $cls['id']) ? 'selected' : '';
                                                        echo '<option value="' . $cls['id'] . '" ' . $selected . '>' . htmlentities($cls['ClassName'] . ' - ' . $cls['Section']) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PANEL DE RESULTADOS -->
                <?php if (isset($_GET['classid']) && !empty($_GET['classid'])): 
                    $classId = intval($_GET['classid']);
                    
                    // Obtener información de la clase
                    $sql = "SELECT * FROM tblclasses WHERE id = :id";
                    $query = $dbh->prepare($sql);
                    $query->execute([':id' => $classId]);
                    $classInfo = $query->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$classInfo) {
                        echo '<div class="container-fluid"><div class="alert alert-danger">Clase no encontrada.</div></div>';
                    } else {
                        // Obtener estudiantes de la clase
                        $sql = "SELECT StudentId, StudentName, RollId FROM tblstudents 
                                WHERE ClassId = :classid AND Status = 1
                                ORDER BY StudentName";
                        $query = $dbh->prepare($sql);
                        $query->execute([':classid' => $classId]);
                        $students = $query->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (empty($students)) {
                            echo '<div class="container-fluid"><div class="alert alert-danger">No hay estudiantes en esta clase.</div></div>';
                        } else {
                ?>
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="result-panel">
                                <h4>
                                    <i class="fa fa-book"></i> 
                                    Calificaciones - <?= htmlentities($classInfo['ClassName'] . ' ' . $classInfo['Section']) ?>
                                </h4>
                                
                                <div class="info-box">
                                    <strong>📚 Materia:</strong> Inglés | <strong>📊 Períodos:</strong> 5 Bimestres | <strong>👥 Estudiantes:</strong> <?= count($students) ?>
                                </div>

                                <form method="POST" id="gradesForm">
                                    <table class="grades-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Nombre del Estudiante</th>
                                                <th>I Bimestre</th>
                                                <th>II Bimestre</th>
                                                <th>III Bimestre</th>
                                                <th>IV Bimestre</th>
                                                <th>V Bimestre</th>
                                                <th>Promedio</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $cnt = 1;
                                            foreach ($students as $student):
                                                $studentId = $student['StudentId'];
                                                
                                                // Obtener calificaciones del estudiante para materias de inglés
                                                // Pre-primaria usa 5 bimestres (terms 1-5)
                                                $sql = "SELECT r.id, r.marks, r.term, s.SubjectName
                                                        FROM tblresult r
                                                        JOIN tblsubjects s ON r.SubjectId = s.id
                                                        WHERE r.StudentId = :studentid 
                                                          AND r.ClassId = :classid
                                                          AND s.Language = 'en'
                                                        ORDER BY r.term, s.SubjectName";
                                                $query = $dbh->prepare($sql);
                                                $query->execute([':studentid' => $studentId, ':classid' => $classId]);
                                                $grades = $query->fetchAll(PDO::FETCH_ASSOC);
                                                
                                                // Organizar por término
                                                $marks = [];
                                                foreach ($grades as $grade) {
                                                    if (!isset($marks[$grade['term']])) {
                                                        $marks[$grade['term']] = [];
                                                    }
                                                    $marks[$grade['term']][] = $grade;
                                                }
                                                
                                                // Calcular promedio de todos los términos
                                                $all_marks = array_map(function($g) { return $g['marks']; }, $grades);
                                                $avg = !empty($all_marks) ? round(array_sum($all_marks) / count($all_marks)) : '-';
                                            ?>
                                            <tr>
                                                <td><?= $cnt++ ?></td>
                                                <td class="student-name"><?= htmlentities($student['StudentName']) ?></td>
                                                <?php for ($term = 1; $term <= 5; $term++): 
                                                    $mark_id = null;
                                                    $mark_value = '';
                                                    
                                                    if (isset($marks[$term]) && !empty($marks[$term])) {
                                                        $mark_id = $marks[$term][0]['id'];
                                                        $mark_value = $marks[$term][0]['marks'];
                                                    }
                                                ?>
                                                    <td>
                                                        <?php if ($mark_id): ?>
                                                            <input type="hidden" name="mark_id[]" value="<?= $mark_id ?>">
                                                            <input type="number" name="mark_value[]" class="mark-input" value="<?= $mark_value ?>" min="0" max="100" placeholder="0-100">
                                                        <?php else: ?>
                                                            <span style="color:#ccc;">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endfor; ?>
                                                <td style="font-weight: 600; color: #1e293b;"><?= $avg ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <button type="submit" name="update_marks" class="btn-update">
                                        <i class="fa fa-save"></i> Guardar Calificaciones
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php 
                        }
                    }
                ?>
                <?php endif; ?>

            </div> <!-- /.main-page -->
        </div> <!-- /.content-container -->
    </div> <!-- /.content-wrapper -->

    <!-- Incluye pie de página -->
    <?php include('includes/footer.php'); ?>

    <script src="js/jquery-2.2.4.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>
