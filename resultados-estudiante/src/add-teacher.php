<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include(__DIR__ . '/includes/config.php');

if (!isset($_SESSION['alogin'])) {
    header("Location: index.php");
    exit;
}

$page_title = "Agregar Docente";
$msg = "";
$error = "";
$teacher_credentials = "";
$lastInsertId = null;

function generatePassword($length = 8) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

if (isset($_POST['submit'])) {
    $teacher_name = $_POST['fullname'] ?? '';
    $teacher_email = $_POST['emailid'] ?? '';
    $gender = $_POST['gender'] ?? 'Male';
    $dob = $_POST['dob'] ?? date('Y-m-d');
    $password = generatePassword(8);

    try {
        // Paso 1: Insertar en tblteachers
        $sql1 = "INSERT INTO tblteachers (TeacherName, TeacherEmail, Gender, DOB, Status, Password) 
                 VALUES(:name, :email, :gender, :dob, 1, :password)";
        $q1 = $dbh->prepare($sql1);
        $q1->bindParam(':name', $teacher_name, PDO::PARAM_STR);
        $q1->bindParam(':email', $teacher_email, PDO::PARAM_STR);
        $q1->bindParam(':gender', $gender, PDO::PARAM_STR);
        $q1->bindParam(':dob', $dob, PDO::PARAM_STR);
        $q1->bindParam(':password', $password, PDO::PARAM_STR);
        $q1->execute();

        $lastInsertId = $dbh->lastInsertId();

        if ($lastInsertId) {
            // Paso 2: Crear cuenta en admin
            $sql2 = "INSERT INTO admin (UserName, Password, role, teacher_id) 
                     VALUES(:username, :password, 'teacher', :teacher_id)";
            $q2 = $dbh->prepare($sql2);
            $q2->bindParam(':username', $teacher_email, PDO::PARAM_STR);
            $q2->bindParam(':password', $password, PDO::PARAM_STR);
            $q2->bindParam(':teacher_id', $lastInsertId, PDO::PARAM_INT);

            if ($q2->execute()) {
                $teacher_credentials = "
                <div class='alert alert-success'>
                    <h4><strong>✅ Docente Creado Exitosamente</strong></h4>
                    <table style='width: 100%; margin-top: 1rem;'>
                        <tr><td style='padding: 0.5rem;'><strong>Nombre:</strong></td><td>" . htmlentities($teacher_name) . "</td></tr>
                        <tr><td style='padding: 0.5rem;'><strong>Email (Usuario):</strong></td><td><code>" . htmlentities($teacher_email) . "</code></td></tr>
                        <tr><td style='padding: 0.5rem;'><strong>Contraseña:</strong></td><td><code style='background: #fff3cd; padding: 0.3rem 0.6rem;'>" . htmlentities($password) . "</code></td></tr>
                    </table>
                    <p style='margin-top: 1rem; font-size: 0.9rem; color: #666;'>⚠️ Comparte estas credenciales con el docente. Puede cambiar su contraseña en primer acceso.</p>
                </div>";
                
                $msg = "✅ Docente agregado correctamente. Ahora puedes asignarle materias.";
                error_log("✅ Docente creado: TeacherId=$lastInsertId, Email=$teacher_email");
            } else {
                $error = "❌ Error al crear la cuenta de acceso: " . $q2->errorInfo()[2];
                error_log("❌ Error creating teacher login: " . $q2->errorInfo()[2]);
            }
        } else {
            $error = "⚠️ Error al insertar el docente.";
        }
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
        error_log("Exception in add-teacher: " . $e->getMessage());
    }
}

?>
<?php include('includes/header.php'); ?>

<div style="max-width: 700px;">
    <h1 style="color: #333; margin-bottom: 1.5rem;">
        <i class="fas fa-chalkboard-user"></i> Agregar Docente
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

    <?php if ($teacher_credentials): ?>
        <?php echo $teacher_credentials; ?>
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem; flex-wrap: wrap;">
            <a href="manage-teacher.php" class="btn btn-primary">
                <i class="fas fa-list"></i> Ver Todos los Docentes
            </a>
            <a href="add-teacher.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Agregar Otro Docente
            </a>
            <?php if ($lastInsertId): ?>
                <a href="assign-teacher-subject.php?teacherid=<?php echo $lastInsertId; ?>" class="btn btn-warning">
                    <i class="fas fa-link"></i> Asignar Materias
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!$teacher_credentials): ?>
    <div class="card">
        <div class="card-header">
            Información del Docente
        </div>
        <div class="card-body">
            <form method="post">
                <div class="form-group">
                    <label for="fullname">Nombre Completo *</label>
                    <input type="text" id="fullname" name="fullname" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="emailid">Email *</label>
                    <input type="email" id="emailid" name="emailid" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Género</label>
                    <div style="display: flex; gap: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="radio" name="gender" value="Male" checked>
                            Masculino
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="radio" name="gender" value="Female">
                            Femenino
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="radio" name="gender" value="Other">
                            Otro
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="dob">Fecha de Nacimiento</label>
                    <input type="date" id="dob" name="dob" class="form-control">
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" name="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Agregar Docente
                    </button>
                    <a href="manage-teacher.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include('includes/footer.php'); ?>
