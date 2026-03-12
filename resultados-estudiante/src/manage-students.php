<?php
// Inicia sesión
session_start();

// Desactiva la notificación de errores
error_reporting(0);

// Incluye archivo de configuración (conexión a base de datos)
include(__DIR__ . '/includes/config.php');

// Verifica si el administrador ha iniciado sesión
if (strlen($_SESSION['alogin']) == "") {
    // Si no está logueado, redirige al login
    header("Location: index.php");
} else {
    // Variables de estado
    $msg = '';
    $error = '';
    $selected_year = isset($_POST['academic_year']) ? intval($_POST['academic_year']) : date('Y');

    // Obtener años académicos disponibles
    $sql_years = "SELECT DISTINCT AcademicYear FROM tblclasses ORDER BY AcademicYear DESC";
    $query_years = $dbh->prepare($sql_years);
    $query_years->execute();
    $available_years = $query_years->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Incluye estilos de DataTables para la tabla -->
<link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css" />

<!-- Barra superior -->
<?php include('includes/topbar.php'); ?>

<!-- Contenedor principal -->
<div class="content-wrapper">
    <div class="content-container">

        <!-- Barra lateral -->
        <?php include('includes/leftbar.php'); ?>

        <!-- Contenido principal -->
        <div class="main-page">
            <div class="container-fluid">

                <!-- Encabezado de página -->
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Gestión de Estudiantes</h2>
                    </div>
                </div>

                <!-- Breadcrumb de navegación -->
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li>Estudiantes</li>
                            <li class="active">Gestión de Estudiantes</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sección principal -->
            <section class="section">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col-md-12">

                            <!-- Panel que contiene la tabla de estudiantes -->
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Ver Información de Estudiante</h5>
                                    </div>
                                </div>

                                <!-- Filtro por Año Académico -->
                                <div class="panel-body" style="border-bottom: 1px solid #ddd; padding: 10px;">
                                    <form method="POST" class="form-inline">
                                        <label for="academic_year" style="margin-right: 10px;">Filtrar por Año:</label>
                                        <select name="academic_year" id="academic_year" class="form-control" onchange="this.form.submit()" style="width: auto;">
                                            <option value="">-- Todos los Años --</option>
                                            <?php foreach ($available_years as $year): ?>
                                                <option value="<?php echo $year['AcademicYear']; ?>" <?php echo ($selected_year == $year['AcademicYear']) ? 'selected' : ''; ?>>
                                                    Año <?php echo $year['AcademicYear']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </div>

                                <!-- Mensajes de éxito o error -->
                                <?php if ($msg) { ?>
                                    <div class="alert alert-success left-icon-alert" role="alert">
                                        <strong>Proceso Correcto! </strong><?php echo htmlentities($msg); ?>
                                    </div>
                                <?php } else if ($error) { ?>
                                    <div class="alert alert-danger left-icon-alert" role="alert">
                                        <strong>Algo salió mal! </strong><?php echo htmlentities($error); ?>
                                    </div>
                                <?php } ?>

                                <!-- Cuerpo del panel con la tabla -->
                                <div class="panel-body p-20">
                                    <table id="example" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Nombre de Estudiante</th>
                                                <th>Email</th>
                                                <th>Grado</th>
                                                <th>Tutor</th>
                                                <th>Email Tutor</th>
                                                <th>Pass Tutor</th>
                                                <th>Estado</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            // Consulta SQL mejorada usando la vista vw_student_with_tutor_complete
                                            $sql = "SELECT * FROM vw_student_with_tutor_complete WHERE 1=1";
                                            
                                            if ($selected_year) {
                                                $sql .= " AND AcademicYear = :year";
                                            }
                                            
                                            $sql .= " ORDER BY ClassName ASC, Section ASC, StudentName ASC";
                                            
                                            $query = $dbh->prepare($sql);
                                            if ($selected_year) {
                                                $query->bindParam(':year', $selected_year, PDO::PARAM_INT);
                                            }
                                            $query->execute();
                                            $results = $query->fetchAll(PDO::FETCH_OBJ);

                                            $cnt = 1;
                                            if ($query->rowCount() > 0) {
                                                foreach ($results as $result) {
                                            ?>
                                                    <tr>
                                                        <td><?php echo htmlentities($cnt); ?></td>
                                                        <td><?php echo htmlentities($result->StudentName); ?></td>
                                                        <td>
                                                            <small style="color: #007bff;"><code><?php echo htmlentities($result->StudentEmail); ?></code></small>
                                                        </td>
                                                        <td><?php echo htmlentities($result->ClassName); ?> - Sección <?php echo htmlentities($result->Section); ?></td>
                                                        <td>
                                                            <?php 
                                                            if ($result->TutorEmail) {
                                                                echo htmlentities($result->TutorEmail);
                                                            } else {
                                                                echo '<span style="color: red;">Sin asignar</span>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            if ($result->TutorEmail) {
                                                                echo '<small><code>' . htmlentities($result->email_login) . '</code></small>';
                                                            } else {
                                                                echo '--';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            if ($result->TutorPassword) {
                                                                echo '<small style="background: #fff3cd; padding: 2px 5px;"><code>' . htmlentities($result->TutorPassword) . '</code></small>';
                                                            } else {
                                                                echo '--';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            echo $result->Status == 1 ? '<span style="color: green;">✓ Activo</span>' : '<span style="color: red;">✗ Bloqueado</span>';
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <!-- Botón para editar información del estudiante -->
                                                            <a href="edit-student.php?stid=<?php echo htmlentities($result->StudentId); ?>" class="btn btn-info btn-sm">
                                                                <i class="fa fa-edit" title="Editar Registro"></i> Editar
                                                            </a>
                                                        </td>
                                                    </tr>
                                            <?php 
                                                    $cnt++;
                                                }
                                            } else {
                                                echo '<tr><td colspan="9" style="text-align: center; padding: 20px; color: #999;">No hay estudiantes registrados para este año académico.</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div> <!-- /.panel-body -->
                            </div> <!-- /.panel -->
                        </div> <!-- /.col-md-12 -->
                    </div> <!-- /.row -->

                </div> <!-- /.container-fluid -->
            </section> <!-- /.section -->

        </div> <!-- /.main-page -->
    </div> <!-- /.content-container -->
</div> <!-- /.content-wrapper -->

<!-- Pie de página -->
<?php include('includes/footer.php'); ?>

<?php } // Cierre del else de session check ?>
