<?php
// Carga las dependencias necesarias para trabajar con Excel
require 'vendor/autoload.php'; // Asegúrate que la ruta sea correcta

// Importa clases necesarias de PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// Inicia sesión del usuario
session_start();

// Muestra todos los errores (bueno para depuración)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluye la conexión a base de datos
include(__DIR__ . '/includes/config.php');

// Verifica que el usuario esté autenticado
if (strlen($_SESSION['alogin']) == 0) {
    header("Location: index.php");
    exit;
}

// Variables de estado para mensajes
$msg = "";
$error = "";
$tutor_credentials = ""; // Para mostrar credenciales del tutor creado

// Función auxiliar: generar contraseña aleatoria
function generatePassword($length = 8) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

// Función auxiliar: crear tutor en base de datos
function createTutor($dbh, $tutor_email, $tutor_name) {
    $password = generatePassword(8);
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'tutor';
    
    $sql = "INSERT INTO admin (UserName, Password, role, teacher_id) 
            VALUES(:username, :password, :role, NULL)";
    $query = $dbh->prepare($sql);
    $query->bindParam(':username', $tutor_email, PDO::PARAM_STR);
    $query->bindParam(':password', $password_hash, PDO::PARAM_STR);
    $query->bindParam(':role', $role, PDO::PARAM_STR);
    
    if ($query->execute()) {
        $tutor_id = $dbh->lastInsertId();
        return ['id' => $tutor_id, 'email' => $tutor_email, 'password' => $password];
    }
    return null;
}

// Función auxiliar: vincular tutor con estudiante
function linkTutorToStudent($dbh, $student_id, $tutor_id, $relationship_type = 'padre') {
    $sql = "INSERT INTO student_tutor (StudentId, TutorId, RelationshipType, PrimaryContact, CanViewGrades, CanDownloadReport)
            VALUES(:student_id, :tutor_id, :relationship, 1, 1, 1)";
    $query = $dbh->prepare($sql);
    $query->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $query->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
    $query->bindParam(':relationship', $relationship_type, PDO::PARAM_STR);
    
    if ($query->execute()) {
        // Actualizar primary_tutor_id en estudiante
        $sql2 = "UPDATE tblstudents SET primary_tutor_id = :tutor_id WHERE StudentId = :student_id";
        $query2 = $dbh->prepare($sql2);
        $query2->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
        $query2->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        return $query2->execute();
    }
    return false;
}

// ------------------------
// Agregar un estudiante individualmente
// ------------------------
if (isset($_POST['submit'])) {
    // Recoge datos del formulario
    $studentname = $_POST['fullanme'];
    $roolid = $_POST['rollid'];
    $studentemail = $_POST['emailid'];
    $curp = $_POST['curp'];
    $classid = $_POST['class'];
    $status = 1; // Activo por defecto
    
    // Datos del tutor
    $tutor_option = $_POST['tutor_option'] ?? 'create'; // 'create' o 'existing'
    $tutor_id = null;
    $tutor_info = null;

    // Inserta el estudiante
    $sql = "INSERT INTO tblstudents(StudentName, RollId, StudentEmail, CURP, ClassId, Status) 
            VALUES(:studentname, :roolid, :studentemail, :curp, :classid, :status)";
    $query = $dbh->prepare($sql);
    $query->bindParam(':studentname', $studentname);
    $query->bindParam(':roolid', $roolid);
    $query->bindParam(':studentemail', $studentemail);
    $query->bindParam(':curp', $curp);
    $query->bindParam(':classid', $classid);
    $query->bindParam(':status', $status);
    $query->execute();

    // Verifica si se insertó correctamente
    $student_id = $dbh->lastInsertId();
    if ($student_id) {
        // ========================================
        // PROCESAR TUTOR
        // ========================================
        if ($tutor_option == 'create') {
            // CREAR NUEVO TUTOR
            $tutor_name = $_POST['tutor_name'] ?? '';
            $tutor_email = $_POST['tutor_email'] ?? strtolower(str_replace(' ', '.', $studentname)) . '.tutor@ipt.edu.mx';
            $relationship = $_POST['relationship_type'] ?? 'padre';
            
            $tutor_info = createTutor($dbh, $tutor_email, $tutor_name);
            if ($tutor_info) {
                $tutor_id = $tutor_info['id'];
                linkTutorToStudent($dbh, $student_id, $tutor_id, $relationship);
                
                $tutor_credentials = "
                <div class='alert alert-info'>
                    <strong>Cuenta de Tutor Creada:</strong><br>
                    <strong>Email:</strong> {$tutor_info['email']}<br>
                    <strong>Contraseña Temporal:</strong> {$tutor_info['password']}<br>
                    <strong>⚠️ Importante:</strong> Entrega estas credenciales al padre/tutor de familia.
                </div>";
                
                $msg = "Estudiante agregado correctamente. Tutor vinculado exitosamente.";
            } else {
                $error = "Error al crear la cuenta del tutor.";
            }
        } elseif ($tutor_option == 'existing') {
            // ASIGNAR TUTOR EXISTENTE
            $tutor_id = $_POST['existing_tutor_id'] ?? null;
            $relationship = $_POST['relationship_type'] ?? 'padre';
            
            if ($tutor_id) {
                if (linkTutorToStudent($dbh, $student_id, $tutor_id, $relationship)) {
                    $msg = "Estudiante agregado correctamente. Tutor asignado exitosamente.";
                } else {
                    $error = "Error al asignar el tutor.";
                }
            } else {
                $error = "Por favor selecciona un tutor existente.";
            }
        }
        
        if (!$tutor_credentials && !$error) {
            $msg = "Información del estudiante agregada correctamente";
        }
    } else {
        $error = "Algo salió mal. Inténtalo de nuevo";
    }
}

// ------------------------
// Importar múltiples estudiantes desde archivo Excel (.xlsx)
// ------------------------
if (isset($_POST['import_excel'])) {
    if (isset($_FILES['excel_file']['tmp_name'])) {
        $file = $_FILES['excel_file']['tmp_name'];

        // Carga el archivo Excel
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(); // Convierte en arreglo

        $inserted = 0; // Contador de registros

        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Salta la cabecera

            // Recoge los datos por columna
            $studentname = trim($row[0]);
            $roolid = trim($row[1]);
            $studentemail = trim($row[2]);
            $curp = trim($row[3]);
            $classid = trim($row[4]);
            $status = 1;

            // Si todos los campos están llenos
            if ($studentname && $roolid && $studentemail && $curp && $classid) {
                $sql = "INSERT INTO tblstudents(StudentName, RollId, StudentEmail, CURP, ClassId, Status)
                        VALUES(:studentname, :roolid, :studentemail, :curp, :classid, :status)";
                $query = $dbh->prepare($sql);
                $query->bindParam(':studentname', $studentname);
                $query->bindParam(':roolid', $roolid);
                $query->bindParam(':studentemail', $studentemail);
                $query->bindParam(':curp', $curp);
                $query->bindParam(':classid', $classid);
                $query->bindParam(':status', $status);
                $query->execute();
                $inserted++;
            }
        }

        // Resultado final
        if ($inserted > 0) {
            $msg = "$inserted estudiantes importados correctamente.";
        } else {
            $error = "No se importó ningún estudiante. Verifica el archivo.";
        }
    } else {
        $error = "Archivo no recibido.";
    }
}
?>

<!-- INTERFAZ HTML A PARTIR DE AQUÍ -->

<?php include('includes/topbar.php'); ?>
<div class="content-wrapper">
    <div class="content-container">
        <?php include('includes/leftbar.php'); ?>

        <div class="main-page">
            <div class="container-fluid">
                <!-- Título de la Página -->
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Agregar Estudiante</h2>
                    </div>
                </div>

                <!-- Breadcrumb -->
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li class="active">Agregar Estudiante</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Contenido Principal -->
            <section class="section">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Completa la información del estudiante</h5>
                                    </div>
                                </div>

                                <div class="panel-body">

                                    <!-- Mostrar mensajes de éxito o error -->
                                    <?php if ($msg) { ?>
                                        <div class="alert alert-success" role="alert">
                                            <strong>Bien hecho! </strong><?php echo htmlentities($msg); ?>
                                        </div>
                                    <?php } elseif ($error) { ?>
                                        <div class="alert alert-danger" role="alert">
                                            <strong>Error! </strong><?php echo htmlentities($error); ?>
                                        </div>
                                    <?php } ?>
                                    
                                    <!-- Mostrar credenciales del tutor si se creó uno nuevo -->
                                    <?php if ($tutor_credentials) { echo $tutor_credentials; } ?>

                                    <!-- Formulario para agregar estudiante manualmente -->
                                    <form class="row" method="post">
                                        <div class="form-group col-md-6">
                                            <label>Nombre Completo</label>
                                            <input type="text" name="fullanme" class="form-control" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>ID Rol</label>
                                            <input type="text" name="rollid" class="form-control" maxlength="5" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Correo</label>
                                            <input type="email" name="emailid" class="form-control" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>CURP</label>
                                            <input type="text" name="curp" class="form-control" maxlength="18" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Año</label>
                                            <select name="class" class="form-control" required>
                                                <option value="">Seleccionar Año</option>
                                                <?php
                                                // Llena las opciones de clase desde la BD
                                                $sql = "SELECT * FROM tblclasses";
                                                $query = $dbh->prepare($sql);
                                                $query->execute();
                                                $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                foreach ($results as $result) {
                                                    echo '<option value="' . $result->id . '">' .
                                                        htmlentities($result->ClassName) . ' - Sección ' . htmlentities($result->Section) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        
                                        <!-- SECCIÓN DE TUTOR (Padres de Familia) -->
                                        <div class="form-group col-md-12">
                                            <hr>
                                            <h5 style="margin-top: 10px;">Información del Tutor (Padre/Madre de Familia)</h5>
                                        </div>
                                        
                                        <div class="form-group col-md-12">
                                            <div class="form-check">
                                                <label class="form-check-label" for="tutor_create">
                                                    Crear nuevo tutor (cuenta de acceso)
                                                </label>
                                                <input class="form-check-input" type="radio" name="tutor_option" id="tutor_create" value="create" checked onchange="toggleTutorFields()">
                                            </div>
                                            <div class="form-check">
                                                <label class="form-check-label" for="tutor_existing">
                                                    Asignar tutor existente
                                                </label>
                                                <input class="form-check-input" type="radio" name="tutor_option" id="tutor_existing" value="existing" onchange="toggleTutorFields()">
                                            </div>
                                        </div>
                                        
                                        <!-- Campos para CREAR nuevo tutor -->
                                        <div id="create_tutor_fields" style="display: block;">
                                            <div class="form-group col-md-6">
                                                <label>Nombre del Tutor</label>
                                                <input type="text" name="tutor_name" class="form-control" placeholder="Ej: María García López">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Email del Tutor</label>
                                                <input type="email" name="tutor_email" class="form-control" placeholder="Se genera automáticamente si no se ingresa">
                                            </div>
                                        </div>
                                        
                                        <!-- Campos para SELECCIONAR tutor existente -->
                                        <div id="existing_tutor_fields" style="display: none;">
                                            <div class="form-group col-md-6">
                                                <label>Seleccionar Tutor Existente</label>
                                                <select name="existing_tutor_id" class="form-control">
                                                    <option value="">-- Seleccionar Tutor --</option>
                                                    <?php
                                                    // Obtener lista de tutores existentes
                                                    $sql = "SELECT id, UserName FROM admin WHERE role = 'tutor' ORDER BY UserName ASC";
                                                    $query = $dbh->prepare($sql);
                                                    $query->execute();
                                                    $tutors = $query->fetchAll(PDO::FETCH_OBJ);
                                                    foreach ($tutors as $tutor) {
                                                        echo '<option value="' . $tutor->id . '">' . htmlentities($tutor->UserName) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <!-- Tipo de relación (común a ambos) -->
                                        <div class="form-group col-md-6">
                                            <label>Relación con el Estudiante</label>
                                            <select name="relationship_type" class="form-control">
                                                <option value="padre">Padre</option>
                                                <option value="madre">Madre</option>
                                                <option value="abuelo">Abuelo/a</option>
                                                <option value="tutor_legal">Tutor Legal</option>
                                                <option value="otro">Otro</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group col-md-12">
                                            <button type="submit" name="submit" class="btn btn-success">Agregar</button>
                                        </div>
                                    </form>
                                    
                                    <!-- Script para mostrar/ocultar campos del tutor -->
                                    <script>
                                        function toggleTutorFields() {
                                            const createFields = document.getElementById('create_tutor_fields');
                                            const existingFields = document.getElementById('existing_tutor_fields');
                                            const option = document.querySelector('input[name="tutor_option"]:checked').value;
                                            
                                            if (option === 'create') {
                                                createFields.style.display = 'block';
                                                existingFields.style.display = 'none';
                                            } else {
                                                createFields.style.display = 'none';
                                                existingFields.style.display = 'block';
                                            }
                                        }
                                    </script>

                                    <hr>

                                    <!-- Formulario para importar estudiantes desde Excel -->
                                    <form method="post" enctype="multipart/form-data">
                                        <label><strong>Subir archivo Excel (.xlsx) para importar múltiples estudiantes:</strong></label>
                                        <input type="file" name="excel_file" accept=".xlsx" required>
                                        <button type="submit" name="import_excel" class="btn btn-primary">Importar Estudiantes</button>
                                    </form>

                                    <p class="text-muted">Formato esperado: Nombre Completo | ID Rol | Email | CURP | ID Clase</p>

                                </div> <!-- /.panel-body -->
                            </div> <!-- /.panel -->
                        </div> <!-- /.col -->
                    </div> <!-- /.row -->
                </div> <!-- /.container-fluid -->
            </section> <!-- /.section -->

        </div> <!-- /.main-page -->
    </div> <!-- /.content-container -->
</div> <!-- /.content-wrapper -->

<?php include('includes/footer.php'); ?>

