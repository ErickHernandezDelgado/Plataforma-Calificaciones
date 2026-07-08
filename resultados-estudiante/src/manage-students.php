<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
include(__DIR__ . '/includes/config.php');

if (!isset($_SESSION['alogin']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
} else {
    $msg = '';
    $error = '';
    $nueva_clave_tutor = '';

    // Genera un token CSRF para las acciones POST destructivas
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Valida CSRF en cualquier POST con acción destructiva
    $csrf_ok = !($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']))
        || (isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']));

    // --- LÓGICA DE REGENERACIÓN DE CONTRASEÑA (POST + CSRF) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'reset_tutor') {
        if (!$csrf_ok) {
            $error = "Solicitud no válida. Recarga la página e inténtalo de nuevo.";
        } else {
            $tutor_id = intval($_POST['tutor_id'] ?? 0);
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            $new_raw_pass = substr(str_shuffle($chars), 0, 8);
            // bcrypt (el login acepta bcrypt y MD5 legacy; no afecta cuentas existentes)
            $new_hash_pass = password_hash($new_raw_pass, PASSWORD_DEFAULT);

            $sql_update = "UPDATE admin SET Password = :pass WHERE id = :id AND role = 'tutor'";
            $query_update = $dbh->prepare($sql_update);
            $query_update->bindParam(':pass', $new_hash_pass, PDO::PARAM_STR);
            $query_update->bindParam(':id', $tutor_id, PDO::PARAM_INT);

            if ($query_update->execute() && $query_update->rowCount() > 0) {
                $msg = "Nueva contraseña generada para el tutor.";
                $nueva_clave_tutor = $new_raw_pass;
            } else {
                $error = "No se pudo actualizar la contraseña del tutor.";
            }
        }
    }

    // --- LÓGICA DE ELIMINACIÓN DE ESTUDIANTE (POST + CSRF, bloquea si tiene calificaciones) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'del_student') {
        if (!$csrf_ok) {
            $error = "Solicitud no válida. Recarga la página e inténtalo de nuevo.";
        } else {
            $stid = intval($_POST['stid'] ?? 0);

            // Bloquea el borrado si el alumno tiene calificaciones registradas
            $chkRes = $dbh->prepare("SELECT COUNT(*) FROM tblresult WHERE StudentId = :id");
            $chkRes->bindParam(':id', $stid, PDO::PARAM_INT);
            $chkRes->execute();
            $totalCalif = (int) $chkRes->fetchColumn();

            if ($totalCalif > 0) {
                $error = "No se puede eliminar al estudiante porque tiene {$totalCalif} calificación(es) registrada(s). Desasígnalo de su grupo primero.";
            } else {
                $sql_del = "DELETE FROM tblstudents WHERE StudentId = :id";
                $query_del = $dbh->prepare($sql_del);
                $query_del->bindParam(':id', $stid, PDO::PARAM_INT);
                if ($query_del->execute()) {
                    $msg = "Estudiante eliminado correctamente del sistema.";
                } else {
                    $error = "Error al intentar eliminar el registro.";
                }
            }
        }
    }

    // --- LÓGICA DE ACTIVAR/DESACTIVAR ESTUDIANTE (Status 1<->0, POST + CSRF) ---
    // Un alumno desactivado deja de aparecer en captura de notas y en el portal del tutor,
    // pero su historial y calificaciones se conservan. Reversible.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'toggle_status') {
        if (!$csrf_ok) {
            $error = "Solicitud no válida. Recarga la página e inténtalo de nuevo.";
        } else {
            $stid  = intval($_POST['stid'] ?? 0);
            $nuevo = (intval($_POST['nuevo_status'] ?? 0) === 1) ? 1 : 0;
            $upd = $dbh->prepare("UPDATE tblstudents SET Status = :st WHERE StudentId = :id");
            $upd->bindParam(':st', $nuevo, PDO::PARAM_INT);
            $upd->bindParam(':id', $stid, PDO::PARAM_INT);
            if ($upd->execute()) {
                $msg = $nuevo === 1
                    ? "Estudiante reactivado. Vuelve a aparecer en el sistema."
                    : "Estudiante desactivado. Ya no aparece en captura ni en el portal del tutor (el historial se conserva).";
            } else {
                $error = "No se pudo actualizar el estado del estudiante.";
            }
        }
    }

    // Ciclo vigente (para mostrar por defecto los alumnos del año actual).
    $cicloVigente = (int)$dbh->query("SELECT MAX(CAST(AcademicYear AS UNSIGNED)) FROM tblschool_config")->fetchColumn();
    $selected_year = isset($_POST['academic_year']) ? intval($_POST['academic_year']) : $cicloVigente;

    // Años disponibles según los ALUMNOS (no los grupos): así el filtro ofrece los ciclos reales.
    $sql_years = "SELECT DISTINCT AcademicYear FROM tblstudents WHERE AcademicYear IS NOT NULL AND AcademicYear <> '' ORDER BY AcademicYear DESC";
    $query_years = $dbh->prepare($sql_years);
    $query_years->execute();
    $available_years = $query_years->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css" />
    <style>
        .btn-reset { background-color: #f59e0b; color: white; }
        .btn-reset:hover { background-color: #d97706; color: white; }
        .btn-delete { background-color: #dc2626; color: white; }
        .btn-delete:hover { background-color: #991b1b; color: white; }
        .alert-success { border-left: 5px solid #15803d; font-size: 16px; }
        .alert-danger { border-left: 5px solid #b91c1c; font-size: 16px; }

        /* Alinea los botones de acción en una fila uniforme (mismo tamaño y separación). */
        .acciones-cell { white-space: nowrap; }
        .acciones-group { display: inline-flex; gap: 6px; justify-content: flex-end; }
        .acciones-group form { display: inline; margin: 0; }
        .acciones-group .btn-action {
            width: 40px; height: 38px; padding: 0;
            display: inline-flex; align-items: center; justify-content: center;
            margin: 0; border-radius: 6px; font-size: 15px;
        }
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
                                                <option value="<?php echo htmlspecialchars($year['AcademicYear'], ENT_QUOTES); ?>" <?php echo ($selected_year == $year['AcademicYear']) ? 'selected' : ''; ?>>
                                                    Ciclo <?php echo htmlentities($year['AcademicYear']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </form>
                            </div>

                            <div class="panel-body p-20">
                                <?php if($msg != ""){ ?>
                                    <div class="alert alert-success">
                                        <strong><i class="fa fa-check-circle"></i> ÉXITO:</strong> <?php echo htmlentities($msg); ?>
                                    </div>
                                <?php } ?>

                                <?php if($nueva_clave_tutor != ""){ ?>
                                    <div class="alert alert-warning">
                                        <strong>Nueva contraseña del tutor:</strong> <code><?php echo htmlentities($nueva_clave_tutor); ?></code>
                                        <br><small>Anótala y entrégala al tutor. No se volverá a mostrar.</small>
                                    </div>
                                <?php } ?>

                                <?php if($error != ""){ ?>
                                    <div class="alert alert-danger">
                                        <strong><i class="fa fa-times-circle"></i> ERROR:</strong> <?php echo htmlentities($error); ?>
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
                                                                <i class="fa fa-lock text-muted"></i> <code>Acceso activo</code>
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
                                                    <td class="text-right acciones-cell">
                                                        <div class="acciones-group">
                                                        <a href="edit-student.php?stid=<?php echo $result->StudentId; ?>" class="btn btn-info btn-action" title="Editar">
                                                            <i class="fa fa-pencil"></i>
                                                        </a>

                                                        <?php if ($result->TutorEmail) { ?>
                                                            <form method="post" action="manage-students.php" style="display:inline;"
                                                                  onsubmit="return confirm('¿Estás seguro de generar una nueva clave para este tutor?')">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">
                                                                <input type="hidden" name="accion" value="reset_tutor">
                                                                <input type="hidden" name="tutor_id" value="<?php echo (int)$result->TutorId; ?>">
                                                                <button type="submit" class="btn btn-reset btn-action" title="Regenerar Contraseña">
                                                                    <i class="fa fa-refresh"></i>
                                                                </button>
                                                            </form>
                                                        <?php } ?>

                                                        <?php $stActivo = ($result->Status == 1); ?>
                                                        <form method="post" action="manage-students.php" style="display:inline;"
                                                              onsubmit="return confirm('<?php echo $stActivo
                                                                  ? "¿Desactivar a este alumno? Dejará de aparecer en captura de notas y en el portal del tutor. El historial se conserva."
                                                                  : "¿Reactivar a este alumno? Volverá a aparecer en el sistema."; ?>')">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">
                                                            <input type="hidden" name="accion" value="toggle_status">
                                                            <input type="hidden" name="stid" value="<?php echo (int)$result->StudentId; ?>">
                                                            <input type="hidden" name="nuevo_status" value="<?php echo $stActivo ? 0 : 1; ?>">
                                                            <button type="submit" class="btn btn-action <?php echo $stActivo ? 'btn-warning' : 'btn-success'; ?>"
                                                                    title="<?php echo $stActivo ? 'Desactivar alumno' : 'Reactivar alumno'; ?>">
                                                                <i class="fa <?php echo $stActivo ? 'fa-ban' : 'fa-check-circle'; ?>"></i>
                                                            </button>
                                                        </form>

                                                        <form method="post" action="manage-students.php" style="display:inline;"
                                                              onsubmit="return confirm('¿Realmente deseas eliminar a este estudiante? Si tiene calificaciones registradas, el sistema lo impedirá. Esta acción no se puede deshacer.')">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">
                                                            <input type="hidden" name="accion" value="del_student">
                                                            <input type="hidden" name="stid" value="<?php echo (int)$result->StudentId; ?>">
                                                            <button type="submit" class="btn btn-delete btn-action" title="Eliminar Estudiante">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </form>
                                                        </div><!-- /.acciones-group -->
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