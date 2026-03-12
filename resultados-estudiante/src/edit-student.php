<?php
/**
 * edit-student.php - Edición unificada de Alumno + Tutor
 * 
 * Permite editar:
 * - Datos del alumno (nombre, email, estado, etc.)
 * - Datos del tutor (crear nuevo, asignar existente, cambiar contraseña)
 * - Vinculación alumno-tutor (student_tutor)
 */

session_start();
error_reporting(0);
include(__DIR__ . '/includes/config.php');

if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php");
    exit;
} else {
    $stid = intval($_GET['stid']);
    $msg = '';
    $error = '';

    // ====== OBTENER DATOS ACTUALES DEL ALUMNO Y TUTOR ======
    $sql = "SELECT 
                s.StudentId, s.StudentName, s.RollId, s.StudentEmail, s.Status, s.CURP, s.ClassId,
                c.ClassName, c.Section, c.AcademicYear,
                st.TutorId, st.RelationshipType, st.email_login,
                a.UserName as TutorEmail, a.Password as TutorPassword
            FROM tblstudents s
            JOIN tblclasses c ON c.id = s.ClassId
            LEFT JOIN student_tutor st ON s.StudentId = st.StudentId AND st.PrimaryContact = 1
            LEFT JOIN admin a ON st.TutorId = a.id
            WHERE s.StudentId = :stid";
    
    $query = $dbh->prepare($sql);
    $query->bindParam(':stid', $stid, PDO::PARAM_INT);
    $query->execute();
    $student_data = $query->fetch(PDO::FETCH_OBJ);
    
    if (!$student_data) {
        die("<div class='alert alert-danger'>Estudiante no encontrado.</div>");
    }

    // ====== PROCESAR FORMULARIO DE ACTUALIZACIÓN ======
    if (isset($_POST['submit'])) {
        $studentname = $_POST['fullname'] ?? '';
        $studentemail = $_POST['emailid'] ?? '';
        $curp = $_POST['curp'] ?? '';
        $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
        $rollid = $_POST['rollid'] ?? '';
        
        // Validaciones
        if (empty($studentname)) {
            $error = "❌ El nombre del estudiante es requerido.";
        } elseif (empty($studentemail)) {
            $error = "❌ El email del estudiante es requerido.";
        } else {
            // PASO 1: Actualizar datos del estudiante
            $sql_update = "UPDATE tblstudents 
                          SET StudentName = :studentname, RollId = :rollid, StudentEmail = :studentemail, 
                              CURP = :curp, Status = :status 
                          WHERE StudentId = :stid";
            
            $query_update = $dbh->prepare($sql_update);
            $query_update->bindParam(':studentname', $studentname, PDO::PARAM_STR);
            $query_update->bindParam(':rollid', $rollid, PDO::PARAM_STR);
            $query_update->bindParam(':studentemail', $studentemail, PDO::PARAM_STR);
            $query_update->bindParam(':curp', $curp, PDO::PARAM_STR);
            $query_update->bindParam(':status', $status, PDO::PARAM_INT);
            $query_update->bindParam(':stid', $stid, PDO::PARAM_INT);
            
            try {
                $query_update->execute();
                
                // PASO 2: Manejar Tutor
                $tutor_option = $_POST['tutor_option'] ?? null;
                
                if ($tutor_option === 'create_new') {
                    // Crear nuevo tutor
                    $tutor_name = $_POST['tutor_name'] ?? '';
                    $tutor_email = $_POST['tutor_email'] ?? '';
                    $tutor_password = $_POST['tutor_password'] ?? 'tutor123';
                    
                    if (empty($tutor_name) || empty($tutor_email)) {
                        $error = "❌ Para crear tutor, nombre y email son requeridos.";
                    } else {
                        // Insertar nuevo tutor en tabla admin
                        $sql_tutor = "INSERT INTO admin (UserName, Password, role) VALUES (:username, :password, 'tutor')";
                        $query_tutor = $dbh->prepare($sql_tutor);
                        $query_tutor->bindParam(':username', $tutor_email, PDO::PARAM_STR);
                        $query_tutor->bindParam(':password', $tutor_password, PDO::PARAM_STR);
                        
                        try {
                            $query_tutor->execute();
                            $new_tutor_id = $dbh->lastInsertId();
                            
                            // Eliminar relación anterior si existe
                            $sql_del = "DELETE FROM student_tutor WHERE StudentId = :stid AND PrimaryContact = 1";
                            $dbh->prepare($sql_del)->execute([':stid' => $stid]);
                            
                            // Crear vinculación nueva
                            $sql_link = "INSERT INTO student_tutor (StudentId, TutorId, email_login, RelationshipType, PrimaryContact, CanViewGrades, CanDownloadReport) 
                                       VALUES (:sid, :tid, :email_login, 'padre', 1, 1, 1)";
                            $query_link = $dbh->prepare($sql_link);
                            $query_link->execute([
                                ':sid' => $stid,
                                ':tid' => $new_tutor_id,
                                ':email_login' => $studentemail
                            ]);
                            
                            $msg = "✅ Alumno actualizado y tutor creado exitosamente!";
                        } catch (Exception $e) {
                            $error = "❌ Error al crear tutor: " . $e->getMessage();
                        }
                    }
                    
                } elseif ($tutor_option === 'assign_existing') {
                    // Asignar tutor existente
                    $existing_tutor_id = intval($_POST['existing_tutor'] ?? 0);
                    
                    if ($existing_tutor_id <= 0) {
                        $error = "❌ Debe seleccionar un tutor existente.";
                    } else {
                        // Eliminar relación anterior si existe
                        $sql_del = "DELETE FROM student_tutor WHERE StudentId = :stid AND PrimaryContact = 1";
                        $dbh->prepare($sql_del)->execute([':stid' => $stid]);
                        
                        // Crear vinculación nueva
                        $sql_link = "INSERT INTO student_tutor (StudentId, TutorId, email_login, RelationshipType, PrimaryContact, CanViewGrades, CanDownloadReport) 
                                   VALUES (:sid, :tid, :email_login, 'padre', 1, 1, 1)";
                        $query_link = $dbh->prepare($sql_link);
                        $query_link->execute([
                            ':sid' => $stid,
                            ':tid' => $existing_tutor_id,
                            ':email_login' => $studentemail
                        ]);
                        
                        $msg = "✅ Alumno y tutor actualizado correctamente!";
                    }
                    
                } elseif ($tutor_option === 'no_change' || $tutor_option === null) {
                    // Sin cambios en tutor
                    $msg = "✅ Información del alumno actualizada correctamente!";
                }
                
                // Recargar datos del estudiante
                $query = $dbh->prepare($sql);
                $query->bindParam(':stid', $stid, PDO::PARAM_INT);
                $query->execute();
                $student_data = $query->fetch(PDO::FETCH_OBJ);
                
            } catch (Exception $e) {
                $error = "❌ Error al actualizar: " . $e->getMessage();
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
                        <h2 class="title">Editar Estudiante y Tutor</h2>
                    </div>
                </div>

                <!-- Breadcrumb de navegación -->
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li><a href="manage-students.php">Estudiantes</a></li>
                            <li class="active">Editar Estudiante</li>
                        </ul>
                    </div>
                </div>
            </div>

            <section class="section">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-10 col-md-offset-1">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>✏️ Editar Estudiante y Tutor</h5>
                                        <p style="color: #666; font-size: 12px; margin-top: 5px;">Actualiza información del alumno y gestiona su tutor (padre/madre)</p>
                                    </div>
                                </div>

                                <div class="panel-body">
                                    <!-- Mensajes de éxito o error -->
                                    <?php if ($msg) { ?>
                                        <div class="alert alert-success left-icon-alert" role="alert">
                                            <i class="fa fa-check-circle"></i> <strong>Éxito!</strong> <?php echo htmlentities($msg); ?>
                                        </div>
                                    <?php } ?>
                                    
                                    <?php if ($error) { ?>
                                        <div class="alert alert-danger left-icon-alert" role="alert">
                                            <i class="fa fa-times-circle"></i> <strong>Error!</strong> <?php echo htmlentities($error); ?>
                                        </div>
                                    <?php } ?>

                                    <!-- FORMULARIO DE EDICIÓN -->
                                    <form class="form-horizontal" method="post" id="editForm">
                                        
                                        <!-- ====== SECCIÓN 1: DATOS DEL ALUMNO ====== -->
                                        <div style="border-bottom: 2px solid #238D15; padding-bottom: 20px; margin-bottom: 30px;">
                                            <h4 style="color: #238D15; margin-bottom: 20px;">
                                                <i class="fa fa-user-circle"></i> Información del Alumno
                                            </h4>
                                            
                                            <!-- Nombre Completo -->
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">Nombre Completo *</label>
                                                <div class="col-sm-9">
                                                    <input type="text" name="fullname" class="form-control" 
                                                           value="<?php echo htmlentities($student_data->StudentName); ?>" required>
                                                </div>
                                            </div>

                                            <!-- ID Rol -->
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">ID Matrícula</label>
                                                <div class="col-sm-9">
                                                    <input type="text" name="rollid" class="form-control" maxlength="5" 
                                                           value="<?php echo htmlentities($student_data->RollId); ?>">
                                                </div>
                                            </div>

                                            <!-- Email del Alumno -->
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">Email del Alumno *</label>
                                                <div class="col-sm-9">
                                                    <input type="email" name="emailid" class="form-control" 
                                                           value="<?php echo htmlentities($student_data->StudentEmail); ?>" required>
                                                    <small style="color: #666;">Este email se usa para login del tutor</small>
                                                </div>
                                            </div>

                                            <!-- CURP -->
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">CURP</label>
                                                <div class="col-sm-9">
                                                    <input type="text" name="curp" class="form-control" maxlength="18" 
                                                           value="<?php echo htmlentities($student_data->CURP); ?>">
                                                </div>
                                            </div>

                                            <!-- Grado y Sección (solo lectura) -->
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">Grado Actual</label>
                                                <div class="col-sm-9">
                                                    <p class="form-control-static">
                                                        <strong><?php echo htmlentities($student_data->ClassName); ?> - Sección <?php echo htmlentities($student_data->Section); ?></strong>
                                                        <span style="color: #999; margin-left: 10px;">(Año: <?php echo htmlentities($student_data->AcademicYear); ?>)</span>
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Estado -->
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">Estado</label>
                                                <div class="col-sm-9">
                                                    <label class="radio-inline">
                                                        <input type="radio" name="status" value="1" <?php echo ($student_data->Status == 1) ? 'checked' : ''; ?>> 
                                                        <i class="fa fa-check-circle" style="color: green;"></i> Activo
                                                    </label>
                                                    <label class="radio-inline">
                                                        <input type="radio" name="status" value="0" <?php echo ($student_data->Status == 0) ? 'checked' : ''; ?>> 
                                                        <i class="fa fa-ban" style="color: red;"></i> Inactivo
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ====== SECCIÓN 2: DATOS DEL TUTOR ====== -->
                                        <div style="padding-bottom: 20px; margin-bottom: 30px;">
                                            <h4 style="color: #238D15; margin-bottom: 20px;">
                                                <i class="fa fa-family"></i> Información del Tutor (Padre/Madre)
                                            </h4>

                                            <!-- Tutor Actual (información) -->
                                            <?php if ($student_data->TutorEmail): ?>
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label">Tutor Actual</label>
                                                    <div class="col-sm-9">
                                                        <p class="form-control-static">
                                                            <strong><?php echo htmlentities($student_data->TutorEmail); ?></strong><br>
                                                            <small style="color: #666;">
                                                                Relación: <?php echo htmlentities(ucfirst($student_data->RelationshipType)); ?> | 
                                                                Contraseña: <code><?php echo htmlentities($student_data->TutorPassword); ?></code>
                                                            </small>
                                                        </p>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-warning" style="margin-bottom: 20px;">
                                                    <i class="fa fa-exclamation-triangle"></i> Este alumno aún no tiene tutor asignado.
                                                </div>
                                            <?php endif; ?>

                                            <!-- Opciones de Tutor -->
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">Acción con Tutor</label>
                                                <div class="col-sm-9">
                                                    <label class="radio">
                                                        <input type="radio" name="tutor_option" value="no_change" checked> 
                                                        No cambiar (mantener tutor actual)
                                                    </label>
                                                    <label class="radio">
                                                        <input type="radio" name="tutor_option" value="create_new" onchange="toggleTutorFields()"> 
                                                        Crear nuevo tutor
                                                    </label>
                                                    <label class="radio">
                                                        <input type="radio" name="tutor_option" value="assign_existing" onchange="toggleTutorFields()"> 
                                                        Asignar tutor existente
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- CREAR NUEVO TUTOR -->
                                            <div id="create_tutor_section" style="display: none; background: #f9f9f9; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                                                <h5 style="margin-top: 0;">Datos del Nuevo Tutor</h5>
                                                
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label">Nombre del Tutor *</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" name="tutor_name" class="form-control" 
                                                               placeholder="Ej: María García López">
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label">Email del Tutor *</label>
                                                    <div class="col-sm-9">
                                                        <input type="email" name="tutor_email" class="form-control" 
                                                               placeholder="Ej: maria.garcia@email.com">
                                                        <small style="color: #666;">Con este email se logea el tutor en el sistema</small>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label">Contraseña del Tutor</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" name="tutor_password" class="form-control" 
                                                               value="tutor123" placeholder="Default: tutor123">
                                                        <small style="color: #666;">Déjalo en blanco para usar: tutor123</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ASIGNAR TUTOR EXISTENTE -->
                                            <div id="assign_tutor_section" style="display: none; background: #f9f9f9; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                                                <h5 style="margin-top: 0;">Selecciona un Tutor Existente</h5>
                                                
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label">Tutor *</label>
                                                    <div class="col-sm-9">
                                                        <select name="existing_tutor" class="form-control">
                                                            <option value="">-- Selecciona un tutor --</option>
                                                            <?php 
                                                            // Obtener tutores disponibles
                                                            $sql_tutors = "SELECT id, UserName FROM admin WHERE role = 'tutor' ORDER BY UserName ASC";
                                                            $query_tutors = $dbh->prepare($sql_tutors);
                                                            $query_tutors->execute();
                                                            $tutors = $query_tutors->fetchAll(PDO::FETCH_OBJ);
                                                            
                                                            foreach ($tutors as $tutor) {
                                                                echo '<option value="' . htmlentities($tutor->id) . '">' . 
                                                                     htmlentities($tutor->UserName) . '</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- BOTONES -->
                                        <div class="form-group">
                                            <div class="col-sm-offset-3 col-sm-9">
                                                <a href="manage-students.php" class="btn btn-secondary" style="background-color: #999;">
                                                    <i class="fa fa-arrow-left"></i> Volver
                                                </a>
                                                <button type="submit" name="submit" class="btn btn-success" style="margin-left: 10px;">
                                                    <i class="fa fa-save"></i> Guardar Cambios
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

<!-- Incluye el pie de página -->
<?php include('includes/footer.php'); ?>

<script>
// Mostrar/Ocultar secciones de tutor según opción seleccionada
function toggleTutorFields() {
    const option = document.querySelector('input[name="tutor_option"]:checked').value;
    
    document.getElementById('create_tutor_section').style.display = 
        (option === 'create_new') ? 'block' : 'none';
    
    document.getElementById('assign_tutor_section').style.display = 
        (option === 'assign_existing') ? 'block' : 'none';
}

// Inicializar en carga
document.addEventListener('DOMContentLoaded', toggleTutorFields);
</script>

<?php } ?>

