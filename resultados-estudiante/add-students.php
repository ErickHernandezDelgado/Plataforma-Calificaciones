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
include('includes/config.php');

// Verifica que el usuario esté autenticado
if (strlen($_SESSION['alogin']) == 0) {
    header("Location: index.php");
    exit;
}

// Variables de estado para mensajes
$msg = "";
$error = "";

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

    // Inserta en la base de datos
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
    if ($dbh->lastInsertId()) {
        $msg = "Información del estudiante agregada correctamente";
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
                                        <div class="form-group col-md-12">
                                            <button type="submit" name="submit" class="btn btn-success">Agregar</button>
                                        </div>
                                    </form>

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
