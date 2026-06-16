<?php
// Muestra todos los errores durante desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inicia sesión
session_start();
include(__DIR__ . '/includes/config.php'); // Conexión a la base de datos

// Verifica que el usuario haya iniciado sesión y que sea un maestro
if (!isset($_SESSION['alogin']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php"); // Si no es maestro o no ha iniciado sesión, redirige al login
    exit;
}

// Obtener ID del maestro
$teacher_id = $_SESSION['id'] ?? null;

if (!$teacher_id) {
    die("Error: No se encontró ID de maestro");
}

// Obtener estadísticas de anuncios del maestro
$sql_stats = "SELECT 
                COUNT(*) as total_notices,
                SUM(CASE WHEN postingDate >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as recent_notices
            FROM tblnotice WHERE created_by = :teacher_id AND is_active = 1";
$query_stats = $dbh->prepare($sql_stats);
$query_stats->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
$query_stats->execute();
$notices_stats = $query_stats->fetch(PDO::FETCH_OBJ);

// Obtener anuncios del maestro
$sql = "SELECT 
            tn.id,
            tn.noticeTitle,
            tn.noticeDetails,
            tn.postingDate,
            tn.audience_type,
            tc.ClassName,
            tc.Section,
            COUNT(DISTINCT ns.student_id) as total_students,
            SUM(CASE WHEN ns.is_viewed = 1 THEN 1 ELSE 0 END) as students_viewed
        FROM tblnotice tn
        LEFT JOIN tblclasses tc ON tn.class_id = tc.id
        LEFT JOIN notice_student ns ON tn.id = ns.notice_id
        WHERE tn.created_by = :teacher_id AND tn.is_active = 1
        GROUP BY tn.id, tn.noticeTitle, tn.noticeDetails, tn.postingDate, tn.audience_type, tc.ClassName, tc.Section
        ORDER BY tn.postingDate DESC";
$query = $dbh->prepare($sql);
$query->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
$query->execute();
$notices = $query->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Profesor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Estilos principales -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css" />
    <style>
        .notices-stats {
            background: #f8f9fa;
            border-left: 4px solid #0F9B3A;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .notices-table {
            margin-top: 20px;
        }
        .notice-badge-audience {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-all { background: #e3f2fd; color: #1976d2; }
        .badge-class { background: #f3e5f5; color: #7b1fa2; }
        .badge-selected { background: #fff3e0; color: #e65100; }
    </style>
</head>

<body>
    <!-- Incluye la barra superior común -->
    <?php include('includes/topbar.php'); ?>

    <!-- Contenedor principal del dashboard -->
    <div class="content-wrapper">
        <div class="content-container">

            <!-- Menú lateral para el maestro -->
            <?php include('includes/leftbar-teacher.php'); ?>

            <!-- Contenido principal del dashboard -->
            <div class="main-page">
                <div class="container-fluid">

                    <!-- Título de la página -->
                    <div class="row page-title-div">
                        <div class="col-md-6">
                            <h2 class="title">Dashboard del Profesor</h2>
                        </div>
                    </div>

                    <!-- Breadcrumb o navegación secundaria -->
                    <div class="row breadcrumb-div">
                        <div class="col-md-6">
                            <ul class="breadcrumb">
                                <li><a href="dashboard-teacher.php"><i class="fa fa-home"></i> Inicio</a></li>
                                <li>Resultados</li>
                                <li class="active">Gestionar Resultados</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Sección de botones de acción -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Acciones de Resultados</h5>
                                    </div>
                                </div>
                                <div class="panel-body p-20">
                                    <!-- Botón para agregar nuevo resultado -->
                                    <a href="add-result2.php" class="btn btn-success">Agregar Nuevo Resultado</a>

                                    <!-- Botón para ver/gestionar resultados -->
                                    <a href="manage-results.php" class="btn btn-info">Ver Resultados</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Fin de acciones de resultados -->

                    <!-- SECCIÓN DE ANUNCIOS -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5><i class="fa fa-bell"></i> Gestionar Anuncios y Comunicados</h5>
                                    </div>
                                </div>
                                <div class="panel-body p-20">
                                    <!-- Botones de acción -->
                                    <a href="add-teacher-notice.php" class="btn btn-primary"><i class="fa fa-plus"></i> Crear Anuncio</a>
                                    <a href="manage-teacher-notices.php" class="btn btn-info"><i class="fa fa-list"></i> Ver Mis Anuncios</a>

                                    <!-- Estadísticas -->
                                    <div class="notices-stats">
                                        <strong><i class="fa fa-bar-chart"></i> Estadísticas de Anuncios:</strong><br>
                                        Total: <strong><?php echo htmlentities($notices_stats->total_notices ?? 0); ?></strong> | 
                                        Esta Semana: <strong><?php echo htmlentities($notices_stats->recent_notices ?? 0); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Fin de sección de anuncios -->

                    <!-- Tabla de anuncios recientes -->
                    <?php if (count($notices) > 0) { ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Anuncios Recientes</h5>
                                    </div>
                                </div>
                                <div class="panel-body p-20 notices-table">
                                    <table id="noticesTable" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Título del Anuncio</th>
                                                <th>Dirigido a</th>
                                                <th>Enviado a</th>
                                                <th>Visto por</th>
                                                <th>Fecha Creación</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $cnt = 1;
                                            foreach ($notices as $notice) {
                                                $audience_desc = '';
                                                $badge_class = '';
                                                
                                                if ($notice->audience_type == 'all') {
                                                    $audience_desc = 'Todos';
                                                    $badge_class = 'badge-all';
                                                } elseif ($notice->audience_type == 'class') {
                                                    $audience_desc = htmlentities($notice->ClassName . ' - ' . $notice->Section);
                                                    $badge_class = 'badge-class';
                                                } else {
                                                    $audience_desc = 'Seleccionados';
                                                    $badge_class = 'badge-selected';
                                                }

                                                $percentage = $notice->total_students > 0 
                                                    ? round(($notice->students_viewed / $notice->total_students) * 100)
                                                    : 0;
                                            ?>
                                            <tr>
                                                <td><?php echo $cnt; ?></td>
                                                <td><strong><?php echo htmlentities($notice->noticeTitle); ?></strong></td>
                                                <td><span class="notice-badge-audience <?php echo $badge_class; ?>"><?php echo $audience_desc; ?></span></td>
                                                <td><?php echo htmlentities($notice->total_students); ?></td>
                                                <td>
                                                    <?php 
                                                    echo htmlentities($notice->students_viewed) . ' / ' . htmlentities($notice->total_students);
                                                    if ($notice->total_students > 0) {
                                                        echo ' (' . $percentage . '%)';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo htmlentities(date('d/m/Y H:i', strtotime($notice->postingDate))); ?></td>
                                                <td>
                                                    <a href="view-notice-recipients.php?id=<?php echo htmlentities($notice->id); ?>" class="btn btn-info btn-sm" title="Ver detalles">
                                                        <i class="fa fa-eye"></i> Ver
                                                    </a>
                                                    <a href="manage-teacher-notices.php?delete=<?php echo htmlentities($notice->id); ?>" onclick="return confirm('¿Deseas eliminar este anuncio?');" class="btn btn-danger btn-sm" title="Eliminar">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php $cnt++; } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Pie de página -->
    <?php include('includes/footer.php'); ?>

    <!-- Scripts -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/DataTables/datatables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#noticesTable').DataTable({
                "language": {
                    "sProcessing": "Procesando...",
                    "sLengthMenu": "Mostrar _MENU_ registros",
                    "sZeroRecords": "No se encontraron resultados",
                    "sEmptyTable": "Ningún dato disponible en esta tabla",
                    "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                    "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                    "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
                    "sInfoPostFix": "",
                    "sSearch": "Buscar:",
                    "sUrl": "",
                    "sInfoThousands": ",",
                    "sLoadingRecords": "Cargando...",
                    "oPaginate": {
                        "sFirst": "Primero",
                        "sLast": "Último",
                        "sNext": "Siguiente",
                        "sPrevious": "Anterior"
                    },
                    "oAria": {
                        "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                    }
                }
            });
        });
    </script>
</body>
</html>
  <?php include('includes/footer.php'); ?>