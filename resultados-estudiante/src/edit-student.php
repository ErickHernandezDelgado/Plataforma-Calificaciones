<?php
// Inicia la sesión
session_start();

// Desactiva los mensajes de error (no recomendado en producción, es mejor manejar errores de forma controlada)
error_reporting(0);

// Incluye el archivo de configuración (conexión a la base de datos, entre otros)
include(__DIR__ . '/includes/config.php');

// Helper del ciclo escolar vigente (current_academic_year) para mantener la matrícula
// (tblenrollment) sincronizada cuando el admin cambia el grupo del alumno.
require_once(__DIR__ . '/includes/result-audit.php');

// Verifica que el usuario haya iniciado sesión y que su rol sea 'admin'
if (!isset($_SESSION['alogin']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
} else {

    $stid = intval($_GET['stid'] ?? 0);
    $msg = '';
    $error = '';
    $nueva_clave_tutor = '';

    // Genera un token CSRF para las acciones POST/GET destructivas
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Relaciones válidas según el ENUM de student_tutor
    $relaciones_validas = ['padre', 'madre', 'tutor_legal', 'abuelo'];

    // Verifica que el estudiante exista antes de procesar cualquier acción
    $chkStudent = $dbh->prepare("SELECT StudentId FROM tblstudents WHERE StudentId = :id");
    $chkStudent->execute([':id' => $stid]);
    if (!$stid || !$chkStudent->fetch()) {
        header("Location: manage-students.php");
        exit;
    }

    // Helper de validación CSRF para esta pantalla
    $csrf_valido = isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);

    // Quitar tutor (POST + CSRF)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_tutor'])) {
        if (!$csrf_valido) {
            $error = "Solicitud no válida. Recarga la página e inténtalo de nuevo.";
        } else {
            $tid = intval($_POST['remove_tutor']);
            $dbh->prepare("DELETE FROM student_tutor WHERE StudentId = :sid AND TutorId = :tid")
                ->execute([':sid' => $stid, ':tid' => $tid]);
            $dbh->prepare("UPDATE tblstudents SET primary_tutor_id = NULL WHERE StudentId = :sid AND primary_tutor_id = :tid")
                ->execute([':sid' => $stid, ':tid' => $tid]);
            $msg = "Tutor desvinculado correctamente.";
        }
    }

    // Agregar tutor (POST + CSRF)
    if (isset($_POST['add_tutor'])) {
        if (!$csrf_valido) {
            $error = "Solicitud no válida. Recarga la página e inténtalo de nuevo.";
        } else {
            $t_rel = $_POST['relationship_type'] ?? 'padre';
            if (!in_array($t_rel, $relaciones_validas, true)) {
                $error = "Selecciona una relación de tutor válida.";
            } elseif (($_POST['tutor_option'] ?? '') === 'create') {
                $t_email = trim($_POST['tutor_email'] ?? '');
                if (!filter_var($t_email, FILTER_VALIDATE_EMAIL)) {
                    $error = "El correo del tutor no es válido.";
                } else {
                    $check = $dbh->prepare("SELECT id FROM admin WHERE UserName = :u");
                    $check->execute([':u' => $t_email]);
                    if ($check->rowCount() > 0) {
                        $error = "Ese correo ya existe. Usa la opción 'Tutor existente'.";
                    } else {
                        try {
                            $raw_pass = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
                            // bcrypt (el login acepta bcrypt y MD5 legacy)
                            $dbh->prepare("INSERT INTO admin (UserName, Password, role) VALUES(:u, :p, 'tutor')")
                                ->execute([':u' => $t_email, ':p' => password_hash($raw_pass, PASSWORD_DEFAULT)]);
                            $new_tid = $dbh->lastInsertId();
                            $dbh->prepare("INSERT INTO student_tutor (StudentId, TutorId, RelationshipType, PrimaryContact) VALUES(:sid,:tid,:rel,0)")
                                ->execute([':sid' => $stid, ':tid' => $new_tid, ':rel' => $t_rel]);
                            $msg = "Tutor creado y vinculado correctamente.";
                            $nueva_clave_tutor = ['email' => $t_email, 'pass' => $raw_pass];
                        } catch (PDOException $e) {
                            $error = "No se pudo crear el tutor. Intenta de nuevo.";
                        }
                    }
                }
            } elseif (($_POST['tutor_option'] ?? '') === 'existing') {
                $existing_tid = intval($_POST['existing_tutor_id'] ?? 0);
                if ($existing_tid < 1) {
                    $error = "Selecciona un tutor válido.";
                } else {
                    $dup = $dbh->prepare("SELECT id FROM student_tutor WHERE StudentId = :sid AND TutorId = :tid");
                    $dup->execute([':sid' => $stid, ':tid' => $existing_tid]);
                    if ($dup->rowCount() > 0) {
                        $error = "Ese tutor ya está vinculado a este estudiante.";
                    } else {
                        $dbh->prepare("INSERT INTO student_tutor (StudentId, TutorId, RelationshipType, PrimaryContact) VALUES(:sid,:tid,:rel,0)")
                            ->execute([':sid' => $stid, ':tid' => $existing_tid, ':rel' => $t_rel]);
                        $msg = "Tutor vinculado correctamente.";
                    }
                }
            }
        }
    }

    if (isset($_POST['submit'])) {
        if (!$csrf_valido) {
            $error = "Solicitud no válida. Recarga la página e inténtalo de nuevo.";
        } else {
            $studentname  = trim($_POST['fullname'] ?? '');
            $studentemail = trim($_POST['emailid'] ?? '');
            $curp         = trim($_POST['curp'] ?? '');
            $status       = ($_POST['status'] ?? '1') === '0' ? 0 : 1;
            $classid      = intval($_POST['classid'] ?? 0);

            if ($studentname === '' || !filter_var($studentemail, FILTER_VALIDATE_EMAIL) || $classid < 1) {
                $error = "Completa todos los campos correctamente (nombre, correo válido y grupo).";
            } else {
                try {
                    // Grupo actual del alumno, para saber si el admin lo está cambiando.
                    $qOld = $dbh->prepare("SELECT ClassId FROM tblstudents WHERE StudentId = :stid");
                    $qOld->execute([':stid' => $stid]);
                    $oldClassId = (int)$qOld->fetchColumn();

                    // Datos del alumno + (si cambió) su matrícula del ciclo vigente se actualizan
                    // juntos o no se cambia nada.
                    $dbh->beginTransaction();

                    $sql = "UPDATE tblstudents
                            SET StudentName = :studentname, StudentEmail = :studentemail,
                                Curp = :curp, Status = :status, ClassId = :classid
                            WHERE StudentId = :stid";

                    $query = $dbh->prepare($sql);
                    $query->bindParam(':studentname',  $studentname,  PDO::PARAM_STR);
                    $query->bindParam(':studentemail', $studentemail, PDO::PARAM_STR);
                    $query->bindParam(':curp',         $curp,         PDO::PARAM_STR);
                    $query->bindParam(':status',       $status,       PDO::PARAM_INT);
                    $query->bindParam(':classid',      $classid,      PDO::PARAM_INT);
                    $query->bindParam(':stid',         $stid,         PDO::PARAM_INT);
                    $query->execute();

                    // Si el admin cambió el grupo, alinear la matrícula del CICLO VIGENTE al grupo
                    // nuevo (los ciclos anteriores del historial NO se tocan: la clave única es
                    // StudentId+AcademicYear, así que solo se afecta la fila del ciclo vigente).
                    if ($classid !== $oldClassId) {
                        $cicloVigente = current_academic_year($dbh, $classid);
                        $dbh->prepare(
                            "INSERT INTO tblenrollment (StudentId, ClassId, AcademicYear)
                             VALUES (:sid, :cl, :ay)
                             ON DUPLICATE KEY UPDATE ClassId = VALUES(ClassId)"
                        )->execute([':sid' => $stid, ':cl' => $classid, ':ay' => $cicloVigente]);
                    }

                    $dbh->commit();
                    $msg = "Información de estudiante actualizada correctamente.";
                } catch (PDOException $e) {
                    if ($dbh->inTransaction()) {
                        $dbh->rollBack();
                    }
                    if ($e->getCode() == 23000) {
                        $error = "Ya existe otro estudiante con ese correo electrónico.";
                    } else {
                        $error = "No se pudieron guardar los cambios. Intenta de nuevo.";
                    }
                }
            }
        }
    }
?>

<!-- Incluye la barra superior -->
<?php include('includes/topbar.php'); ?>

<div class="content-wrapper">
    <div class="content-container">
        <!-- Incluye la barra lateral izquierda -->
        <?php include('includes/leftbar.php'); ?>

        <div class="main-page">
            <div class="container-fluid">
                <!-- Título de la página -->
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Editar Estudiante</h2>
                    </div>
                </div>

                <!-- Breadcrumb de navegación -->
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li class="active">Editar Estudiante</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel">
                            <div class="panel-heading">
                                <div class="panel-title">
                                    <h5>Editar información del estudiante</h5>
                                </div>
                            </div>

                            <div class="panel-body">
                                <!-- Muestra mensaje de éxito o error si existe -->
                                <?php if ($msg) { ?>
                                    <div class="alert alert-success left-icon-alert" role="alert">
                                        <strong>Éxito! </strong><?php echo htmlentities($msg); ?>
                                    </div>
                                <?php } elseif ($error) { ?>
                                    <div class="alert alert-danger left-icon-alert" role="alert">
                                        <strong>Error! </strong><?php echo htmlentities($error); ?>
                                    </div>
                                <?php } ?>

                                <?php /* Credenciales del tutor ocultas (2026-07-09): el tutor entra solo con el
                                         correo del alumno, sin clave. Reactivar quitando "false &&". */ ?>
                                <?php if (false && $nueva_clave_tutor) { ?>
                                    <div class="alert alert-warning" role="alert">
                                        <strong>Credenciales del nuevo tutor:</strong><br>
                                        Usuario: <b><?php echo htmlentities($nueva_clave_tutor['email']); ?></b> |
                                        Contraseña: <code style="color:#b91c1c;"><?php echo htmlentities($nueva_clave_tutor['pass']); ?></code>
                                        <br><small>Anótala y entrégala al tutor. No se volverá a mostrar.</small>
                                    </div>
                                <?php } ?>

                                <!-- Formulario de edición del estudiante -->
                                <form class="form-horizontal" method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">
                                    <?php
                                    // Consulta para obtener los datos actuales del estudiante
                                    $sql = "SELECT StudentName, RollId, RegDate, StudentId, Status, StudentEmail, Curp, ClassId, ClassName, Section
                                            FROM tblstudents
                                            JOIN tblclasses ON tblclasses.id = tblstudents.ClassId
                                            WHERE StudentId = :stid";

                                    // Prepara y ejecuta la consulta
                                    $query = $dbh->prepare($sql);
                                    $query->bindParam(':stid', $stid, PDO::PARAM_INT);
                                    $query->execute();

                                    // Obtiene el resultado como objeto
                                    $result = $query->fetch(PDO::FETCH_OBJ);

                                    // Si se encontró el estudiante, muestra el formulario con sus datos
                                    if ($result) {
                                    ?>
                                        <!-- Campo: Nombre completo -->
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Nombre Completo</label>
                                            <div class="col-sm-10">
                                                <input type="text" name="fullname" class="form-control" value="<?php echo htmlentities($result->StudentName); ?>" required>
                                            </div>
                                        </div>

                                        <!-- Campo: Correo -->
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Correo</label>
                                            <div class="col-sm-10">
                                                <input type="email" name="emailid" class="form-control" value="<?php echo htmlentities($result->StudentEmail); ?>" required>
                                            </div>
                                        </div>

                                        <!-- Campo: CURP -->
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">CURP</label>
                                            <div class="col-sm-10">
                                                <input type="text" name="curp" class="form-control" maxlength="18" value="<?php echo htmlentities($result->Curp); ?>" required>
                                            </div>
                                        </div>

                                        <!-- Campo: Grupo (seleccionable) -->
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Grupo</label>
                                            <div class="col-sm-10">
                                                <select name="classid" class="form-control" required>
                                                    <?php
                                                    $qc = $dbh->prepare("SELECT id, ClassName, Section FROM tblclasses ORDER BY ClassName ASC, Section ASC");
                                                    $qc->execute();
                                                    foreach ($qc->fetchAll(PDO::FETCH_OBJ) as $class) {
                                                        $sel = ($class->id == $result->ClassId) ? 'selected' : '';
                                                        echo "<option value=\"{$class->id}\" $sel>" . htmlentities($class->ClassName . " - Sección " . $class->Section) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Campo: Fecha de Registro -->
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Fecha de Registro</label>
                                            <div class="col-sm-10">
                                                <p class="form-control-static"><?php echo htmlentities($result->RegDate); ?></p>
                                            </div>
                                        </div>

                                        <!-- Campo: Estado del estudiante (Activo/Inactivo) -->
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Estado</label>
                                            <div class="col-sm-10">
                                                <label><input type="radio" name="status" value="1" <?php if ($result->Status == 1) echo 'checked'; ?>> Activo</label>
                                                <label><input type="radio" name="status" value="0" <?php if ($result->Status == 0) echo 'checked'; ?>> Inactivo</label>
                                            </div>
                                        </div>

                                        <!-- Botón para enviar el formulario -->
                                        <div class="form-group">
                                            <div class="col-sm-offset-2 col-sm-10">
                                                <button type="submit" name="submit" class="btn btn-primary">Actualizar</button>
                                            </div>
                                        </div>
                                    <?php } else {
                                        echo "<p>Estudiante no encontrado.</p>";
                                    } ?>
                                </form>

                                <?php if ($result): ?>
                                <hr>
                                <h5><i class="fa fa-users"></i> Tutores / Responsables</h5>

                                <!-- Tabla de tutores actuales -->
                                <?php
                                $qt = $dbh->prepare("SELECT st.TutorId, st.RelationshipType, st.PrimaryContact, a.UserName
                                                     FROM student_tutor st
                                                     JOIN admin a ON a.id = st.TutorId
                                                     WHERE st.StudentId = :sid");
                                $qt->execute([':sid' => $stid]);
                                $tutors = $qt->fetchAll(PDO::FETCH_OBJ);
                                ?>
                                <?php if ($tutors): ?>
                                <table class="table table-bordered table-sm" style="margin-bottom:20px;">
                                    <thead><tr><th>Email</th><th>Relación</th><th>Principal</th><th>Acción</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($tutors as $t): ?>
                                        <tr>
                                            <td><?php echo htmlentities($t->UserName); ?></td>
                                            <td><?php echo ucfirst(htmlentities($t->RelationshipType)); ?></td>
                                            <td><?php echo $t->PrimaryContact ? '<span class="label label-success">Sí</span>' : 'No'; ?></td>
                                            <td>
                                                <form method="post" style="display:inline;"
                                                      onsubmit="return confirm('¿Quitar a <?php echo htmlspecialchars($t->UserName, ENT_QUOTES); ?> como tutor?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">
                                                    <input type="hidden" name="remove_tutor" value="<?php echo (int)$t->TutorId; ?>">
                                                    <button type="submit" class="btn btn-danger btn-xs">
                                                        <i class="fa fa-trash"></i> Quitar
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php else: ?>
                                    <p class="text-muted">Este estudiante no tiene tutores vinculados.</p>
                                <?php endif; ?>

                                <!-- Formulario para agregar tutor -->
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">
                                    <h6><i class="fa fa-plus"></i> Agregar Tutor</h6>
                                    <div class="form-group">
                                        <label>
                                            <input type="radio" name="tutor_option" value="create" checked onchange="toggleTutorEdit('c')"> Nuevo tutor
                                        </label>
                                        &nbsp;&nbsp;
                                        <label>
                                            <input type="radio" name="tutor_option" value="existing" onchange="toggleTutorEdit('e')"> Tutor existente
                                        </label>
                                    </div>

                                    <div id="te_create">
                                        <div class="row">
                                            <div class="form-group col-sm-6">
                                                <label>Email del tutor</label>
                                                <input type="email" name="tutor_email" class="form-control" placeholder="correo@ejemplo.com">
                                            </div>
                                            <div class="form-group col-sm-3">
                                                <label>Relación</label>
                                                <select name="relationship_type" class="form-control">
                                                    <option value="padre">Padre</option>
                                                    <option value="madre">Madre</option>
                                                    <option value="tutor_legal">Tutor Legal</option>
                                                    <option value="abuelo">Abuelo/a</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="te_existing" style="display:none;">
                                        <div class="row">
                                            <div class="form-group col-sm-6">
                                                <label>Seleccionar tutor existente</label>
                                                <select name="existing_tutor_id" class="form-control">
                                                    <option value="">-- Seleccionar --</option>
                                                    <?php
                                                    $qe = $dbh->prepare("SELECT id, UserName FROM admin WHERE role='tutor' ORDER BY UserName ASC");
                                                    $qe->execute();
                                                    foreach ($qe->fetchAll(PDO::FETCH_OBJ) as $te) {
                                                        echo "<option value='{$te->id}'>" . htmlentities($te->UserName) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-sm-3">
                                                <label>Relación</label>
                                                <select name="relationship_type" class="form-control">
                                                    <option value="padre">Padre</option>
                                                    <option value="madre">Madre</option>
                                                    <option value="tutor_legal">Tutor Legal</option>
                                                    <option value="abuelo">Abuelo/a</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" name="add_tutor" class="btn btn-success btn-sm">
                                        <i class="fa fa-plus"></i> Agregar Tutor
                                    </button>
                                </form>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<script>
function toggleTutorEdit(mode) {
    document.getElementById('te_create').style.display  = (mode === 'c') ? 'block' : 'none';
    document.getElementById('te_existing').style.display = (mode === 'e') ? 'block' : 'none';
}
</script>

<!-- Incluye el pie de página -->
<?php include('includes/footer.php'); ?>

<?php } ?>

