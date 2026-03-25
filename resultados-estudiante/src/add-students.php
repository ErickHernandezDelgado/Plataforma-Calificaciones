<?php
/**
 * add-students.php
 * Sistema de Gestión de Calificaciones IPT
 * Desarrollado por: Residente
 */

// 1. INICIO DE SESIÓN SEGURO (Debe ser lo primero)
session_start();

// 2. CARGA DE DEPENDENCIAS Y CONFIGURACIÓN
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Importante: Usar __DIR__ para que la ruta sea absoluta y no falle la sesión
include(__DIR__ . '/includes/config.php');

// 3. VALIDACIÓN DE AUTENTICACIÓN
if (!isset($_SESSION['alogin']) || strlen($_SESSION['alogin']) == 0) {
    header("Location: index.php");
    exit;
}

// Variables de control
$msg = "";
$error = "";
$tutor_credentials = ""; 

/** * LÓGICA DE FUNCIONES AUXILIARES 
 */

// Generar clave legible
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

// Crear tutor con cifrado MD5 (Estándar del sistema)
function createTutor($dbh, $email, $name) {
    $raw_pass = generatePassword(8);
    $md5_pass = md5($raw_pass);
    $role = 'tutor';

    $check = $dbh->prepare("SELECT id FROM admin WHERE UserName = :u");
    $check->execute([':u' => $email]);
    if ($check->rowCount() > 0) return 'exists';

    $sql = "INSERT INTO admin (UserName, Password, role) VALUES(:u, :p, :r)";
    $query = $dbh->prepare($sql);
    $query->execute([':u' => $email, ':p' => $md5_pass, ':r' => $role]);

    if ($query) {
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
    $name = $_POST['fullanme'];
    $email = $_POST['emailid'];
    $curp = $_POST['curp'];
    $classid = $_POST['class'];
    
    $sql = "INSERT INTO tblstudents(StudentName, StudentEmail, CURP, ClassId, Status) VALUES(:n, :e, :c, :cl, 1)";
    $query = $dbh->prepare($sql);
    $query->execute([':n' => $name, ':e' => $email, ':c' => $curp, ':cl' => $classid]);
    $student_id = $dbh->lastInsertId();

    if ($student_id) {
        if ($_POST['tutor_option'] == 'create') {
            $t_email = $_POST['tutor_email'];
            $t_info = createTutor($dbh, $t_email, $_POST['tutor_name']);
            
            if ($t_info === 'exists') {
                $error = "El correo del tutor ya existe. Alumno creado sin vinculación.";
            } elseif ($t_info) {
                linkTutor($dbh, $student_id, $t_info['id'], $_POST['relationship_type']);
                $tutor_credentials = "
                <div class='alert alert-warning' style='border: 2px solid #856404;'>
                    <strong>🔑 CREDENCIALES DEL TUTOR:</strong><br>
                    Usuario: <b>{$t_info['email']}</b> | Contraseña: <b style='color:red;'>{$t_info['pass']}</b>
                </div>";
                $msg = "Estudiante y Tutor creados con éxito.";
            }
        }
    }
}

// Importación Excel
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
                                    <?php if($msg) echo "<div class='alert alert-success'>$msg</div>"; ?>
                                    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
                                    <?php if($tutor_credentials) echo $tutor_credentials; ?>

                                    <form method="post" class="row">
                                        <div class="form-group col-md-6">
                                            <label>Nombre Completo</label>
                                            <input type="text" name="fullanme" class="form-control" required>
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
                                                    echo "<option value='{$c->id}'>{$c->ClassName_display}</option>";
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
                                                foreach($qt->fetchAll(PDO::FETCH_OBJ) as $t) echo "<option value='{$t->id}'>{$t->UserName}</option>";
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