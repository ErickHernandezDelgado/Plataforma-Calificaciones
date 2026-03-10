<?php
// Inicia la sesión
session_start();

// Desactiva la visualización de errores
error_reporting(0);

// Incluye archivo de configuración (conexión a base de datos, etc.)
include('includes/config.php');

// Verifica si el administrador ha iniciado sesión
if (strlen($_SESSION['alogin']) == "") {
    // Si no ha iniciado sesión, redirige a la página de login
    header("Location: index.php");
} else {
?>

    <!-- Incluye estilos para DataTables -->
    <link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css" />

    <!-- Incluye la barra superior de navegación -->
    <?php include('includes/topbar.php'); ?>

    <!-- Contenedor para barras laterales y contenido principal -->
    <div class="content-wrapper">
        <div class="content-container">

            <!-- Incluye barra lateral izquierda -->
            <?php include('includes/leftbar.php'); ?>

            <div class="main-page">
                <div class="container-fluid">

                    <!-- Título de la página -->
                    <div class="row page-title-div">
                        <div class="col-md-6">
                            <h2 class="title">Gestionar Años</h2>
                        </div>
                    </div>

                    <!-- Breadcrumb de navegación -->
                    <div class="row breadcrumb-div">
                        <div class="col-md-6">
                            <ul class="breadcrumb">
                                <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                                <li> Años</li>
                                <li class="active">Gestionar Años</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Sección principal -->
                <section class="section">
                    <div class="container-fluid">

                        <div class="row">
                            <div class="col-md-12">

                                <div class="panel">

                                    <!-- Encabezado del panel -->
                                    <div class="panel-heading">
                                        <div class="panel-title">
                                            <h5>Ver información de Año</h5>
                                        </div>
                                    </div>

                                    <!-- Muestra mensaje de éxito si $msg está definido -->
                                    <?php if ($msg) { ?>
                                        <div class="alert alert-success left-icon-alert" role="alert">
                                            <strong>Bien hecho</strong><?php echo htmlentities($msg); ?>
                                        </div>

                                    <!-- Muestra mensaje de error si $error está definido -->
                                    <?php } else if ($error) { ?>
                                        <div class="alert alert-danger left-icon-alert" role="alert">
                                            <strong>Inconvenientes</strong> <?php echo htmlentities($error); ?>
                                        </div>
                                    <?php } ?>

                                    <!-- Cuerpo del panel -->
                                    <div class="panel-body p-20">

                                        <!-- Tabla para mostrar años -->
                                        <table id="example" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Nombre de Año</th>
                                                    <th>Año en número</th>
                                                    <th>Sección</th>
                                                    <th>Fecha de Creación</th>
                                                    <th>Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Consulta para obtener todos los registros de la tabla tblclasses
                                                $sql = "SELECT * from tblclasses";
                                                $query = $dbh->prepare($sql);
                                                $query->execute();

                                                // Obtiene todos los resultados como objetos
                                                $results = $query->fetchAll(PDO::FETCH_OBJ);

                                                // Contador para numerar filas
                                                $cnt = 1;

                                                // Si hay resultados, recorre cada uno para mostrarlos en la tabla
                                                if ($query->rowCount() > 0) {
                                                    foreach ($results as $result) { ?>
                                                        <tr>
                                                            <!-- Número consecutivo -->
                                                            <td><?php echo htmlentities($cnt); ?></td>

                                                            <!-- Nombre del año -->
                                                            <td><?php echo htmlentities($result->ClassName); ?></td>

                                                            <!-- Año en número -->
                                                            <td><?php echo htmlentities($result->ClassNameNumeric); ?></td>

                                                            <!-- Sección -->
                                                            <td><?php echo htmlentities($result->Section); ?></td>

                                                            <!-- Fecha de creación del registro -->
                                                            <td><?php echo htmlentities($result->CreationDate); ?></td>

                                                            <!-- Botón para editar el registro -->
                                                            <td>
                                                                <a href="edit-class.php?classid=<?php echo htmlentities($result->id); ?>" class="btn btn-info">
                                                                    <i class="fa fa-edit" title="Edit Record"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                <?php
                                                        // Incrementa contador
                                                        $cnt = $cnt + 1;
                                                    }
                                                }
                                                ?>
                                            </tbody>
                                        </table>

                                        <!-- /.col-md-12 -->
                                    </div>
                                </div>
                            </div>
                            <!-- /.col-md-6 -->
                        </div>
                        <!-- /.col-md-12 -->
                    </div>
            </div>
            <!-- /.panel -->
        </div>
        <!-- /.col-md-6 -->
    </div>
    <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
    </section>
    <!-- /.section -->
    </div>
    <!-- /.main-page -->
    </div>
    <!-- /.content-container -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Incluye pie de página -->
    <?php include('includes/footer.php'); ?>

<?php } ?>
