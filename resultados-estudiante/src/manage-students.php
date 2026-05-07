<?php
session_start();
error_reporting(E_ALL); 
include(__DIR__ . '/includes/config.php');

if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php");
    exit;
} else {
    // --- LÓGICA DE REGENERACIÓN DE CONTRASEÑA ---
    if (isset($_GET['reset_tutor_id'])) {
        $tutor_id = intval($_GET['reset_tutor_id']);
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $new_raw_pass = substr(str_shuffle($chars), 0, 8);
        $new_md5_pass = md5($new_raw_pass);

        $sql_update = "UPDATE admin SET Password = :pass WHERE id = :id AND role = 'tutor'";
        $query_update = $dbh->prepare($sql_update);
        $query_update->bindParam(':pass', $new_md5_pass, PDO::PARAM_STR);
        $query_update->bindParam(':id', $tutor_id, PDO::PARAM_INT);
        
        if ($query_update->execute()) {
            $msg = "Nueva contraseña generada para el tutor: " . $new_raw_pass;
        } else {
            $error = "No se pudo actualizar la contraseña.";
        }
    }

    // --- LÓGICA DE ELIMINACIÓN DE ESTUDIANTE (Agregado por Auditoría) ---
    if (isset($_GET['del_stid'])) {
        $stid = intval($_GET['del_stid']);
        
        // Se recomienda que en la BD la relación tenga ON DELETE CASCADE 
        // para borrar calificaciones automáticamente, si no, se borra solo al alumno.
        $sql_del = "DELETE FROM tblstudents WHERE StudentId = :id";
        $query_del = $dbh->prepare($sql_del);
        $query_del->bindParam(':id', $stid, PDO::PARAM_INT);
        
        if ($query_del->execute()) {
            $msg = "Estudiante eliminado correctamente del sistema.";
        } else {
            $error = "Error al intentar eliminar el registro.";
        }
    }

    $selected_year = isset($_POST['academic_year']) ? intval($_POST['academic_year']) : date('Y');

    $sql_years = "SELECT DISTINCT AcademicYear FROM tblclasses ORDER BY AcademicYear DESC";
    $query_years = $dbh->prepare($sql_years);
    $query_years->execute();
    $available_years = $query_years->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css" />
    <style>
        .btn-reset { background-color: #f59e0b; color: white; margin-left: 4px; }
        .btn-reset:hover { background-color: #d97706; color: white; }
        .btn-delete { background-color: #dc2626; color: white; margin-left: 4px; }
        .btn-delete:hover { background-color: #991b1b; color: white; }
        .alert-success { border-left: 5px solid #15803d; font-size: 16px; }
        .alert-danger { border-left: 5px solid #b91c1c; font-size: 16px; }
    </style>
</head>
<body>

<?php include('includes/topbar.php'); ?>

<div class="content-wrapper">
    <div class="content-container">
        <?php include('includes/leftbar.php'); ?>

        <div class="main-page">
            <div class="container-fluid">
                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-12">
                        <div class="main-card">
                            <div class="card-header-custom">
                                <h5 class="m-0">Listado de Inscritos</h5>
                                <form method="POST" class="form-inline">
                                    <div class="form-group mb-0">
                                        <label class="mr-2">Año Académico:</label>
                                        <select name="academic_year" class="form-control" onchange="this.form.submit()">
                                            <option value="">Todos los registros</option>
                                            <?php foreach ($available_years as $year): ?>
                                                <option value="<?php echo $year['AcademicYear']; ?>" <?php echo ($selected_year == $year['AcademicYear']) ? 'selected' : ''; ?>>
                                                    Ciclo <?php echo $year['AcademicYear']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </form>
                            </div>

                            <div class="panel-body p-20">
                                <?php if(isset($msg) && $msg != ""){ ?>
                                    <div class="alert alert-success">
                                        <strong><i class="fa fa-check-circle"></i> ÉXITO:</strong> <?php echo $msg; ?>
                                    </div>
                                <?php } ?>

                                <?php if(isset($error) && $error != ""){ ?>
                                    <div class="alert alert-danger">
                                        <strong><i class="fa fa-times-circle"></i> ERROR:</strong> <?php echo $error; ?>
                                    </div>
                                <?php } ?>

                                <div class="table-responsive">
                                    <table id="studentsTable" class="table table-hover" width="100%">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Estudiante</th>
                                                <th>Grado / Sección</th>
                                                <th>Acceso Tutor</th>
                                                <th class="text-center">Estado</th>
                                                <th class="text-right">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $sql = "SELECT * FROM vw_student_with_tutor_complete WHERE 1=1";
                                            if ($selected_year) { $sql .= " AND AcademicYear = :year"; }
                                            $sql .= " ORDER BY ClassName ASC, Section ASC, StudentName ASC";

                                            $query = $dbh->prepare($sql);
                                            if ($selected_year) { $query->bindParam(':year', $selected_year, PDO::PARAM_INT); }
                                            $query->execute();
                                            $results = $query->fetchAll(PDO::FETCH_OBJ);

                                            foreach ($results as $index => $result) { ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <div class="student-info">
                                                            <span class="student-name"><?php echo htmlentities($result->StudentName); ?></span>
                                                            <span class="student-email"><?php echo htmlentities($result->StudentEmail); ?></span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info"><?php echo htmlentities($result->ClassName); ?></span>
                                                        <span class="text-muted small">Secc. <?php echo htmlentities($result->Section); ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($result->TutorEmail) { ?>
                                                            <div style="font-size: 11px; line-height: 1.4;">
                                                                <i class="fa fa-envelope-o text-primary"></i> <?php echo htmlentities($result->TutorEmail); ?><br>
                                                                <i class="fa fa-lock text-muted"></i> <code>MD5 Hash Active</code>
                                                            </div>
                                                        <?php } else { ?>
                                                            <span class="text-muted small italic">Sin tutor</span>
                                                        <?php } ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="status-pill <?php echo ($result->Status == 1) ? 'bg-active' : 'bg-blocked'; ?>">
                                                            <?php echo ($result->Status == 1) ? 'ACTIVO' : 'BLOQUEADO'; ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-right">
                                                        <a href="edit-student.php?stid=<?php echo $result->StudentId; ?>" class="btn btn-info btn-action" title="Editar">
                                                            <i class="fa fa-pencil"></i>
                                                        </a>

                                                        <?php if ($result->TutorEmail) { ?>
                                                            <a href="manage-students.php?reset_tutor_id=<?php echo $result->TutorId; ?>" 
                                                               class="btn btn-reset btn-action" 
                                                               title="Regenerar Contraseña"
                                                               onclick="return confirm('¿Estás seguro de generar una nueva clave para este tutor?')">
                                                                <i class="fa fa-refresh"></i>
                                                            </a>
                                                        <?php } ?>

                                                        <a href="manage-students.php?del_stid=<?php echo $result->StudentId; ?>" 
                                                           class="btn btn-delete btn-action" 
                                                           title="Eliminar Estudiante"
                                                           onclick="return confirm('¿Realmente deseas eliminar a este estudiante? Se borrará su historial académico y esta acción no se puede deshacer.')">
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
    </div>
</div>

<?php include('includes/footer.php'); ?>
</body>
</html>
<?php } ?>