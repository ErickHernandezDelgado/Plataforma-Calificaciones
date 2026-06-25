<?php
// Inicia la sesión y desactiva los errores visibles
session_start();
error_reporting(0);
include(__DIR__ . '/includes/config.php');

// Verifica que el usuario haya iniciado sesión y que su rol sea 'admin'
if (!isset($_SESSION['alogin']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
} else {
    // Inicializa los mensajes para evitar variables indefinidas en la vista
    $msg = '';
    $error = '';

    // Niveles educativos válidos (deben coincidir con el ENUM de tblclasses.educationLevel)
    $nivelesPermitidos = ['maternal', 'preprimaria', 'kinder', 'primaria', 'secundaria'];

    // Si el formulario fue enviado
    if (isset($_POST['submit'])) {
        // Saneo básico de los datos recibidos
        $classname = trim($_POST['classname']);
        $classnamenumeric = trim($_POST['classnamenumeric']);
        $section = trim($_POST['section']);
        $educationLevel = $_POST['educationLevel'];

        // Validación del lado servidor
        if ($classname === '' || $section === '') {
            $error = "El nombre del año y la sección son obligatorios.";
        } elseif (!ctype_digit($classnamenumeric)) {
            $error = "El año en número debe ser un valor numérico válido.";
        } elseif (!in_array($educationLevel, $nivelesPermitidos, true)) {
            $error = "Selecciona un nivel educativo válido.";
        } else {
            try {
                $sql = "INSERT INTO tblclasses(ClassName, ClassNameNumeric, Section, educationLevel) VALUES(:classname, :classnamenumeric, :section, :educationLevel)";
                $query = $dbh->prepare($sql);
                $query->bindParam(':classname', $classname, PDO::PARAM_STR);
                $query->bindParam(':classnamenumeric', $classnamenumeric, PDO::PARAM_INT);
                $query->bindParam(':section', $section, PDO::PARAM_STR);
                $query->bindParam(':educationLevel', $educationLevel, PDO::PARAM_STR);
                $query->execute();

                // Verifica si se insertó correctamente
                $lastInsertId = $dbh->lastInsertId();
                if ($lastInsertId) {
                    $msg = "Año/grupo creado correctamente.";
                } else {
                    $error = "Algo salió mal. Por favor, inténtalo de nuevo.";
                }
            } catch (PDOException $e) {
                // Código 23000 = violación de restricción de integridad (clave única duplicada)
                if ($e->getCode() == 23000) {
                    $error = "Ya existe un grupo con ese nombre y sección.";
                } else {
                    $error = "Algo salió mal. Por favor, inténtalo de nuevo.";
                }
            }
        }
    }
?>

<!-- Barra superior -->
<?php include('includes/topbar.php'); ?>

<div class="content-wrapper">
    <div class="content-container">

        <!-- Barra lateral -->
        <?php include('includes/leftbar.php'); ?>

        <div class="main-page">
            <div class="container-fluid">
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Crear Año</h2>
                    </div>
                </div>

                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li><a href="#">Año</a></li>
                            <li class="active">Crear Año</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sección de formulario para crear clase/año -->
            <section class="section">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Crear Año</h5>
                                    </div>
                                </div>

                                <!-- Mensaje de éxito o error -->
                                <?php if ($msg) { ?>
                                    <div class="alert alert-success left-icon-alert" role="alert">
                                        <strong>Bien Hecho</strong> <?php echo htmlentities($msg); ?>
                                    </div>
                                <?php } else if ($error) { ?>
                                    <div class="alert alert-danger left-icon-alert" role="alert">
                                        <strong>Hubo un problema</strong> <?php echo htmlentities($error); ?>
                                    </div>
                                <?php } ?>

                                <div class="panel-body">
                                    <form method="post">
                                        <!-- Campo: Nombre del año (texto) -->
                                        <div class="form-group has-success">
                                            <label class="control-label">Nombre Año</label>
                                            <input type="text" name="classname" class="form-control" required>
                                            <span class="help-block">Puedes poner Primero, Segundo o algo así</span>
                                        </div>

                                        <!-- Campo: Año en número (número) -->
                                        <div class="form-group has-success">
                                            <label class="control-label">Año en Número</label>
                                            <input type="number" name="classnamenumeric" class="form-control" required>
                                            <span class="help-block">Puedes poner 1, 2, 3...</span>
                                        </div>

                                        <!-- Campo: Sección (texto) -->
                                        <div class="form-group has-success">
                                            <label class="control-label">Sección</label>
                                            <input type="text" name="section" class="form-control" required>
                                            <span class="help-block">Puedes poner A, B, C...</span>
                                        </div>

                                        <!-- Campo: Nivel educativo -->
                                        <div class="form-group has-success">
                                            <label class="control-label">Nivel Educativo</label>
                                            <select name="educationLevel" class="form-control" required>
                                                <option value="">Seleccionar nivel...</option>
                                                <option value="maternal">Maternal</option>
                                                <option value="preprimaria">Pre-primaria</option>
                                                <option value="kinder">Kinder</option>
                                                <option value="primaria">Primaria</option>
                                                <option value="secundaria">Secundaria</option>
                                            </select>
                                            <span class="help-block">Determina si el grupo usa bimestres (Maternal–Primaria) o trimestres (Secundaria).</span>
                                        </div>

                                        <!-- Botón para enviar -->
                                        <div class="form-group has-success">
                                            <button type="submit" name="submit" class="btn btn-success">Crear Grupo</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div> <!-- /.col-md-8 -->
                    </div> <!-- /.row -->
                </div> <!-- /.container-fluid -->
            </section>
        </div> <!-- /.main-page -->
    </div> <!-- /.content-container -->
</div> <!-- /.content-wrapper -->

<!-- Pie de página -->
<?php include('includes/footer.php'); ?>
<?php } ?>

