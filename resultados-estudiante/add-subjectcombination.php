<?php
// Inicia sesión del usuario
session_start();

// Desactiva reportes de errores (puedes activar esto en desarrollo con error_reporting(E_ALL))
error_reporting(0);

// Conexión a la base de datos
include('includes/config.php');

// Verifica si el usuario está logueado, si no, redirige a login
if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php");
} else {
    // Si se envió el formulario
    if (isset($_POST['submit'])) {
        $class = $_POST['class'];      // ID del año/clase
        $subject = $_POST['subject'];  // ID de la materia
        $status = 1;                   // Activo por defecto

        // Prepara la consulta SQL para insertar la combinación clase-materia
        $sql = "INSERT INTO tblsubjectcombination(ClassId, SubjectId, status) 
                VALUES(:class, :subject, :status)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':class', $class, PDO::PARAM_STR);
        $query->bindParam(':subject', $subject, PDO::PARAM_STR);
        $query->bindParam(':status', $status, PDO::PARAM_STR);
        $query->execute();

        // Verifica si se insertó correctamente
        $lastInsertId = $dbh->lastInsertId();
        if ($lastInsertId) {
            $msg = "Combinación agregada correctamente.";
        } else {
            $error = "Algo salió mal. Intenta de nuevo.";
        }
    }
?>

<!-- Incluye la barra superior -->
<?php include('includes/topbar.php'); ?>

<!-- Contenedor principal -->
<div class="content-wrapper">
    <div class="content-container">

        <!-- Menú lateral -->
        <?php include('includes/leftbar.php'); ?>

        <!-- Página principal -->
        <div class="main-page">

            <!-- Encabezado -->
            <div class="container-fluid">
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Agregar Combinación de Materia</h2>
                    </div>
                </div>

                <!-- Breadcrumb -->
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li>Materias</li>
                            <li class="active">Agregar Combinación de Materia</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sección del formulario -->
            <section class="section">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Agregar Combinación de Materia</h5>
                                    </div>
                                </div>

                                <div class="panel-body">
                                    <!-- Mensajes de éxito o error -->
                                    <?php if ($msg) { ?>
                                        <div class="alert alert-success left-icon-alert" role="alert">
                                            <strong>¡Bien hecho!</strong> <?php echo htmlentities($msg); ?>
                                        </div>
                                    <?php } else if ($error) { ?>
                                        <div class="alert alert-danger left-icon-alert" role="alert">
                                            <strong>¡Error!</strong> <?php echo htmlentities($error); ?>
                                        </div>
                                    <?php } ?>

                                    <!-- Formulario para agregar combinación -->
                                    <form method="post">
                                        <!-- Selección de clase -->
                                        <div class="form-group">
                                            <label for="default" class="control-label">Año</label>
                                            <select name="class" class="form-control" required>
                                                <option value="">Selecciona año</option>
                                                <?php
                                                // Consulta todas las clases disponibles
                                                $sql = "SELECT * FROM tblclasses";
                                                $query = $dbh->prepare($sql);
                                                $query->execute();
                                                $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                if ($query->rowCount() > 0) {
                                                    foreach ($results as $result) {
                                                        echo '<option value="' . htmlentities($result->id) . '">' .
                                                             htmlentities($result->ClassName) . ' Section-' . htmlentities($result->Section) .
                                                             '</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <!-- Selección de materia -->
                                        <div class="form-group">
                                            <label for="default" class="control-label">Materia</label>
                                            <select name="subject" class="form-control" required>
                                                <option value="">Selecciona Materia</option>
                                                <?php
                                                // Consulta todas las materias disponibles
                                                $sql = "SELECT * FROM tblsubjects";
                                                $query = $dbh->prepare($sql);
                                                $query->execute();
                                                $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                if ($query->rowCount() > 0) {
                                                    foreach ($results as $result) {
                                                        echo '<option value="' . htmlentities($result->id) . '">' .
                                                             htmlentities($result->SubjectName) .
                                                             '</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <!-- Botón para enviar -->
                                        <div class="form-group">
                                            <button type="submit" name="submit" class="btn btn-success">Agregar</button>
                                        </div>
                                    </form>

                                </div> <!-- /.panel-body -->
                            </div> <!-- /.panel -->
                        </div> <!-- /.col -->
                    </div> <!-- /.row -->
                </div> <!-- /.container-fluid -->
            </section> <!-- /.section -->
        </div> <!-- /.main-page -->
    </div> <!-- /.content-container -->
</div> <!-- /.content-wrapper -->

<!-- Pie de página -->
<?php include('includes/footer.php'); ?>

<?php } // Fin del else ?>
