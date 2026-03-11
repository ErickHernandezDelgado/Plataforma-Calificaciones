<?php
// Inicia la sesión
session_start();

// Desactiva la visualización de errores
error_reporting(0);

// Incluye archivo de configuración (conexión a la base de datos, etc.)
include(__DIR__ . '/includes/config.php');

// Verifica si el administrador ha iniciado sesión
if (strlen($_SESSION['alogin']) == "") {
    // Si no ha iniciado sesión, redirige al login
    header("Location: index.php");
} else {
    // Proceso para eliminar un comunicado si se recibe el parámetro 'id' vía GET
    if ($_GET['id']) {
        // Obtiene el ID del comunicado a eliminar
        $id = $_GET['id'];

        // Prepara consulta SQL para eliminar el comunicado con el ID dado
        $sql = "delete from tblnotice where id=:id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':id', $id, PDO::PARAM_STR);

        // Ejecuta la consulta de eliminación
        $query->execute();

        // Muestra alerta confirmando eliminación
        echo '<script>alert("Comunicado Eliminado")</script>';

        // Redirige nuevamente a la página de gestión de comunicados
        echo "<script>window.location.href ='manage-notices.php'</script>";
    }
?>

    <!-- Incluye estilos para DataTables -->
    <link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css" />

    <!-- Incluye la barra superior -->
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
                            <h2 class="title">Gestionar Comunicado</h2>
                        </div>
                    </div>

                    <!-- Breadcrumb de navegación -->
                    <div class="row breadcrumb-div">
                        <div class="col-md-6">
                            <ul class="breadcrumb">
                                <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                                <li> Años</li>
                                <li class="active">Gestionar Comunicados</li>
                            </ul>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

                <!-- Sección principal -->
                <section class="section">
                    <div class="container-fluid">

                        <div class="row">
                            <div class="col-md-12">

                                <div class="panel">

                                    <!-- Encabezado del panel -->
                                    <div class="panel-heading">
                                        <div class="panel-title">
                                            <h5>Ver Información de Comunicados</h5>
                                        </div>
                                    </div>

                                    <!-- Cuerpo del panel -->
                                    <div class="panel-body p-20">

                                        <!-- Tabla que muestra los comunicados -->
                                        <table id="example" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Título de Comunicado</th>
                                                    <th>Información de Comunicado</th>
                                                    <th>Fecha Creación</th>
                                                    <th>Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Consulta para obtener todos los comunicados
                                                $sql = "SELECT * from tblnotice";
                                                $query = $dbh->prepare($sql);
                                                $query->execute();

                                                // Obtiene todos los resultados como objetos
                                                $results = $query->fetchAll(PDO::FETCH_OBJ);

                                                // Contador para numerar filas
                                                $cnt = 1;

                                                // Si existen registros, los muestra en la tabla
                                                if ($query->rowCount() > 0) {
                                                    foreach ($results as $result) { ?>
                                                        <tr>
                                                            <!-- Número consecutivo -->
                                                            <td><?php echo htmlentities($cnt); ?></td>

                                                            <!-- Título del comunicado -->
                                                            <td><?php echo htmlentities($result->noticeTitle); ?></td>

                                                            <!-- Detalles del comunicado -->
                                                            <td><?php echo htmlentities($result->noticeDetails); ?></td>

                                                            <!-- Fecha de publicación -->
                                                            <td><?php echo htmlentities($result->postingDate); ?></td>

                                                            <!-- Botón para eliminar el comunicado -->
                                                            <td>
                                                                <a href="manage-notices.php?id=<?php echo htmlentities($result->id); ?>" onclick="return confirm('Deseas eliminar este comunicado?');" class="btn btn-danger">
                                                                    <i class="fa fa-trash" title="Delete this Record"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                <?php
                                                        // Incrementa el contador
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

