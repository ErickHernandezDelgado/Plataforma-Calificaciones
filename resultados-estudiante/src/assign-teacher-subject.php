<?php
/**
 * assign-teacher-subject.php - Versión con Filtro Dinámico (AJAX)
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/includes/config.php');

if (!isset($_SESSION['alogin']) || !in_array($_SESSION['role'], ['admin', 'director'])) {
    header("Location: index.php");
    exit;
}

// --- BLOQUE AJAX: Responde a la solicitud de materias por grupo ---
if (isset($_GET['get_subjects']) && isset($_GET['class_id'])) {
    $class_id = intval($_GET['class_id']);
    $sql = "SELECT s.id, s.SubjectName FROM tblsubjects s 
            JOIN tblsubjectcombination sc ON s.id = sc.SubjectId 
            WHERE sc.ClassId = :cid AND sc.status = 1 
            ORDER BY s.SubjectName ASC";
    $query = $dbh->prepare($sql);
    $query->execute([':cid' => $class_id]);
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($results);
    exit; // Detiene la ejecución aquí para enviar solo el JSON
}

$msg = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign') {
    $teacher_id = intval($_POST['teacher_id'] ?? 0);
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $class_id = intval($_POST['class_id'] ?? 0);
    
    if (!$teacher_id || !$subject_id || !$class_id) {
        $error = "Selecciona todos los campos.";
    } else {
        // Verificar duplicados
        $sql_exist = "SELECT Id FROM tblteacher_subject WHERE TeacherId = :tid AND SubjectId = :sid AND ClassId = :cid";
        $stmt_exist = $dbh->prepare($sql_exist);
        $stmt_exist->execute([':tid' => $teacher_id, ':sid' => $subject_id, ':cid' => $class_id]);
        
        if ($stmt_exist->rowCount() > 0) {
            $error = "Esta asignación ya existe.";
        } else {
            $sql_ins = "INSERT INTO tblteacher_subject (TeacherId, SubjectId, ClassId) VALUES (:tid, :sid, :cid)";
            if ($dbh->prepare($sql_ins)->execute([':tid' => $teacher_id, ':sid' => $subject_id, ':cid' => $class_id])) {
                $msg = "Asignación guardada con éxito.";
            }
        }
    }
}

// Procesar eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['assignment_id'] ?? 0);
    $dbh->prepare("DELETE FROM tblteacher_subject WHERE Id = :id")->execute([':id' => $id]);
    $msg = "Asignación eliminada.";
}

// Carga inicial de datos
$assignments = $dbh->query("SELECT ts.Id, t.TeacherName, s.SubjectName, c.ClassName, c.Section FROM tblteacher_subject ts JOIN tblteachers t ON ts.TeacherId = t.Id JOIN tblsubjects s ON ts.SubjectId = s.id JOIN tblclasses c ON ts.ClassId = c.id ORDER BY ts.Id DESC LIMIT 20")->fetchAll(PDO::FETCH_OBJ);
$teachers = $dbh->query("SELECT Id, TeacherName FROM tblteachers WHERE Status = 1 ORDER BY TeacherName")->fetchAll(PDO::FETCH_OBJ);
$classes = $dbh->query("SELECT id, ClassName, Section FROM tblclasses ORDER BY AcademicYear DESC, ClassName ASC")->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SGC | Asignación Dinámica</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/main.css">
    
    <style>
        body { background-color: #f4f4f4 !important; }
        .main-page { background: #fff !important; min-height: 100vh; padding: 20px; position: relative; }
        
        /* Estilo Verde IPT */
        .panel-verde { border: 1px solid #0F9B3A; border-radius: 8px; overflow: hidden; margin-bottom: 20px; }
        .panel-verde-header { background: #0F9B3A !important; color: white !important; padding: 12px 20px; font-weight: bold; }
        .btn-verde-ipt { background: #0F9B3A !important; color: white !important; font-weight: bold; border: none; height: 45px; }
        
        /* Selectores corregidos */
        select.form-control { height: 45px !important; display: block !important; border: 1px solid #ccc; }
        .table thead th { background: #0F9B3A !important; color: white !important; text-align: center; font-size: 12px; }
    </style>
</head>
<body class="top-navbar-fixed">
    <div class="main-wrapper">
        <?php include('includes/topbar.php'); ?>
        <div class="content-wrapper">
            <div class="content-container">
                <?php include('includes/leftbar.php'); ?>
                
                <div class="main-page">
                    <div class="container-fluid">
                        <h2 class="title">Asignación de Carga Académica</h2>
                        
                        <?php if($msg) echo "<div class='alert alert-success'>$msg</div>"; ?>
                        <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

                        <div class="panel-verde">
                            <div class="panel-verde-header"><i class="fa fa-plus-circle"></i> NUEVA ASIGNACIÓN</div>
                            <div class="panel-body">
                                <form method="POST" id="assignForm">
                                    <input type="hidden" name="action" value="assign">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label>Maestro</label>
                                            <select name="teacher_id" class="form-control" required>
                                                <option value="">-- Seleccione --</option>
                                                <?php foreach($teachers as $t): ?>
                                                    <option value="<?= $t->Id ?>"><?= htmlentities($t->TeacherName) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label>Grupo / Clase</label>
                                            <select name="class_id" id="class_select" class="form-control" required onchange="fetchSubjects(this.value)">
                                                <option value="">-- Seleccione Grupo --</option>
                                                <?php foreach($classes as $c): ?>
                                                    <option value="<?= $c->id ?>"><?= htmlentities($c->ClassName." - ".$c->Section) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label>Materia (Asociadas al grupo)</label>
                                            <select name="subject_id" id="subject_select" class="form-control" required disabled>
                                                <option value="">-- Seleccione Grupo Primero --</option>
                                            </select>
                                        </div>

                                        <div class="col-md-2">
                                            <label>&nbsp;</label>
                                            <button type="submit" class="btn btn-verde-ipt btn-block">GUARDAR</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="panel panel-default">
                            <div class="panel-heading">Asignaciones Recientes</div>
                            <div class="panel-body">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Maestro</th>
                                            <th>Materia</th>
                                            <th>Grupo</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($assignments as $a): ?>
                                        <tr>
                                            <td><strong><?= htmlentities($a->TeacherName) ?></strong></td>
                                            <td><?= htmlentities($a->SubjectName) ?></td>
                                            <td><?= htmlentities($a->ClassName." - ".$a->Section) ?></td>
                                            <td class="text-center">
                                                <form method="POST" onsubmit="return confirm('¿Quitar asignación?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="assignment_id" value="<?= $a->Id ?>">
                                                    <button type="submit" class="btn btn-danger btn-xs">Quitar</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <?php include('includes/footer.php'); ?>
    </div>

    <script>
    function fetchSubjects(classId) {
        const subjectSelect = document.getElementById('subject_select');
        
        // Si no hay grupo seleccionado, limpiamos y bloqueamos
        if (!classId) {
            subjectSelect.innerHTML = '<option value="">-- Seleccione Grupo Primero --</option>';
            subjectSelect.disabled = true;
            return;
        }

        // Llamada AJAX al mismo archivo
        fetch(`assign-teacher-subject.php?get_subjects=1&class_id=${classId}`)
            .then(response => response.json())
            .then(data => {
                subjectSelect.innerHTML = '<option value="">-- Elija la Materia --</option>';
                
                if (data.length > 0) {
                    data.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject.id;
                        option.text = subject.SubjectName;
                        subjectSelect.appendChild(option);
                    });
                    subjectSelect.disabled = false;
                } else {
                    subjectSelect.innerHTML = '<option value="">No hay materias vinculadas</option>';
                    subjectSelect.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar materias');
            });
    }
    </script>
</body>
</html>