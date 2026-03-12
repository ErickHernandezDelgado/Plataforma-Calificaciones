<?php
session_start(); // Inicia la sesión
error_reporting(0); // Desactiva la visualización de errores en producción
include(__DIR__ . '/includes/config.php'); // Incluye el archivo de configuración (BD, constantes, etc.)

// Si no hay sesión iniciada como admin, redirige al login
if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php");
} else {
?>

<!-- Estilos de DataTables para la tabla -->
<link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css" />

<!-- Barra superior -->
<?php include('includes/topbar.php'); ?>

<!-- Contenedor principal -->
<div class="content-wrapper">
    <div class="content-container">

        <!-- Barra lateral -->
        <?php include('includes/leftbar.php'); ?>

        <!-- Página principal -->
        <div class="main-page">
            <div class="container-fluid">

                <!-- Título de la página -->
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Gestionar Materias</h2>
                    </div>
                </div>

                <!-- Migas de pan (navegación) -->
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li>Materias</li>
                            <li class="active">Gestionar Materias</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sección de tabla -->
            <section class="section">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col-md-12">

                            <!-- Panel contenedor -->
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Ver Información de Materia</h5>
                                    </div>
                                </div>

                                <!-- Mensajes de éxito o error -->
                                <?php if ($msg) { ?>
                                    <div class="alert alert-success left-icon-alert" role="alert">
                                        <strong>Bien hecho!</strong> <?php echo htmlentities($msg); ?>
                                    </div>
                                <?php } else if ($error) { ?>
                                    <div class="alert alert-danger left-icon-alert" role="alert">
                                        <strong>Hubo un inconveniente!</strong> <?php echo htmlentities($error); ?>
                                    </div>
                                <?php } ?>

                                <!-- Filtro por grupo académico -->
                                <div class="panel-body p-20" style="border-bottom: 1px solid #ddd;">
                                    <form method="POST" action="">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label><strong>Filtrar por Grupo:</strong></label>
                                                <select name="class_filter" class="form-control" onchange="this.form.submit();">
                                                    <option value="">-- Ver Todos --</option>
                                                    <?php
                                                    // Obtener todos los grupos disponibles
                                                    $sql_class = "SELECT DISTINCT c.id, c.ClassName, c.Section, c.AcademicYear, c.educationLevel
                                                                  FROM tblclasses c
                                                                  JOIN tblsubjectcombination sc ON sc.ClassId = c.id
                                                                  WHERE sc.status = 1
                                                                  ORDER BY c.AcademicYear DESC, 
                                                                           FIELD(c.educationLevel, 'infantil', 'primaria', 'secundaria'),
                                                                           c.ClassName ASC, c.Section ASC";
                                                    $query_class = $dbh->prepare($sql_class);
                                                    $query_class->execute();
                                                    $classes = $query_class->fetchAll(PDO::FETCH_OBJ);
                                                    
                                                    $selected_class = isset($_POST['class_filter']) ? $_POST['class_filter'] : '';
                                                    
                                                    foreach ($classes as $class) {
                                                        $selected = ($selected_class == $class->id) ? 'selected' : '';
                                                        echo "<option value='{$class->id}' {$selected}>{$class->ClassName} - {$class->Section} ({$class->AcademicYear})</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Tabla de materias por grupo (VISTA CONSOLIDADA) -->
                                <div class="panel-body p-20">
                                    <table id="example" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Materia</th>
                                                <th>Código</th>
                                                <th>Grupos Asignados</th>
                                                <th>Nivel Educativo</th>
                                                <th>Año Académico</th>
                                                <th>Estado</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            // Consultar vista consolidada de materias por grupo
                                            $sql = "SELECT DISTINCT 
                                                    s.id,
                                                    s.SubjectName,
                                                    s.SubjectCode,
                                                    s.Language,
                                                    GROUP_CONCAT(DISTINCT c.ClassName, ' (', c.Section, ')' ORDER BY c.ClassName SEPARATOR ', ') as grupos,
                                                    GROUP_CONCAT(DISTINCT c.educationLevel ORDER BY c.educationLevel SEPARATOR ', ') as niveles,
                                                    GROUP_CONCAT(DISTINCT c.AcademicYear ORDER BY c.AcademicYear SEPARATOR ', ') as anos,
                                                    COUNT(DISTINCT sc.id) as num_grupos
                                            FROM tblsubjects s
                                            LEFT JOIN tblsubjectcombination sc ON sc.SubjectId = s.id AND sc.status = 1
                                            LEFT JOIN tblclasses c ON sc.ClassId = c.id";
                                            
                                            // Agregar filtro si se seleccionó un grupo
                                            if (!empty($selected_class)) {
                                                $sql .= " WHERE sc.ClassId = :class_id";
                                            }
                                            
                                            $sql .= " GROUP BY s.id ORDER BY s.SubjectName ASC";
                                            
                                            $query = $dbh->prepare($sql);
                                            
                                            if (!empty($selected_class)) {
                                                $query->bindParam(':class_id', $selected_class, PDO::PARAM_INT);
                                            }
                                            
                                            $query->execute();
                                            $results = $query->fetchAll(PDO::FETCH_OBJ);
                                            $cnt = 1;

                                            // Si hay resultados, los mostramos
                                            if ($query->rowCount() > 0) {
                                                foreach ($results as $result) {
                                                    $status_badge = ($result->num_grupos > 0) ? '<span class="badge badge-success">Asignada</span>' : '<span class="badge badge-warning">No Asignada</span>';
                                            ?>
                                                    <tr>
                                                        <td><?php echo htmlentities($cnt); ?></td>
                                                        <td><?php echo htmlentities($result->SubjectName); ?></td>
                                                        <td><?php echo htmlentities($result->SubjectCode); ?></td>
                                                        <td>
                                                            <?php 
                                                            if (empty($result->grupos)) {
                                                                echo '<em class="text-danger">-- Sin asignar --</em>';
                                                            } else {
                                                                echo htmlentities($result->grupos);
                                                            }
                                                            ?>
                                                        </td>
                                                        <td><?php echo htmlentities($result->niveles); ?></td>
                                                        <td><?php echo htmlentities($result->anos); ?></td>
                                                        <td><?php echo $status_badge; ?></td>
                                                        <td>
                                                            <!-- Botón para editar materia -->
                                                            <a href="edit-subject.php?subjectid=<?php echo htmlentities($result->id); ?>" class="btn btn-info btn-sm" title="Editar datos">
                                                                <i class="fa fa-edit"></i> Editar
                                                            </a>
                                                            <!-- Botón para asignar a grupos -->
                                                            <a href="add-subjectcombination.php?subjectid=<?php echo htmlentities($result->id); ?>" class="btn btn-warning btn-sm" title="Asignar a grupos">
                                                                <i class="fa fa-plus"></i> Asignar
                                                            </a>
                                                        </td>
                                                    </tr>
                                            <?php 
                                                    $cnt++;
                                                }
                                            } else {
                                            ?>
                                                <tr>
                                                    <td colspan="8" class="text-center text-muted">No hay materias registradas</td>
                                                </tr>
                                            <?php } ?>
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

<?php } ?>

