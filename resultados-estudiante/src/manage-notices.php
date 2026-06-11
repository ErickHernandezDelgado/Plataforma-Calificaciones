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
                                                    <th>Dirigido a</th>
                                                    <th>Enviado a</th>
                                                    <th>Visto por</th>
                                                    <th>Fecha Creación</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Consulta para obtener todos los comunicados con información de audiencia
                                                $sql = "SELECT 
                                                            tn.id,
                                                            tn.noticeTitle,
                                                            tn.noticeDetails,
                                                            tn.postingDate,
                                                            tn.audience_type,
                                                            COALESCE(tc.ClassName, 'N/A') as ClassName,
                                                            COALESCE(tc.Section, 'N/A') as Section,
                                                            COUNT(DISTINCT ns.student_id) as total_students,
                                                            SUM(CASE WHEN ns.is_viewed = 1 THEN 1 ELSE 0 END) as students_viewed
                                                        FROM tblnotice tn
                                                        LEFT JOIN tblclasses tc ON tn.class_id = tc.id
                                                        LEFT JOIN notice_student ns ON tn.id = ns.notice_id
                                                        WHERE tn.is_active = 1
                                                        GROUP BY tn.id, tn.noticeTitle, tn.noticeDetails, tn.postingDate, tn.audience_type, tc.ClassName, tc.Section
                                                        ORDER BY tn.postingDate DESC";
                                                
                                                $query = $dbh->prepare($sql);
                                                $query->execute();

                                                // Obtiene todos los resultados como objetos
                                                $results = $query->fetchAll(PDO::FETCH_OBJ);

                                                // Contador para numerar filas
                                                $cnt = 1;

                                                // Si existen registros, los muestra en la tabla
                                                if ($query->rowCount() > 0) {
                                                    foreach ($results as $result) { 
                                                        // Determinar descripción de audiencia
                                                        $audience_desc = '';
                                                        if ($result->audience_type == 'all') {
                                                            $audience_desc = 'Todos';
                                                        } elseif ($result->audience_type == 'class') {
                                                            $audience_desc = $result->ClassName . ' - ' . $result->Section;
                                                        } else {
                                                            $audience_desc = 'Seleccionados';
                                                        }
                                                        ?>
                                                        <tr>
                                                            <!-- Número consecutivo -->
                                                            <td><?php echo htmlentities($cnt); ?></td>

                                                            <!-- Título del comunicado -->
                                                            <td><?php echo htmlentities($result->noticeTitle); ?></td>

                                                            <!-- Tipo de audiencia -->
                                                            <td>
                                                                <span class="label label-info"><?php echo htmlentities($audience_desc); ?></span>
                                                            </td>

                                                            <!-- Total de estudiantes -->
                                                            <td><?php echo htmlentities($result->total_students); ?></td>

                                                            <!-- Estudiantes que han visto el anuncio -->
                                                            <td>
                                                                <?php 
                                                                echo htmlentities($result->students_viewed) . ' / ' . htmlentities($result->total_students);
                                                                if ($result->total_students > 0) {
                                                                    $percentage = round(($result->students_viewed / $result->total_students) * 100);
                                                                    echo ' (' . $percentage . '%)';
                                                                }
                                                                ?>
                                                            </td>

                                                            <!-- Fecha de publicación -->
                                                            <td><?php echo htmlentities($result->postingDate); ?></td>

                                                            <!-- Botones de acción -->
                                                            <td>
                                                                <button class="btn btn-info btn-sm" onclick="loadNoticeDetails(<?php echo intval($result->id); ?>)" title="Ver detalles del anuncio">
                                                                    <i class="fa fa-eye"></i> Ver
                                                                </button>
                                                                <a href="manage-notices.php?id=<?php echo htmlentities($result->id); ?>" onclick="return confirm('Deseas eliminar este comunicado?');" class="btn btn-danger btn-sm" title="Eliminar este anuncio">
                                                                    <i class="fa fa-trash"></i>
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

    <!-- Modal para ver detalles del anuncio -->
    <div class="modal fade" id="noticeDetailsModal" tabindex="-1" role="dialog" aria-labelledby="noticeDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #0F9B3A 0%, #065D21 100%); color: white;">
                    <h5 class="modal-title" id="noticeDetailsModalLabel" style="color: white;">Detalles del Anuncio</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 1;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="noticeDetailsContent">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin" style="font-size: 2rem; color: #0F9B3A;"></i>
                        <p>Cargando detalles...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        function loadNoticeDetails(noticeId) {
            $.ajax({
                url: 'get-notice-details.php?id=' + noticeId,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const notice = response.notice;
                        let html = '<div style="padding: 0;">';
                        
                        // Header info
                        html += '<div style="background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-left: 4px solid #0F9B3A;">';
                        html += '<p style="margin: 5px 0;"><strong>Creado por:</strong> ' + notice.created_by + '</p>';
                        html += '<p style="margin: 5px 0;"><strong>Fecha:</strong> ' + notice.date + '</p>';
                        html += '<p style="margin: 5px 0;"><strong>Dirigido a:</strong> ' + notice.audience + '</p>';
                        html += '</div>';
                        
                        // Stats boxes
                        html += '<div class="row" style="margin-bottom: 20px;">';
                        html += '<div class="col-md-3 text-center"><div style="background: #e8f5e9; padding: 15px; border-radius: 5px;"><div style="font-size: 1.5rem; color: #0F9B3A; font-weight: bold;">' + notice.total_recipients + '</div><small>Total Destinatarios</small></div></div>';
                        html += '<div class="col-md-3 text-center"><div style="background: #c8e6c9; padding: 15px; border-radius: 5px;"><div style="font-size: 1.5rem; color: #2e7d32; font-weight: bold;">' + notice.viewed_count + '</div><small>Ya Visto</small></div></div>';
                        html += '<div class="col-md-3 text-center"><div style="background: #fff9c4; padding: 15px; border-radius: 5px;"><div style="font-size: 1.5rem; color: #f57f17; font-weight: bold;">' + notice.pending_count + '</div><small>Pendiente</small></div></div>';
                        html += '<div class="col-md-3 text-center"><div style="background: #e0f2f1; padding: 15px; border-radius: 5px;"><div style="font-size: 1.5rem; color: #00695c; font-weight: bold;">' + notice.percentage + '%</div><small>Tasa Lectura</small></div></div>';
                        html += '</div>';
                        
                        // Content
                        html += '<div style="margin-bottom: 20px; padding: 15px; background: #fafafa; border-radius: 5px;">';
                        html += '<h6 style="color: #0F9B3A; margin-bottom: 10px;"><strong>Contenido del Anuncio:</strong></h6>';
                        html += '<p style="line-height: 1.6; color: #555; white-space: pre-wrap;">' + notice.content + '</p>';
                        html += '</div>';
                        
                        // Recipients table
                        html += '<div style="margin-top: 20px;">';
                        html += '<h6 style="color: #0F9B3A; margin-bottom: 10px;"><strong>Destinatarios y Estado de Lectura:</strong></h6>';
                        html += '<div style="max-height: 400px; overflow-y: auto;">';
                        html += response.recipients_html;
                        html += '</div>';
                        html += '</div>';
                        
                        html += '</div>';
                        
                        $('#noticeDetailsContent').html(html);
                        $('#noticeDetailsModal').modal('show');
                    } else {
                        $('#noticeDetailsContent').html('<div class="alert alert-danger">Error: ' + response.message + '</div>');
                    }
                },
                error: function() {
                    $('#noticeDetailsContent').html('<div class="alert alert-danger">Error al cargar los detalles del anuncio.</div>');
                }
            });
        }
    </script>

<?php } ?>

