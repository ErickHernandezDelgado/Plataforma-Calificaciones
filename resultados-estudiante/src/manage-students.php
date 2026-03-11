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
                                                <th>ID Roll</th>
                                                <th>Año</th>
                                                <th>Fecha de Registro</th>
                                                <th>Estado</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            // Consulta SQL para obtener estudiantes junto con el nombre de su clase y sección
                                            $sql = "SELECT tblstudents.StudentName, tblstudents.RollId, tblstudents.RegDate, 
                                                           tblstudents.StudentId, tblstudents.Status, 
                                                           tblclasses.ClassName, tblclasses.Section 
                                                    FROM tblstudents 
                                                    JOIN tblclasses ON tblclasses.id = tblstudents.ClassId";
                                            
                                            $query = $dbh->prepare($sql);
                                            $query->execute();
                                            $results = $query->fetchAll(PDO::FETCH_OBJ);

                                            $cnt = 1;
                                            if ($query->rowCount() > 0) {
                                                foreach ($results as $result) {
                                            ?>
                                                    <tr>
                                                        <td><?php echo htmlentities($cnt); ?></td>
                                                        <td><?php echo htmlentities($result->StudentName); ?></td>
                                                        <td><?php echo htmlentities($result->RollId); ?></td>
                                                        <td><?php echo htmlentities($result->ClassName); ?> (<?php echo htmlentities($result->Section); ?>)</td>
                                                        <td><?php echo htmlentities($result->RegDate); ?></td>
                                                        <td>
                                                            <?php 
                                                            echo $result->Status == 1 ? 'Activo' : 'Bloqueado';
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <!-- Botón para editar información del estudiante -->
                                                            <a href="edit-student.php?stid=<?php echo htmlentities($result->StudentId); ?>" class="btn btn-info">
                                                                <i class="fa fa-edit" title="Editar Registro"></i> 
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

