<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include(__DIR__ . '/includes/config.php');

if (!isset($_SESSION['alogin'])) {
    header("Location: index.php");
    exit;
}

$page_title = "Agregar Estudiante";
$msg = "";
$error = "";
$tutor_credentials = "";

// Funciones auxiliares
function generatePassword($length = 8) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

function createTutor($dbh, $email, $name) {
    $password = generatePassword(8);
    $role = 'tutor';
    
    $sql = "INSERT INTO admin (UserName, Password, role, teacher_id) VALUES(:username, :password, :role, NULL)";
    $query = $dbh->prepare($sql);
    $query->bindParam(':username', $email, PDO::PARAM_STR);
    $query->bindParam(':password', $password, PDO::PARAM_STR);
    $query->bindParam(':role', $role, PDO::PARAM_STR);
    
    if ($query->execute()) {
        $tutor_id = $dbh->lastInsertId();
        error_log("✅ Tutor creado: ID=$tutor_id, Email=$email");
        return ['id' => $tutor_id, 'email' => $email, 'password' => $password, 'name' => $name];
    } else {
        error_log("❌ Error creating tutor: " . $query->errorInfo()[2]);
        return null;
    }
}

function linkTutorToStudent($dbh, $student_id, $tutor_id, $relationship) {
    $sql = "INSERT INTO student_tutor (StudentId, TutorId, RelationshipType, PrimaryContact, CanViewGrades) 
            VALUES(:student_id, :tutor_id, :relationship, 1, 1)";
    $query = $dbh->prepare($sql);
    $query->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $query->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
    $query->bindParam(':relationship', $relationship, PDO::PARAM_STR);
    return $query->execute();
}

// Procesar formulario
if (isset($_POST['submit'])) {
    $studentname = $_POST['fullname'] ?? '';
    $studentemail = $_POST['emailid'] ?? '';
    $curp = $_POST['curp'] ?? '';
    $classid = $_POST['classid'] ?? '';
    $tutor_option = $_POST['tutor_option'] ?? '';
    $status = 1;

    if ($studentname && $studentemail && $classid) {
        try {
            $sql = "INSERT INTO tblstudents (StudentName, StudentEmail, CURP, ClassId, Status) 
                    VALUES(:studentname, :studentemail, :curp, :classid, :status)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':studentname', $studentname);
            $query->bindParam(':studentemail', $studentemail);
            $query->bindParam(':curp', $curp);
            $query->bindParam(':classid', $classid);
            $query->bindParam(':status', $status);
            $query->execute();

            $student_id = $dbh->lastInsertId();

            if ($student_id) {
                if ($tutor_option === 'create') {
                    $tutor_name = $_POST['tutor_name'] ?? '';
                    $tutor_email = $_POST['tutor_email'] ?? strtolower(str_replace(' ', '.', $studentname)) . '.tutor@ipt.edu.mx';
                    $relationship = $_POST['relationship_type'] ?? 'padre';

                    $tutor_info = createTutor($dbh, $tutor_email, $tutor_name);
                    if ($tutor_info) {
                        linkTutorToStudent($dbh, $student_id, $tutor_info['id'], $relationship);
                        
                        $tutor_credentials = "
                        <div class='alert alert-success'>
                            <h4><strong>✅ Tutor Creado Exitosamente</strong></h4>
                            <table style='width: 100%; margin-top: 1rem;'>
                                <tr><td style='padding: 0.5rem;'><strong>Nombre:</strong></td><td>" . htmlentities($tutor_info['name']) . "</td></tr>
                                <tr><td style='padding: 0.5rem;'><strong>Email (Usuario):</strong></td><td><code>" . htmlentities($tutor_info['email']) . "</code></td></tr>
                                <tr><td style='padding: 0.5rem;'><strong>Contraseña:</strong></td><td><code style='background: #fff3cd; padding: 0.3rem 0.6rem;'>" . htmlentities($tutor_info['password']) . "</code></td></tr>
                            </table>
                            <p style='margin-top: 1rem; font-size: 0.9rem; color: #666;'>⚠️ Entrega estas credenciales al padre/tutor. Puede cambiar su contraseña en primer acceso.</p>
                        </div>";
                        
                        $msg = "✅ Estudiante y tutor agregados correctamente.";
                    } else {
                        $error = "❌ Error al crear la cuenta del tutor.";
                    }
                } elseif ($tutor_option === 'existing') {
                    $tutor_id = $_POST['existing_tutor_id'] ?? null;
                    if ($tutor_id) {
                        linkTutorToStudent($dbh, $student_id, $tutor_id, $_POST['relationship_type'] ?? 'padre');
                        $msg = "✅ Estudiante agregado con tutor existente.";
                    } else {
                        $error = "⚠️ Selecciona un tutor.";
                    }
                }
            }
        } catch (Exception $e) {
            $error = "❌ Error: " . $e->getMessage();
        }
    } else {
        $error = "❌ Completa todos los campos requeridos.";
    }
}

// Obtener tutores existentes
$tutors = [];
try {
    $sql_tutors = "SELECT id, UserName FROM admin WHERE role = 'tutor' ORDER BY UserName";
    $q_tutors = $dbh->prepare($sql_tutors);
    $q_tutors->execute();
    $tutors = $q_tutors->fetchAll(PDO::FETCH_OBJ);
} catch (Exception $e) {
    error_log("Error fetching tutors: " . $e->getMessage());
}

// Obtener clases
$classes = [];
try {
    $sql_classes = "SELECT id, CONCAT(ClassName, ' ', Section) as label FROM tblclasses ORDER BY ClassName";
    $q_classes = $dbh->prepare($sql_classes);
    $q_classes->execute();
    $classes = $q_classes->fetchAll(PDO::FETCH_OBJ);
} catch (Exception $e) {
    error_log("Error fetching classes: " . $e->getMessage());
}

?>
<?php include('includes/header.php'); ?>

<div style="max-width: 700px;">
    <h1 style="color: #333; margin-bottom: 1.5rem;">
        <i class="fas fa-user-plus"></i> Agregar Estudiante + Tutor
    </h1>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($msg): ?>
        <div class="alert alert-success">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <?php if ($tutor_credentials): ?>
        <?php echo $tutor_credentials; ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            Información del Estudiante
        </div>
        <div class="card-body">
            <form method="post">
                <div class="form-group">
                    <label for="fullname">Nombre Completo *</label>
                    <input type="text" id="fullname" name="fullname" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="emailid">Email del Estudiante</label>
                    <input type="email" id="emailid" name="emailid" class="form-control">
                </div>

                <div class="form-group">
                    <label for="curp">CURP</label>
                    <input type="text" id="curp" name="curp" class="form-control" maxlength="18">
                </div>

                <div class="form-group">
                    <label for="classid">Año Escolar *</label>
                    <select id="classid" name="classid" class="form-control" required>
                        <option value="">-- Seleccionar Año --</option>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?php echo $cls->id; ?>"><?php echo htmlentities($cls->label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="border: 1px solid #ddd; padding: 1rem; border-radius: 8px; margin: 1.5rem 0;">
                    <h4 style="margin-top: 0;">Tutor / Padre de Familia</h4>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="radio" name="tutor_option" value="create" checked>
                            <span>🆕 Crear Nuevo Tutor</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="radio" name="tutor_option" value="existing">
                            <span>👥 Usar Tutor Existente</span>
                        </label>
                    </div>

                    <!-- Create Tutor Section -->
                    <div id="create-tutor" style="display: block;">
                        <div class="form-group">
                            <label for="tutor_name">Nombre del Tutor</label>
                            <input type="text" id="tutor_name" name="tutor_name" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="tutor_email">Email del Tutor</label>
                            <input type="email" id="tutor_email" name="tutor_email" class="form-control" placeholder="Ej: padre@example.com">
                        </div>

                        <div class="form-group">
                            <label for="relationship_type">Relación con el Estudiante</label>
                            <select id="relationship_type" name="relationship_type" class="form-control">
                                <option value="padre">Padre</option>
                                <option value="madre">Madre</option>
                                <option value="abuelo">Abuelo</option>
                                <option value="tutor_legal">Tutor Legal</option>
                            </select>
                        </div>
                    </div>

                    <!-- Existing Tutor Section -->
                    <div id="existing-tutor" style="display: none;">
                        <div class="form-group">
                            <label for="existing_tutor_id">Seleccionar Tutor</label>
                            <select id="existing_tutor_id" name="existing_tutor_id" class="form-control">
                                <option value="">-- Seleccionar Tutor --</option>
                                <?php foreach ($tutors as $tutor): ?>
                                    <option value="<?php echo $tutor->id; ?>"><?php echo htmlentities($tutor->UserName); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" name="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Agregar Estudiante
                    </button>
                    <a href="manage-students.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('input[name="tutor_option"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('create-tutor').style.display = this.value === 'create' ? 'block' : 'none';
        document.getElementById('existing-tutor').style.display = this.value === 'existing' ? 'block' : 'none';
    });
});
</script>

<?php include('includes/footer.php'); ?>
