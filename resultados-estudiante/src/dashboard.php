<?php
// 1. Inicia sesión de forma limpia
session_start();
// Desactivamos la visualización de errores en pantalla para evitar que se filtren rutas de carpetas
error_reporting(0); 
ini_set('display_errors', 0);
include(__DIR__ . '/includes/config.php');

// 2. Verificación de seguridad
if (!isset($_SESSION['alogin']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SGC | Dashboard</title>
    <link rel="stylesheet" href="css/bootstrap.min.css" media="screen" >
    <link rel="stylesheet" href="css/font-awesome.min.css" media="screen" >
    <link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css" />
    <link rel="stylesheet" href="css/main.css" media="screen" >
    
    <style>
        .main-page { padding: 25px; background: #f4f7f6; }
        /* Tarjetas de estadísticas mejoradas */
        .dashboard-stat {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s;
            border-bottom: 4px solid #0F9B3A; /* Detalle Verde IPT */
        }
        .dashboard-stat:hover { transform: translateY(-5px); }
        .dashboard-stat .number { font-size: 28px; font-weight: bold; display: block; }
        .dashboard-stat .name { color: #666; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
        
        /* Panel de tabla */
        .panel-verde-ipt { border-top: 5px solid #0F9B3A; border-radius: 8px; }
    </style>
</head>
<body class="top-navbar-fixed">
    <div class="main-wrapper">
        <?php include('includes/topbar.php'); ?>

        <div class="content-wrapper">
            <div class="content-container">
                <?php include('includes/leftbar.php'); ?>

                <div class="main-page">
                    <div class="container-fluid">
                        <div class="row page-title-div">
                            <div class="col-sm-6">
                                <h2 class="title">Panel de Control (Dashboard)</h2>
                            </div>
                        </div>

                        <section class="section">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-lg-3 col-md-3 col-sm-6 text-center">
                                        <a class="dashboard-stat bg-white" href="manage-students.php">
                                            <?php 
                                            $q1 = $dbh->prepare("SELECT StudentId FROM tblstudents WHERE Status = 1");
                                            $q1->execute();
                                            ?>
                                            <span class="number" style="color: #0F9B3A;"><?= $q1->rowCount(); ?></span>
                                            <span class="name">Estudiantes Activos</span>
                                        </a>
                                    </div>

                                    <div class="col-lg-3 col-md-3 col-sm-6 text-center">
                                        <a class="dashboard-stat bg-white" href="manage-teacher.php">
                                            <?php 
                                            $q_t = $dbh->prepare("SELECT Id FROM tblteachers WHERE Status = 1");
                                            $q_t->execute();
                                            ?>
                                            <span class="number" style="color: #2196F3;"><?= $q_t->rowCount(); ?></span>
                                            <span class="name">Docentes Activos</span>
                                        </a>
                                    </div>

                                    <div class="col-lg-3 col-md-3 col-sm-6 text-center">
                                        <a class="dashboard-stat bg-white" href="manage-subjects.php">
                                            <?php 
                                            $q_s = $dbh->prepare("SELECT id FROM tblsubjects");
                                            $q_s->execute();
                                            ?>
                                            <span class="number" style="color: #FF9800;"><?= $q_s->rowCount(); ?></span>
                                            <span class="name">Materias Totales</span>
                                        </a>
                                    </div>

                                    <div class="col-lg-3 col-md-3 col-sm-6 text-center">
                                        <a class="dashboard-stat bg-white" href="manage-classes.php">
                                            <?php 
                                            $q_c = $dbh->prepare("SELECT id FROM tblclasses");
                                            $q_c->execute();
                                            ?>
                                            <span class="number" style="color: #9C27B0;"><?= $q_c->rowCount(); ?></span>
                                            <span class="name">Años / Grupos</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="section">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="panel panel-verde-ipt">
                                            <div class="panel-heading">
                                                <div class="panel-title">
                                                    <h5>Últimas Calificaciones Publicadas</h5>
                                                </div>
                                            </div>
                                            <div class="panel-body p-20">
                                                <table id="recentResults" class="display table table-striped table-bordered" width="100%">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Estudiante</th>
                                                            <th>ID Roll</th>
                                                            <th>Grupo</th>
                                                            <th>Fecha Registro</th>
                                                            <th>Estado</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $sql = "SELECT DISTINCT s.StudentName, s.RollId, s.RegDate, s.Status, c.ClassName, c.Section
                                                                FROM tblresult r
                                                                JOIN tblstudents s ON s.StudentId = r.StudentId  
                                                                JOIN tblclasses c ON c.id = r.ClassId
                                                                ORDER BY s.RegDate DESC LIMIT 10";
                                                        $query = $dbh->prepare($sql);
                                                        $query->execute();
                                                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                        $cnt = 1;
                                                        foreach ($results as $res) { ?>
                                                            <tr>
                                                                <td><?= $cnt++; ?></td>
                                                                <td><strong><?= htmlentities($res->StudentName); ?></strong></td>
                                                                <td><?= htmlentities($res->RollId); ?></td>
                                                                <td><?= htmlentities($res->ClassName); ?> (<?= htmlentities($res->Section); ?>)</td>
                                                                <td><?= htmlentities($res->RegDate); ?></td>
                                                                <td><?= ($res->Status == 1) ? '<span class="label label-success">Activo</span>' : '<span class="label label-danger">Inactivo</span>'; ?></td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
        <?php include('includes/footer2.php'); ?>
    </div>

    <script src="js/jquery/jquery-2.2.4.min.js"></script>
    <script src="js/bootstrap/bootstrap.min.js"></script>
    <script src="assets/js/DataTables/datatables.min.js"></script>
    <script>
        $(function() {
            $('#recentResults').DataTable({
                "language": { "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json" },
                "searching": false, 
                "lengthChange": false,
                "info": false,
                "paging": true,
                "pageLength": 5
            });
        });
    </script>
</body>
</html>
    <?php include('includes/footer.php'); ?>