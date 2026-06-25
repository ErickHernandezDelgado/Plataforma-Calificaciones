<?php
/**
 * add-students.php
 * Sistema de Gestión de Calificaciones IPT
 * Agregar estudiantes de forma individual o masiva
 */

// Validación centralizada de sesión
include(__DIR__ . '/includes/check-login.php');

// Verificación de rol admin (check-login.php solo valida sesión, no rol)
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Cargar dependencias
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

error_reporting(0);
ini_set('display_errors', 0);

// Genera un token CSRF para proteger los formularios
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Variables de control
$msg = "";
$error = "";
$tutor_credentials = "";

// Relaciones válidas según el ENUM de student_tutor
$relaciones_validas = ['padre', 'madre', 'tutor_legal', 'abuelo'];

/** * LÓGICA DE FUNCIONES AUXILIARES 
 */

// Generar clave legible
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

// Crear tutor con contraseña cifrada en bcrypt (el login acepta bcrypt y MD5 legacy)
function createTutor($dbh, $email, $name) {
    $raw_pass = generatePassword(8);
    $hash_pass = password_hash($raw_pass, PASSWORD_DEFAULT);
    $role = 'tutor';

    $check = $dbh->prepare("SELECT id FROM admin WHERE UserName = :u");
    $check->execute([':u' => $email]);
    if ($check->rowCount() > 0) return 'exists';

    $sql = "INSERT INTO admin (UserName, Password, role) VALUES(:u, :p, :r)";
    $query = $dbh->prepare($sql);
    if ($query->execute([':u' => $email, ':p' => $hash_pass, ':r' => $role])) {
        return ['id' => $dbh->lastInsertId(), 'email' => $email, 'pass' => $raw_pass];
    }
    return null;
}

// Vincular alumno y tutor
function linkTutor($dbh, $sid, $tid, $rel) {
    $sql = "INSERT INTO student_tutor (StudentId, TutorId, RelationshipType, PrimaryContact) VALUES(:sid, :tid, :rel, 1)";
    $q = $dbh->prepare($sql);
    $q->execute([':sid' => $sid, ':tid' => $tid, ':rel' => $rel]);
    
    $upd = $dbh->prepare("UPDATE tblstudents SET primary_tutor_id = :tid WHERE StudentId = :sid");
    return $upd->execute([':tid' => $tid, ':sid' => $sid]);
}

/** * PROCESAMIENTO DE FORMULARIOS 
 */

// Registro Individual
if (isset($_POST['submit'])) {
    // Validación CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Solicitud no válida. Recarga la página e inténtalo de nuevo.";
    } else {
        $name = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['emailid'] ?? '');
        $curp = trim($_POST['curp'] ?? '');
        $classid = intval($_POST['class'] ?? 0);
        $tutor_option = $_POST['tutor_option'] ?? '';
        $relationship = $_POST['relationship_type'] ?? 'padre';

        // Validación del lado servidor
        if ($name === '' || $email === '') {
            $error = "El nombre y el correo del estudiante son obligatorios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "El correo del estudiante no es válido.";
        } elseif ($classid < 1) {
            $error = "Selecciona un grupo/grado válido.";
        } elseif (!in_array($relationship, $relaciones_validas, true)) {
            $error = "Selecciona una relación de tutor válida.";
        } else {
            // Verifica que el grupo exista
            $chkClass = $dbh->prepare("SELECT id FROM tblclasses WHERE id = :id");
            $chkClass->execute([':id' => $classid]);
            if (!$chkClass->fetch()) {
                $error = "El grupo/grado seleccionado no existe.";
            } else {
                // Transacción: alumno + tutor se crean juntos o no se crea nada
                try {
                    $dbh->beginTransaction();

                    $sql = "INSERT INTO tblstudents(StudentName, StudentEmail, Curp, ClassId, Status) VALUES(:n, :e, :c, :cl, 1)";
                    $query = $dbh->prepare($sql);
                    $query->execute([':n' => $name, ':e' => $email, ':c' => $curp, ':cl' => $classid]);
                    $student_id = $dbh->lastInsertId();

                    if ($tutor_option === 'create') {
                        // OPCIÓN 1: CREAR TUTOR NUEVO
                        $t_email = trim($_POST['tutor_email'] ?? '');
                        if (!filter_var($t_email, FILTER_VALIDATE_EMAIL)) {
                            throw new RuntimeException("El correo del tutor no es válido.");
                        }
                        $t_info = createTutor($dbh, $t_email, trim($_POST['tutor_name'] ?? ''));

                        if ($t_info === 'exists') {
                            throw new RuntimeException("El correo del tutor ya existe en el sistema.");
                        } elseif ($t_info) {
                            linkTutor($dbh, $student_id, $t_info['id'], $relationship);
                            $dbh->commit();
                            $tutor_credentials = "
                            <div class='alert alert-warning' style='border: 2px solid #856404;'>
                                <strong>🔑 CREDENCIALES DEL TUTOR:</strong><br>
                                Usuario: <b>" . htmlentities($t_info['email']) . "</b> | Contraseña: <b style='color:red;'>" . htmlentities($t_info['pass']) . "</b>
                            </div>";
                            $msg = "Estudiante y Tutor creados con éxito.";
                        } else {
                            throw new RuntimeException("No se pudo crear el tutor.");
                        }
                    } elseif ($tutor_option === 'existing') {
                        // OPCIÓN 2: VINCULAR TUTOR EXISTENTE
                        $existing_tutor_id = intval($_POST['existing_tutor_id'] ?? 0);
                        if ($existing_tutor_id < 1) {
                            throw new RuntimeException("Debe seleccionar un tutor válido.");
                        }
                        $tutor_query = $dbh->prepare("SELECT UserName FROM admin WHERE id = :tid AND role = 'tutor'");
                        $tutor_query->execute([':tid' => $existing_tutor_id]);
                        $tutor_info = $tutor_query->fetch(PDO::FETCH_OBJ);

                        if (!$tutor_info) {
                            throw new RuntimeException("El tutor seleccionado no existe en el sistema.");
                        }
                        linkTutor($dbh, $student_id, $existing_tutor_id, $relationship);
                        $dbh->commit();
                        $msg = "Estudiante vinculado con tutor existente correctamente.";
                        $tutor_credentials = "
                        <div class='alert alert-success' style='border: 2px solid #155724;'>
                            <strong>✓ TUTOR EXISTENTE VINCULADO:</strong><br>
                            Email del Tutor: <b>" . htmlentities($tutor_info->UserName) . "</b><br>
                            Relación: <b>" . htmlentities(ucfirst($relationship)) . "</b><br>
                            <em>El tutor puede acceder a las calificaciones de este estudiante</em>
                        </div>";
                    } else {
                        throw new RuntimeException("Selecciona una opción de tutor.");
                    }
                } catch (PDOException $e) {
                    // PDOException debe ir ANTES que RuntimeException porque es una subclase de ella;
                    // si no, el catch de RuntimeException la atraparía y mostraría el mensaje SQL crudo.
                    if ($dbh->inTransaction()) $dbh->rollBack();
                    if ($e->getCode() == 23000) {
                        $error = "Ya existe un estudiante o tutor registrado con ese correo electrónico.";
                    } else {
                        $error = "No se pudo registrar al estudiante. Intenta de nuevo.";
                    }
                } catch (RuntimeException $e) {
                    if ($dbh->inTransaction()) $dbh->rollBack();
                    $error = $e->getMessage();
                }
            }
        }
    }
}

// Importación Excel
// TODO (pendiente auditoría): este bloque necesita endurecerse — validación de tipo de archivo,
// transacción, validación de filas y de ClassId, y creación de tutores. No tocado por decisión del usuario.
if (isset($_POST['import_excel']) && isset($_FILES['excel_file'])) {
    $spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);
    $data = $spreadsheet->getActiveSheet()->toArray();
    $count = 0;
    foreach ($data as $key => $row) {
        if ($key == 0) continue;
        $sql = "INSERT INTO tblstudents(StudentName, StudentEmail, CURP, ClassId, Status) VALUES(?,?,?,?,1)";
        $dbh->prepare($sql)->execute([$row[0], $row[1], $row[2], $row[3]]);
        $count++;
    }
    $msg = "Se importaron $count registros.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IPT | Agregar Estudiante</title>
    <link rel="stylesheet" href="css/bootstrap.min.css" media="screen">
    <link rel="stylesheet" href="css/font-awesome.min.css" media="screen">
    <link rel="stylesheet" href="css/main.css" media="screen">
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
                            <div class="col-md-6"><h2 class="title">Inscripción de Alumnos</h2></div>
                        </div>
                        
                        <section class="section">
                            <div class="panel">
                                <div class="panel-body">
                                    <?php if($msg) echo "<div class='alert alert-success'>" . htmlentities($msg) . "</div>"; ?>
                                    <?php if($error) echo "<div class='alert alert-danger'>" . htmlentities($error) . "</div>"; ?>
                                    <?php if($tutor_credentials) echo $tutor_credentials; ?>

                                    <form method="post" class="row">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">
                                        <div class="form-group col-md-6">
                                            <label>Nombre Completo</label>
                                            <input type="text" name="fullname" class="form-control" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Correo Electrónico</label>
                                            <input type="email" name="emailid" class="form-control" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>CURP</label>
                                            <input type="text" name="curp" class="form-control" maxlength="18">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Clase/Grado</label>
                                            <select name="class" class="form-control" required>
                                                <option value="">Seleccione...</option>
                                                <?php 
                                                $q = $dbh->prepare("SELECT * FROM vw_classes_for_enrollment");
                                                $q->execute();
                                                foreach($q->fetchAll(PDO::FETCH_OBJ) as $c) {
                                                    echo "<option value='" . (int)$c->id . "'>" . htmlentities($c->ClassName_display) . "</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <div class="col-md-12"><hr><h4>Datos del Tutor</h4></div>
                                        <div class="form-group col-md-12">
                                            <label for="opt1">Nuevo</label>
                                            <input type="radio" name="tutor_option" value="create" checked id="opt1" onchange="toggleTutor('c')"> 
                                            <label for="opt2">Existente</label>
                                            <input type="radio" name="tutor_option" value="existing" id="opt2" onchange="toggleTutor('e')"> 
                                        </div>

                                        <div id="c_fields" class="col-md-12">
                                            <div class="row">
                                                <div class="form-group col-md-4"><label>Nombre</label><input type="text" name="tutor_name" class="form-control"></div>
                                                <div class="form-group col-md-4"><label>Email</label><input type="email" name="tutor_email" class="form-control"></div>
                                                <div class="form-group col-md-4">
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
                                        
                                        <div id="e_fields" class="col-md-12" style="display:none;">
                                            <label>Seleccionar Tutor</label>
                                            <select name="existing_tutor_id" class="form-control">
                                                <?php
                                                $qt = $dbh->prepare("SELECT id, UserName FROM admin WHERE role='tutor'");
                                                $qt->execute();
                                                foreach($qt->fetchAll(PDO::FETCH_OBJ) as $t) echo "<option value='" . (int)$t->id . "'>" . htmlentities($t->UserName) . "</option>";
                                                ?>
                                            </select>
                                        </div>

                                        <div class="col-md-12"><br><button type="submit" name="submit" class="btn btn-primary">Registrar</button></div>
                                    </form>

                                    <hr>
                                    <form method="post" enctype="multipart/form-data">
                                        <label>Carga Masiva (Excel)</label>
                                        <input type="file" name="excel_file" accept=".xlsx" required>
                                        <button type="submit" name="import_excel" class="btn btn-info">Importar</button>
                                    </form>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jquery/jquery-2.2.4.min.js"></script>
    <script src="js/bootstrap/bootstrap.min.js"></script>
    <script>
        function toggleTutor(mode) {
            document.getElementById('c_fields').style.display = (mode === 'c') ? 'block' : 'none';
            document.getElementById('e_fields').style.display = (mode === 'e') ? 'block' : 'none';
        }
    </script>
</body>
</html>
<?php include('includes/footer.php'); ?>