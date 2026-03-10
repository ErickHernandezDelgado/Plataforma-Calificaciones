<?php
session_start(); // Inicia la sesión
error_reporting(0); // Desactiva la visualización de errores en producción
include('includes/config.php'); // Incluye el archivo de configuración (BD, constantes, etc.)

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

                                <!-- Tabla de materias -->
                                <div class="panel-body p-20">
                                    <table id="example" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Nombre Materia</th>
                                                <th>Código Materia</th>
                                                <th>Fecha Creación</th>
                                                <th>Fecha Actualización</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            // Consulta todas las materias
                                            $sql = "SELECT * FROM tblsubjects";
                                            $query = $dbh->prepare($sql);
                                            $query->execute();
                                            $results = $query->fetchAll(PDO::FETCH_OBJ);
                                            $cnt = 1;

                                            // Si hay resultados, los mostramos
                                            if ($query->rowCount() > 0) {
                                                foreach ($results as $result) {
                                            ?>
                                                    <tr>
                                                        <td><?php echo htmlentities($cnt); ?></td>
                                                        <td><?php echo htmlentities($result->SubjectName); ?></td>
                                                        <td><?php echo htmlentities($result->SubjectCode); ?></td>
                                                        <td><?php echo htmlentities($result->Creationdate); ?></td>
                                                        <td><?php echo htmlentities($result->UpdationDate); ?></td>
                                                        <td>
                                                            <!-- Botón para editar -->
                                                            <a href="edit-subject.php?subjectid=<?php echo htmlentities($result->id); ?>" class="btn btn-info">
                                                                <i class="fa fa-edit" title="Editar Materia"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                            <?php 
                                                    $cnt++;
                                                }
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

<?php } ?>
