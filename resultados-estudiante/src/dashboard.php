<?php
// Inicia sesión y oculta errores (puedes cambiar esto a error_reporting(E_ALL) para depuración)
session_start();
error_reporting(0);
include(__DIR__ . '/includes/config.php');

// Verifica si el usuario ha iniciado sesión y tiene el rol de administrador
if (!isset($_SESSION['alogin']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php"); // Si no está logueado o no es admin, redirige al login
    exit;
} else {
?>

    <!-- Incluye la barra superior -->
    <?php include('includes/topbar.php'); ?>

    <div class="content-wrapper">
        <div class="content-container">

            <!-- Menú lateral izquierdo -->
            <?php include('includes/leftbar.php'); ?>

            <div class="main-page">
                <div class="container-fluid">
                    <!-- Título del dashboard -->
                    <div class="row page-title-div">
                        <div class="col-sm-6">
                            <h2 class="title">Dashboard</h2>
                        </div>
                    </div>
                </div>

                <!-- INICIO PANEL DE ESTADÍSTICAS -->
                <section class="section">
                    <div class="container-fluid">
                        <div class="row">
                            <!-- Total de estudiantes -->
                            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 text-center">
                                <a class="dashboard-stat bg-white" href="manage-students.php">
                                    <?php
                                    $sql1 = "SELECT StudentId FROM tblstudents WHERE Status = 1";
                                    $query1 = $dbh->prepare($sql1);
                                    $query1->execute();
                                    $totalstudents = $query1->rowCount();
                                    ?>
                                    <span class="number counter" style="color: #4CAF50;">👨‍🎓 <?php echo htmlentities($totalstudents); ?></span>
                                    <span class="name">Estudiantes Activos</span>
                                </a>
                            </div>

                            <!-- Total de docentes (NUEVO FASE D) -->
                            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 text-center">
                                <a class="dashboard-stat bg-white" href="manage-teacher.php">
                                    <?php
                                    $sql_teachers = "SELECT Id FROM tblteachers WHERE Status = 1";
                                    $query_teachers = $dbh->prepare($sql_teachers);
                                    $query_teachers->execute();
                                    $totalteachers = $query_teachers->rowCount();
                                    ?>
                                    <span class="number counter" style="color: #2196F3;">👨‍🏫 <?php echo htmlentities($totalteachers); ?></span>
                                    <span class="name">Docentes Activos</span>
                                </a>
                            </div>

                            <!-- Total de materias -->
                            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 text-center">
                                <a class="dashboard-stat bg-white" href="manage-subjects.php">
                                    <?php
                                    $sql = "SELECT id FROM tblsubjects";
                                    $query = $dbh->prepare($sql);
                                    $query->execute();
                                    $totalsubjects = $query->rowCount();
                                    ?>
                                    <span class="number counter" style="color: #FF9800;">📚 <?php echo htmlentities($totalsubjects); ?></span>
                                    <span class="name">Materias Registradas</span>
                                </a>
                            </div>

                            <!-- Total de clases/años -->
                            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 text-center">
                                <a class="dashboard-stat bg-white" href="manage-classes.php">
                                    <?php
                                    $sql2 = "SELECT id FROM tblclasses";
                                    $query2 = $dbh->prepare($sql2);
                                    $query2->execute();
                                    $totalclasses = $query2->rowCount();
                                    ?>
                                    <span class="number counter" style="color: #9C27B0;">🎓 <?php echo htmlentities($totalclasses); ?></span>
                                    <span class="name">Años Escolares</span>
                                </a>
                            </div>
                        </div>

                        <!-- Segunda fila de estadísticas (FASE C+D) -->
                        <div class="row" style="margin-top: 20px;">
                            <!-- Total de tutores (NUEVO FASE C) -->
                            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 text-center">
                                <a class="dashboard-stat bg-white" href="manage-students.php">
                                    <?php
                                    $sql_tutors = "SELECT DISTINCT student_tutor.teacherid FROM student_tutor";
                                    $query_tutors = $dbh->prepare($sql_tutors);
                                    $query_tutors->execute();
                                    $totaltutors = $query_tutors->rowCount();
                                    ?>
                                    <span class="number counter" style="color: #E91E63;">👨‍👩‍👧 <?php echo htmlentities($totaltutors); ?></span>
                                    <span class="name">Tutores Registrados</span>
                                </a>
                            </div>

                            <!-- Asignaciones Docente-Materia (NUEVO FASE D) -->
                            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 text-center">
                                <a class="dashboard-stat bg-white" href="assign-teacher-subject.php">
                                    <?php
                                    $sql_assign = "SELECT Id FROM tblteacher_subject";
                                    $query_assign = $dbh->prepare($sql_assign);
                                    $query_assign->execute();
                                    $totalassign = $query_assign->rowCount();
                                    ?>
                                    <span class="number counter" style="color: #F44336;">🔗 <?php echo htmlentities($totalassign); ?></span>
                                    <span class="name">Docentes Asignados</span>
                                </a>
                            </div>

                            <!-- Total de calificaciones -->
                            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 text-center">
                                <a class="dashboard-stat bg-white" href="manage-results.php">
                                    <?php
                                    $sql3 = "SELECT DISTINCT StudentId FROM tblresult";
                                    $query3 = $dbh->prepare($sql3);
                                    $query3->execute();
                                    $totalresults = $query3->rowCount();
                                    ?>
                                    <span class="number counter" style="color: #00BCD4;">📊 <?php echo htmlentities($totalresults); ?></span>
                                    <span class="name">Estudiantes con Calificaciones</span>
                                </a>
                            </div>

                            <!-- Versión del sistema -->
                            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 text-center">
                                <div class="dashboard-stat bg-white">
                                    <span class="number counter" style="color: #673AB7;">✨</span>
                                    <span class="name">Sistema v4.0 Fase D+E</span>
                                </div>
                            </div>
                        </div>

                        <!-- INICIO TABLA DE RESULTADOS MÁS RECIENTES -->
                        <section class="section">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="panel">
                                            <div class="panel-heading">
                                                <div class="panel-title">
                                                    <h5>Resultados Publicados Recientemente</h5>
                                                </div>
                                            </div>

                                            <!-- Mensajes de éxito o error (si existen) -->
                                            <?php if ($msg) { ?>
                                                <div class="alert alert-success left-icon-alert" role="alert">
                                                    <strong>¡Bien hecho!</strong> <?php echo htmlentities($msg); ?>
                                                </div>
                                            <?php } else if ($error) { ?>
                                                <div class="alert alert-danger left-icon-alert" role="alert">
                                                    <strong>¡Error!</strong> <?php echo htmlentities($error); ?>
                                                </div>
                                            <?php } ?>

                                            <div class="panel-body p-20">
                                                <!-- Tabla de resultados -->
                                                <table id="example" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Nombre Estudiante</th>
                                                            <th>ID Roll</th>
                                                            <th>Año</th>
                                                            <th>Fecha de Registro</th>
                                                            <th>Estado</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $sql = "SELECT DISTINCT 
                                                                    tblstudents.StudentName,
                                                                    tblstudents.RollId,
                                                                    tblstudents.RegDate,
                                                                    tblstudents.StudentId,
                                                                    tblstudents.Status,
                                                                    tblclasses.ClassName,
                                                                    tblclasses.Section
                                                                FROM tblresult 
                                                                JOIN tblstudents ON tblstudents.StudentId = tblresult.StudentId  
                                                                JOIN tblclasses ON tblclasses.id = tblresult.ClassId";

                                                        $query = $dbh->prepare($sql);
                                                        $query->execute();
                                                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                        $cnt = 1;

                                                        if ($query->rowCount() > 0) {
                                                            foreach ($results as $result) { ?>
                                                                <tr>
                                                                    <td><?php echo htmlentities($cnt); ?></td>
                                                                    <td><?php echo htmlentities($result->StudentName); ?></td>
                                                                    <td><?php echo htmlentities($result->RollId); ?></td>
                                                                    <td><?php echo htmlentities($result->ClassName); ?> (<?php echo htmlentities($result->Section); ?>)</td>
                                                                    <td><?php echo htmlentities($result->RegDate); ?></td>
                                                                    <td>
                                                                        <?php echo $result->Status == 1 ? 'Active' : 'Blocked'; ?>
                                                                    </td>
                                                                </tr>
                                                        <?php $cnt++;
                                                            }
                                                        } ?>
                                                    </tbody>
                                                </table>
                                            </div> <!-- /.panel-body -->
                                        </div> <!-- /.panel -->
                                    </div> <!-- /.col-md-12 -->
                                </div> <!-- /.row -->
                            </div> <!-- /.container-fluid -->
                        </section> <!-- FIN sección de resultados recientes -->

                    </div> <!-- /.container-fluid -->
                </section> <!-- /.section principal -->
            </div> <!-- /.main-page -->
        </div> <!-- /.content-container -->
    </div> <!-- /.content-wrapper -->

    <!-- Pie de página -->
    <?php include('includes/footer2.php'); ?>

<?php } ?>
