<?php
session_start(); // Inicia la sesión
error_reporting(0); // Suprime errores en producción
include('includes/config.php'); // Incluye archivo de configuración (conexión a la BD, etc.)

// Si no hay sesión activa, redirige al login
if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php");
} else {
?>

<!-- Hoja de estilos de DataTables para tablas interactivas -->
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
                        <h2 class="title">Gestión de Maestros</h2>
                    </div>
                </div>

                <!-- Migas de pan (breadcrumb) -->
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li>Maestros</li>
                            <li class="active">Gestión de Maestros</li>
                        </ul>
                    </div>
                </div>
            </div> <!-- /.container-fluid -->

            <!-- Sección de tabla de maestros -->
            <section class="section">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">

                            <!-- Panel de tabla -->
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Ver Información de Maestro</h5>
                                    </div>
                                </div>

                                <!-- Mensajes de éxito o error -->
                                <?php if ($msg) { ?>
                                    <div class="alert alert-success left-icon-alert" role="alert">
                                        <strong>Proceso Correcto! </strong><?php echo htmlentities($msg); ?>
                                    </div>
                                <?php } else if ($error) { ?>
                                    <div class="alert alert-danger left-icon-alert" role="alert">
                                        <strong>Algo salió mal! </strong> <?php echo htmlentities($error); ?>
                                    </div>
                                <?php } ?>

                                <!-- Tabla de maestros -->
                                <div class="panel-body p-20">
                                    <table id="example" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Nombre del Maestro</th>
                                                <th>Correo</th>
                                                <th>Fecha de Ingreso</th>
                                                <th>Estado</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Consulta para obtener los datos de los maestros
                                            $sql = "SELECT Id, TeacherName, TeacherEmail, JoiningDate, Status FROM tblteachers";
                                            $query = $dbh->prepare($sql);
                                            $query->execute();
                                            $results = $query->fetchAll(PDO::FETCH_OBJ);
                                            $cnt = 1;

                                            // Recorremos y mostramos los resultados
                                            if ($query->rowCount() > 0) {
                                                foreach ($results as $result) { ?>
                                                    <tr>
                                                        <td><?php echo htmlentities($cnt); ?></td>
                                                        <td><?php echo htmlentities($result->TeacherName); ?></td>
                                                        <td><?php echo htmlentities($result->TeacherEmail); ?></td>
                                                        <td><?php echo htmlentities($result->JoiningDate); ?></td>
                                                        <td><?php echo $result->Status == 1 ? 'Activo' : 'Bloqueado'; ?></td>
                                                        <td>
                                                            <!-- Botón para editar -->
                                                            <a href="edit-teacher.php?tid=<?php echo htmlentities($result->Id); ?>" class="btn btn-info">
                                                                <i class="fa fa-edit" title="Editar Registro"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                            <?php
                                                    $cnt++;
                                                }
                                            } ?>
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

<?php } // Fin del else ?>
