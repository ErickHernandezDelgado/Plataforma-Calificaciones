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
// ===== FILTRO POR AÑO ACADÉMICO =====
$selectedYear = isset($_GET['academic_year']) ? intval($_GET['academic_year']) : date('Y');

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
                                        <h5>Ver Información de Estudiante y Tutor</h5>
                                    </div>
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

                                <!-- Filtro por Año Académico -->
                                <div class="panel-body p-20" style="border-bottom: 1px solid #ddd; padding-bottom: 15px;">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label><strong>Filtrar por Año Escolar:</strong></label>
                                            <form method="GET" style="display: flex; gap: 10px; align-items: flex-end;">
                                                <select name="academic_year" class="form-control" onchange="this.form.submit();">
                                                    <?php 
                                                    // Obtener años únicos disponibles
                                                    $sql_years = "SELECT DISTINCT AcademicYear FROM tblclasses ORDER BY AcademicYear DESC";
                                                    $query_years = $dbh->prepare($sql_years);
                                                    $query_years->execute();
                                                    $years = $query_years->fetchAll(PDO::FETCH_OBJ);
                                                    
                                                    foreach ($years as $year) {
                                                        $selected = ($year->AcademicYear == $selectedYear) ? 'selected' : '';
                                                        echo "<option value='" . htmlentities($year->AcademicYear) . "' $selected>" . htmlentities($year->AcademicYear) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cuerpo del panel con la tabla -->
                                <div class="panel-body p-20">
                                    <table id="example" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Alumno</th>
                                                <th>Email Alumno</th>
                                                <th>Grado</th>
                                                <th>Tutor (Padre/Madre)</th>
                                                <th>Email Tutor</th>
                                                <th>Contraseña Tutor</th>
                                                <th>Estado</th>
                                                <th>Editar</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            // Consulta mejorada con información de tutor
                                            $sql = "SELECT 
                                                        s.StudentId,
                                                        s.StudentName,
                                                        s.StudentEmail,
                                                        s.RollId,
                                                        s.Status,
                                                        c.ClassName,
                                                        c.Section,
                                                        c.AcademicYear,
                                                        a.UserName as TutorEmail,
                                                        a.Password as TutorPassword,
                                                        st.RelationshipType
                                                    FROM tblstudents s
                                                    JOIN tblclasses c ON c.id = s.ClassId
                                                    LEFT JOIN student_tutor st ON s.StudentId = st.StudentId AND st.PrimaryContact = 1
                                                    LEFT JOIN admin a ON st.TutorId = a.id
                                                    WHERE c.AcademicYear = :year
                                                    ORDER BY c.ClassName ASC, c.Section ASC, s.StudentName ASC";
                                            
                                            $query = $dbh->prepare($sql);
                                            $query->bindParam(':year', $selectedYear, PDO::PARAM_INT);
                                            $query->execute();
                                            $results = $query->fetchAll(PDO::FETCH_OBJ);

                                            $cnt = 1;
                                            if ($query->rowCount() > 0) {
                                                foreach ($results as $result) {
                                            ?>
                                                    <tr>
                                                        <td><?php echo htmlentities($cnt); ?></td>
                                                        <td><strong><?php echo htmlentities($result->StudentName); ?></strong></td>
                                                        <td><code><?php echo htmlentities($result->StudentEmail); ?></code></td>
                                                        <td><?php echo htmlentities($result->ClassName); ?> (<?php echo htmlentities($result->Section); ?>)</td>
                                                        <td><?php echo htmlentities($result->TutorEmail ?: 'Sin asignar'); ?></td>
                                                        <td><code><?php echo htmlentities($result->TutorEmail ?: '-'); ?></code></td>
                                                        <td>
                                                            <?php 
                                                            if ($result->TutorPassword) {
                                                                echo '<span style="background-color: #e8f4f8; padding: 3px 8px; border-radius: 3px;">';
                                                                echo htmlentities($result->TutorPassword);
                                                                echo '</span>';
                                                            } else {
                                                                echo '<span style="color: #999;">-</span>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            if ($result->Status == 1) {
                                                                echo '<span class="label label-success">Activo</span>';
                                                            } else {
                                                                echo '<span class="label label-danger">Inactivo</span>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <!-- Botón para editar información del estudiante y tutor -->
                                                            <a href="edit-student.php?stid=<?php echo htmlentities($result->StudentId); ?>" class="btn btn-info btn-sm">
                                                                <i class="fa fa-edit"></i> Editar
                                                            </a>
                                                        </td>
                                                    </tr>
                                            <?php 
                                                    $cnt++;
                                                }
                                            } else {
                                            ?>
                                                <tr>
                                                    <td colspan="9" style="text-align: center; color: #999;">
                                                        <em>No hay estudiantes registrados para el año <?php echo htmlentities($selectedYear); ?></em>
                                                    </td>
                                                </tr>
                                            <?php
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTables
    $('#example').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.15/i18n/Spanish.json"
        },
        "order": [[ 3, "asc" ]], // Ordenar por grado por defecto
        "pageLength": 25
    });
});
</script>

<?php } ?>

