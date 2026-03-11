<?php
session_start(); // Inicia la sesión
error_reporting(0); // Desactiva los mensajes de error
include(__DIR__ . '/includes/config.php'); // Incluye el archivo de configuración (conexión DB)

// Verifica si el administrador ha iniciado sesión
if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php"); // Redirige al login si no hay sesión
} else {

    // Activar materia
    if (isset($_GET['acid'])) {
        $acid = intval($_GET['acid']); // ID de la combinación de materia a activar
        $status = 1;
        $sql = "update tblsubjectcombination set status=:status where id=:acid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':acid', $acid, PDO::PARAM_STR);
        $query->bindParam(':status', $status, PDO::PARAM_STR);
        $query->execute();
        $msg = "Materia activada correctamente";
    }

    // Desactivar materia
    if (isset($_GET['did'])) {
        $did = intval($_GET['did']); // ID de la combinación de materia a desactivar
        $status = 0;
        $sql = "update tblsubjectcombination set status=:status where id=:did";
        $query = $dbh->prepare($sql);
        $query->bindParam(':did', $did, PDO::PARAM_STR);
        $query->bindParam(':status', $status, PDO::PARAM_STR);
        $query->execute();
        $msg = "Materia desactivada correctamente";
    }
?>

<!-- Estilo de DataTables para la tabla -->
<link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css" />

<!-- Barra superior -->
<?php include('includes/topbar.php'); ?>

<!-- Contenedor general -->
<div class="content-wrapper">
    <div class="content-container">

        <!-- Barra lateral -->
        <?php include('includes/leftbar.php'); ?>

        <!-- Página principal -->
        <div class="main-page">
            <div class="container-fluid">

                <!-- Encabezado de la página -->
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Gestionar la Relación de Materia</h2>
                    </div>
                </div>

                <!-- Ruta de navegación -->
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li>Materia</li>
                            <li class="active">Gestionar Relación de Materia</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sección principal -->
            <section class="section">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">

                            <!-- Panel de visualización de datos -->
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Ver Información de Relación de Materia</h5>
                                    </div>
                                </div>

                                <!-- Mensajes de éxito o error -->
                                <?php if ($msg) { ?>
                                    <div class="alert alert-success left-icon-alert" role="alert">
                                        <strong>Bien hecho!</strong> <?php echo htmlentities($msg); ?>
                                    </div>
                                <?php } else if ($error) { ?>
                                    <div class="alert alert-danger left-icon-alert" role="alert">
                                        <strong>Hubo inconvenientes!</strong> <?php echo htmlentities($error); ?>
                                    </div>
                                <?php } ?>

                                <!-- Tabla de relaciones materia-clase -->
                                <div class="panel-body p-20">
                                    <table id="example" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Materia y Sección</th>
                                                <th>Materia</th>
                                                <th>Estado</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            // Consulta para obtener la relación entre materias y clases
                                            $sql = "SELECT 
                                                        tblclasses.ClassName, 
                                                        tblclasses.Section, 
                                                        tblsubjects.SubjectName, 
                                                        tblsubjectcombination.id as scid, 
                                                        tblsubjectcombination.status 
                                                    FROM tblsubjectcombination 
                                                    JOIN tblclasses ON tblclasses.id = tblsubjectcombination.ClassId  
                                                    JOIN tblsubjects ON tblsubjects.id = tblsubjectcombination.SubjectId";
                                            
                                            $query = $dbh->prepare($sql);
                                            $query->execute();
                                            $results = $query->fetchAll(PDO::FETCH_OBJ);
                                            $cnt = 1;

                                            if ($query->rowCount() > 0) {
                                                foreach ($results as $result) {
                                            ?>
                                                    <tr>
                                                        <td><?php echo htmlentities($cnt); ?></td>
                                                        <td><?php echo htmlentities($result->ClassName); ?> - Sección <?php echo htmlentities($result->Section); ?></td>
                                                        <td><?php echo htmlentities($result->SubjectName); ?></td>
                                                        <td>
                                                            <?php 
                                                            $stts = $result->status;
                                                            echo ($stts == 0) ? 'Inactiva' : 'Activa';
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($stts == 0) { ?>
                                                                <!-- Activar materia -->
                                                                <a href="manage-subjectcombination.php?acid=<?php echo htmlentities($result->scid); ?>" onclick="return confirm('¿Deseas activar esta materia?');" class="btn btn-success">
                                                                    <i class="fa fa-check" title="Activar"></i>
                                                                </a>
                                                            <?php } else { ?>
                                                                <!-- Desactivar materia -->
                                                                <a href="manage-subjectcombination.php?did=<?php echo htmlentities($result->scid); ?>" onclick="return confirm('¿Deseas desactivar esta materia?');" class="btn btn-danger">
                                                                    <i class="fa fa-times" title="Desactivar"></i>
                                                                </a>
                                                            <?php } ?>
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

