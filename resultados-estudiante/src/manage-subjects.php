<?php
session_start();
error_reporting(E_ALL & ~E_NOTICE);
include(__DIR__ . '/includes/config.php');

if (empty($_SESSION['alogin'])) {
    header("Location: index.php");
    exit;
}

$msg = '';
$error = '';

// Eliminar materia
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $subjectId = $_GET['delete'];

    $checks = [
        'tblsubjectcombination' => 'grupos asignados',
        'tblteacher_subject'    => 'docentes asignados',
        'tblresult'             => 'calificaciones registradas',
    ];
    $blocking = [];
    foreach ($checks as $table => $label) {
        $chk = $dbh->prepare("SELECT COUNT(*) FROM `$table` WHERE SubjectId = :id");
        $chk->bindParam(':id', $subjectId, PDO::PARAM_INT);
        $chk->execute();
        if ($chk->fetchColumn() > 0) {
            $blocking[] = $label;
        }
    }

    if (!empty($blocking)) {
        $error = "No se puede eliminar esta materia porque tiene: " . implode(', ', $blocking) . ". Elimina esos registros primero.";
    } else {
        $del = $dbh->prepare("DELETE FROM tblsubjects WHERE id = :id");
        $del->bindParam(':id', $subjectId, PDO::PARAM_INT);
        if ($del->execute()) {
            $msg = "Materia eliminada correctamente.";
        } else {
            $error = "No se pudo eliminar la materia. Intenta de nuevo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SGC | Gestionar Materias</title>
    <link rel="stylesheet" href="css/bootstrap.min.css" media="screen" >
    <link rel="stylesheet" href="css/font-awesome.min.css" media="screen" >
    <link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css" />
    <link rel="stylesheet" href="css/main.css" media="screen" >

    <style>
        /* FIX DEFINITIVO PARA EL CORTE DE PÁGINA */
        .content-container {
            display: flex; /* Alinea sidebar y contenido */
            width: 100%;
        }
        
        .main-page { 
            flex: 1; /* Hace que el contenido ocupe todo el espacio restante */
            min-width: 0; /* Evita que el contenido ancho rompa el flexbox */
            padding: 25px; 
            background: #f8f9fa; 
            min-height: 90vh; 
        }

        .panel-verde-ipt { border-top: 5px solid #0F9B3A; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .btn-verde-ipt { background: #0F9B3A !important; color: white !important; border: none; font-weight: bold; padding: 8px 20px; }
        .btn-verde-ipt:hover { background: #0c7a2d !important; transition: 0.3s; }
        
        /* Ajuste para la tabla responsiva */
        .table-responsive { 
            border: none !important; 
            overflow-x: auto !important; /* Permite scroll horizontal solo en la tabla si es muy ancha */
            width: 100%;
        }
        
        .col-grupos { max-width: 250px; font-size: 0.9em; line-height: 1.4; }
        .badge-asignada { background-color: #0F9B3A; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.85em; white-space: nowrap; }
        .badge-pendiente { background-color: #f39c12; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.85em; white-space: nowrap; }
        
        .filter-box { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #e0e0e0; }
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
                        <div class="row page-title-div">
                            <div class="col-md-12">
                                <h2 class="title">Gestión de Materias</h2>
                            </div>
                        </div>

                        <div class="filter-box">
                            <form method="POST">
                                <div class="row" style="display: flex; align-items: center; flex-wrap: wrap;">
                                    <div class="col-md-5">
                                        <label><strong><i class="fa fa-filter"></i> Filtrar por Grupo Académico:</strong></label>
                                        <select name="class_filter" class="form-control" onchange="this.form.submit();">
                                            <option value="">-- Todas las Materias --</option>
                                            <?php
                                            $sql_class = "SELECT DISTINCT c.id, c.ClassName, c.Section, c.AcademicYear FROM tblclasses c ORDER BY c.AcademicYear DESC, c.ClassName ASC";
                                            $query_class = $dbh->prepare($sql_class);
                                            $query_class->execute();
                                            $classes = $query_class->fetchAll(PDO::FETCH_OBJ);
                                            $selected_class = $_POST['class_filter'] ?? '';
                                            foreach ($classes as $class) {
                                                $selected = ($selected_class == $class->id) ? 'selected' : '';
                                                echo "<option value='{$class->id}' {$selected}>{$class->ClassName} - {$class->Section} ({$class->AcademicYear})</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-7 text-right">
                                        <a href="create-subject.php" class="btn btn-verde-ipt">
                                            <i class="fa fa-plus-circle"></i> Nueva Materia
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <?php if ($msg): ?>
                            <div class="alert alert-success left-icon-alert" role="alert">
                                <strong>Bien hecho!</strong> <?php echo htmlentities($msg); ?>
                            </div>
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger left-icon-alert" role="alert">
                                <strong>Inconvenientes:</strong> <?php echo htmlentities($error); ?>
                            </div>
                        <?php endif; ?>

                        <div class="panel panel-default panel-verde-ipt">
                            <div class="panel-body p-20">
                                <div class="table-responsive"> 
                                    <table id="example" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Materia</th>
                                                <th>Código</th>
                                                <th>Grupos Asignados</th>
                                                <th>Nivel</th>
                                                <th>Año</th>
                                                <th>Estado</th>
                                                <th width="80">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $sql = "SELECT s.id, s.SubjectName, s.SubjectCode,
                                                    GROUP_CONCAT(DISTINCT c.ClassName, ' (', c.Section, ')' ORDER BY c.ClassName SEPARATOR ', ') as grupos,
                                                    GROUP_CONCAT(DISTINCT c.educationLevel ORDER BY c.educationLevel SEPARATOR ', ') as niveles,
                                                    GROUP_CONCAT(DISTINCT c.AcademicYear ORDER BY c.AcademicYear SEPARATOR ', ') as anos,
                                                    COUNT(DISTINCT sc.id) as num_grupos
                                                    FROM tblsubjects s
                                                    LEFT JOIN tblsubjectcombination sc ON sc.SubjectId = s.id AND sc.status = 1
                                                    LEFT JOIN tblclasses c ON sc.ClassId = c.id";
                                            
                                            if (!empty($selected_class)) {
                                                $sql .= " WHERE sc.ClassId = :class_id";
                                            }
                                            
                                            $sql .= " GROUP BY s.id ORDER BY s.SubjectName ASC";
                                            $query = $dbh->prepare($sql);
                                            if (!empty($selected_class)) $query->bindParam(':class_id', $selected_class);
                                            $query->execute();
                                            $results = $query->fetchAll(PDO::FETCH_OBJ);
                                            $cnt = 1;

                                            foreach ($results as $result) { ?>
                                            <tr>
                                                <td><?php echo $cnt++; ?></td>
                                                <td><strong><?php echo htmlentities($result->SubjectName ?? ''); ?></strong></td>
                                                <td><code><?php echo htmlentities($result->SubjectCode ?? ''); ?></code></td>
                                                <td class="col-grupos">
                                                    <?php echo ($result->grupos) ? htmlentities($result->grupos) : '<em class="text-muted">Sin asignar</em>'; ?>
                                                </td>
                                                <td><span class="text-uppercase" style="font-size: 0.85em; font-weight:600;"><?php echo htmlentities($result->niveles ?? 'N/A'); ?></span></td>
                                                <td><?php echo htmlentities($result->anos ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php echo ($result->num_grupos > 0) 
                                                        ? '<span class="badge-asignada"><i class="fa fa-check"></i> Asignada</span>' 
                                                        : '<span class="badge-pendiente"><i class="fa fa-clock-o"></i> Pendiente</span>'; ?>
                                                </td>
                                                <td class="text-center">
                                                    <a href="edit-subject.php?subjectid=<?php echo $result->id; ?>" class="btn btn-info btn-xs" title="Editar"><i class="fa fa-edit"></i></a>
                                                    <a href="manage-subjects.php?delete=<?php echo $result->id; ?>"
                                                       class="btn btn-danger btn-xs"
                                                       title="Eliminar"
                                                       onclick="return confirm('¿Eliminar la materia «<?php echo htmlspecialchars($result->SubjectName, ENT_QUOTES); ?>»? Esta acción no se puede deshacer.');">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div> 
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include('includes/footer.php'); ?>
    </div>

    <script src="js/jquery/jquery-2.2.4.min.js"></script>
    <script src="js/bootstrap/bootstrap.min.js"></script>
    <script src="assets/js/DataTables/datatables.min.js"></script>
    <script>
        $(function($) {
            $('#example').DataTable({
                "language": { "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json" },
                "searching": true,
                "lengthChange": false,
                "info": true,
                "paging": true,
                "pageLength": 10 
            });
        });
    </script>
</body>
</html>
<?php include('includes/footer.php'); ?>