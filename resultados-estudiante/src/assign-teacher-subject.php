<?php
/**
 * assign-teacher-subject.php - Asignar maestros a materias y clases
 * 
 * Permite al administrador:
 * - Seleccionar un maestro
 * - Seleccionar materia(s) y clase(s)
 * - Ver asignaciones existentes
 * - Eliminar asignaciones
 */

session_start();
error_reporting(0);
include(__DIR__ . '/includes/config.php');

// Verificar autenticación
if (!isset($_SESSION['alogin']) || !in_array($_SESSION['role'], ['admin', 'director'])) {
    header("Location: index.php");
    exit;
}

// Procesamiento de POST (crear/eliminar asignaciones)
$msg = "";
$error = "";
$teacher_id_param = intval($_GET['teacherid'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    
    if ($action === 'assign') {
        // Crear nueva asignación
        $teacher_id = intval($_POST['teacher_id'] ?? 0);
        $subject_id = intval($_POST['subject_id'] ?? 0);
        $class_id = intval($_POST['class_id'] ?? 0);
        
        if (!$teacher_id || !$subject_id || !$class_id) {
            $error = "Por favor selecciona maestro, materia y clase.";
        } else {
            // VALIDACIÓN FK 1: Verificar que teacher existe
            $sql_check_teacher = "SELECT Id FROM tblteachers WHERE Id = :tid AND Status = 1";
            $check_teacher = $dbh->prepare($sql_check_teacher);
            $check_teacher->execute([':tid' => $teacher_id]);
            
            if ($check_teacher->rowCount() == 0) {
                $error = "El maestro seleccionado no existe o está inactivo.";
            } else {
                // VALIDACIÓN FK 2: Verificar que subject existe
                $sql_check_subject = "SELECT id FROM tblsubjects WHERE id = :sid";
                $check_subject = $dbh->prepare($sql_check_subject);
                $check_subject->execute([':sid' => $subject_id]);
                
                if ($check_subject->rowCount() == 0) {
                    $error = "La materia seleccionada no existe.";
                } else {
                    // VALIDACIÓN FK 3: Verificar que class existe
                    $sql_check_class = "SELECT id FROM tblclasses WHERE id = :cid";
                    $check_class = $dbh->prepare($sql_check_class);
                    $check_class->execute([':cid' => $class_id]);
                    
                    if ($check_class->rowCount() == 0) {
                        $error = "El grupo seleccionado no existe.";
                    } else {
                        // VALIDACIÓN FK 4: CRÍTICA - Verificar que la materia esté asignada al grupo
                        $sql_check_combination = "SELECT id FROM tblsubjectcombination 
                                                 WHERE SubjectId = :sid AND ClassId = :cid AND status = 1";
                        $check_combination = $dbh->prepare($sql_check_combination);
                        $check_combination->execute([':sid' => $subject_id, ':cid' => $class_id]);
                        
                        if ($check_combination->rowCount() == 0) {
                            $error = "Error: La materia NO está asignada a este grupo. "
                                   . "Primero asigna la materia al grupo en Gestionar Materias.";
                        } else {
                            // Verificar que no existe asignación duplicada
                            $sql = "SELECT Id FROM tblteacher_subject 
                                    WHERE TeacherId = :tid AND SubjectId = :sid AND ClassId = :cid";
                            $check = $dbh->prepare($sql);
                            $check->execute([':tid' => $teacher_id, ':sid' => $subject_id, ':cid' => $class_id]);
                            
                            if ($check->rowCount() > 0) {
                                $error = "Esta asignación ya existe.";
                            } else {
                                // Insertar nueva asignación
                                $sql = "INSERT INTO tblteacher_subject (TeacherId, SubjectId, ClassId) 
                                        VALUES (:tid, :sid, :cid)";
                                $insert = $dbh->prepare($sql);
                                if ($insert->execute([':tid' => $teacher_id, ':sid' => $subject_id, ':cid' => $class_id])) {
                                    $msg = "Asignación creada exitosamente.";
                                } else {
                                    $error = "Error al crear la asignación. Inténtalo de nuevo.";
                                }
                            }
                        }
                    }
                }
            }
        }
    } 
    elseif ($action === 'delete') {
        // Eliminar asignación
        $assignment_id = intval($_POST['assignment_id'] ?? 0);
        
        if (!$assignment_id) {
            $error = "ID de asignación inválido.";
        } else {
            $sql = "DELETE FROM tblteacher_subject WHERE Id = :id";
            $delete = $dbh->prepare($sql);
            if ($delete->execute([':id' => $assignment_id])) {
                $msg = "Asignación eliminada correctamente.";
            } else {
                $error = "Error al eliminar la asignación.";
            }
        }
    }
}

// Obtener todas las asignaciones actuales
$sql = "SELECT ts.Id, t.TeacherName, t.TeacherEmail, s.SubjectName, c.ClassName, c.Section, c.AcademicYear
        FROM tblteacher_subject ts
        JOIN tblteachers t ON ts.TeacherId = t.Id
        JOIN tblsubjects s ON ts.SubjectId = s.id
        JOIN tblclasses c ON ts.ClassId = c.id
        ORDER BY t.TeacherName, c.AcademicYear DESC, c.ClassName, s.SubjectName";
$query = $dbh->prepare($sql);
$query->execute();
$assignments = $query->fetchAll(PDO::FETCH_OBJ);

// Obtener maestros, materias y clases para los selects
$sql = "SELECT Id, TeacherName, TeacherEmail FROM tblteachers WHERE Status = 1 ORDER BY TeacherName";
$query = $dbh->prepare($sql);
$query->execute();
$teachers = $query->fetchAll(PDO::FETCH_OBJ);

$sql = "SELECT id, SubjectName FROM tblsubjects ORDER BY SubjectName";
$query = $dbh->prepare($sql);
$query->execute();
$subjects = $query->fetchAll(PDO::FETCH_OBJ);

$sql = "SELECT id, ClassName, Section, AcademicYear FROM tblclasses ORDER BY AcademicYear DESC, ClassName, Section";
$query = $dbh->prepare($sql);
$query->execute();
$classes = $query->fetchAll(PDO::FETCH_OBJ);

// Si vino desde manage-teacher.php con ID, preseleccionar el maestro
$selected_teacher = $teacher_id_param > 0 ? $teacher_id_param : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Maestros a Materias - Instituto Panamericano</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container-main {
            background: white;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            padding: 30px;
            margin-top: 20px;
        }
        .header-section {
            border-bottom: 3px solid #238D15;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header-section h2 {
            color: #238D15;
            font-weight: 700;
        }
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #238D15;
        }
        .form-section h5 {
            color: #238D15;
            margin-bottom: 15px;
        }
        .btn-assign {
            background: #238D15;
            border: none;
            padding: 10px 25px;
            font-weight: 600;
        }
        .btn-assign:hover {
            background: #1a6a0f;
            color: white;
        }
        .table-section {
            margin-top: 30px;
        }
        .table-section h5 {
            color: #238D15;
            margin-bottom: 15px;
            font-weight: 700;
        }
        .table {
            font-size: 0.95rem;
        }
        .table thead {
            background: #238D15;
            color: white;
        }
        .table tbody tr:hover {
            background: #f0f0f0;
        }
        .btn-delete {
            background: #dc3545;
            border: none;
            color: white;
            padding: 5px 12px;
            font-size: 0.85rem;
            border-radius: 4px;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .alert {
            border-radius: 6px;
            border-left: 4px solid #238D15;
        }
        .alert-danger {
            border-left-color: #dc3545;
        }
    </style>
</head>
<body>
    <?php include('includes/topbar.php'); ?>
    
    <div class="content-wrapper">
        <div class="content-container">
            <?php include('includes/leftbar.php'); ?>
            
            <div class="main-page">
                <div class="container-lg">
                    <!-- Mensajes de estado -->
                    <?php if ($msg): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?= htmlentities($msg) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?= htmlentities($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="container-main">
                        <!-- Encabezado -->
                        <div class="header-section">
                            <h2><i class="fas fa-user-tie"></i> Asignar Maestros a Materias</h2>
                            <p class="text-muted">Administra las asignaciones de maestros a materias y clases</p>
                        </div>
                        
                        <!-- Formulario de asignación -->
                        <div class="form-section">
                            <h5><i class="fas fa-plus-circle"></i> Nueva Asignación</h5>
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="action" value="assign">
                                
                                <div class="col-md-4">
                                    <label for="teacher_id" class="form-label">Maestro</label>
                                    <select name="teacher_id" id="teacher_id" class="form-select" required>
                                        <option value="">-- Seleccionar Maestro --</option>
                                        <?php foreach ($teachers as $teacher): 
                                            $selected = ($selected_teacher == $teacher->Id) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $teacher->Id ?>" <?php echo $selected; ?>>
                                                <?= htmlentities($teacher->TeacherName) ?> (<?= htmlentities($teacher->TeacherEmail) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="subject_id" class="form-label">Materia</label>
                                    <select name="subject_id" id="subject_id" class="form-select" required>
                                        <option value="">-- Seleccionar Materia --</option>
                                        <?php foreach ($subjects as $subject): ?>
                                            <option value="<?= $subject->id ?>">
                                                <?= htmlentities($subject->SubjectName) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="class_id" class="form-label">Clase/Grupo</label>
                                    <select name="class_id" id="class_id" class="form-select" required>
                                        <option value="">-- Seleccionar Clase --</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?= $class->id ?>">
                                                <?= htmlentities($class->ClassName . ' ' . $class->Section) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-assign">
                                        <i class="fas fa-save"></i> Crear Asignación
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Tabla de asignaciones existentes -->
                        <div class="table-section">
                            <h5><i class="fas fa-list"></i> Asignaciones Actuales</h5>
                            <?php if (count($assignments) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Maestro</th>
                                                <th>Materia</th>
                                                <th>Clase</th>
                                                <th>Fecha Asignación</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($assignments as $assign): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlentities($assign->TeacherName) ?></strong><br>
                                                        <small class="text-muted"><?= htmlentities($assign->TeacherEmail) ?></small>
                                                    </td>
                                                    <td><?= htmlentities($assign->SubjectName) ?></td>
                                                    <td><?= htmlentities($assign->ClassName . ' (' . $assign->Section . ') - ' . $assign->AcademicYear) ?></td>
                                                    <td>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="assignment_id" value="<?= $assign->Id ?>">
                                                            <button type="submit" class="btn-delete" onclick="return confirm('¿Eliminar esta asignación?')">
                                                                <i class="fas fa-trash"></i> Eliminar
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No hay asignaciones aún. Crea una nueva.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('includes/footer.php'); ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
