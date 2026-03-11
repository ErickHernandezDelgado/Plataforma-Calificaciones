<?php 
// Inicia la sesión
session_start();

// Desactiva la visualización de errores
error_reporting(0);

// Incluye archivo de configuración (conexión a base de datos, etc.)
include(__DIR__ . '/includes/config.php');

// Verifica si el usuario ha iniciado sesión correctamente
if (strlen($_SESSION['alogin']) == "") {
    // Si no ha iniciado sesión, redirige al login
    header("Location: index.php");
    exit;
}

// Obtiene el ID y rol del maestro desde la sesión (si aplica)
$teacherId = $_SESSION['teacherid'] ?? null;
$teacherRole = $_SESSION['role'] ?? null;
$teacherSubjectId = null;

// Si el usuario tiene rol de maestro y está autenticado
if ($teacherRole === 'teacher' && $teacherId) {
    // Consulta para obtener la materia asignada al maestro
    $sql = "SELECT SubjectId FROM tblteacher_subject WHERE TeacherId = :teacherid LIMIT 1";
    $query = $dbh->prepare($sql);
    $query->bindParam(':teacherid', $teacherId, PDO::PARAM_INT);
    $query->execute();
    $res = $query->fetch(PDO::FETCH_ASSOC);

    // Si se encuentra una materia, se asigna a la variable
    if ($res) {
        $teacherSubjectId = intval($res['SubjectId']);
    }
}

// Variables para mensajes de éxito o error
$msg = "";
$error = "";
?>

<!-- Hoja de estilos para DataTables -->
<link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css" />

<!-- Incluye barra superior -->
<?php include('includes/topbar.php'); ?>

<div class="content-wrapper">
    <div class="content-container">

        <!-- Incluye barra lateral -->
        <?php include('includes/leftbar.php'); ?>

        <div class="main-page">
            <div class="container-fluid">

                <!-- Encabezado de la página -->
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Gestionar Resultados</h2>
                    </div>
                </div>

                <!-- Breadcrumb de navegación -->
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li>Resultados</li>
                            <li class="active">Gestionar Resultados</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- FORMULARIO PARA GENERAR PDF DE BOLETAS -->
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">

                        <!-- Formulario con selector de grupo para generar PDF -->
                        <form method="GET" action="generate-report-cards.php" target="_blank" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 6px;">
                            <div class="form-group">
                                <label for="classid"><strong>Seleccionar Grupo para Generar Boletas PDF:</strong></label>
                                
                                <!-- Selector de grupo -->
                                <select name="classid" id="classid" class="form-control" required style="display: inline-block; width: auto; margin-right: 10px;">
                                    <option value="">-- Seleccionar Grupo --</option>
                                    <?php
                                    // Consulta para obtener todos los grupos ordenados por nombre y sección
                                    $sql = "SELECT id, ClassName, Section FROM tblclasses ORDER BY ClassName, Section";
                                    $query = $dbh->prepare($sql);
                                    $query->execute();
                                    $classes = $query->fetchAll(PDO::FETCH_ASSOC);

                                    // Genera las opciones del selector
                                    foreach ($classes as $cls) {
                                        echo '<option value="' . $cls['id'] . '">' . htmlentities($cls['ClassName'] . ' ' . $cls['Section']) . '</option>';
                                    }
                                    ?>
                                </select>

                                <!-- Botón para generar PDF -->
                                <button type="submit" class="btn btn-primary">Generar PDF de Boletas</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- TABLA DE RESULTADOS -->
            <section class="section">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">

                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Ver Información de Resultados</h5>
                                    </div>
                                </div>

                                <!-- Mensaje de éxito o error -->
                                <?php if ($msg) { ?>
                                    <div class="alert alert-success left-icon-alert" role="alert">
                                        <strong>Proceso Correcto! </strong><?php echo htmlentities($msg); ?>
                                    </div>
                                <?php } else if ($error) { ?>
                                    <div class="alert alert-danger left-icon-alert" role="alert">
                                        <strong>Algo salió mal! </strong> <?php echo htmlentities($error); ?>
                                    </div>
                                <?php } ?>

                                <div class="panel-body p-20">

                                    <!-- Tabla de resultados registrados -->
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
                                            // Consulta para obtener estudiantes con resultados registrados
                                            $sql = "SELECT DISTINCT tblstudents.StudentName, tblstudents.RollId, tblstudents.RegDate, tblstudents.StudentId, 
                                                            tblstudents.Status, tblclasses.ClassName, tblclasses.Section
                                                    FROM tblresult 
                                                    JOIN tblstudents ON tblstudents.StudentId = tblresult.StudentId  
                                                    JOIN tblclasses ON tblclasses.id = tblresult.ClassId";
                                            $query = $dbh->prepare($sql);
                                            $query->execute();
                                            $results = $query->fetchAll(PDO::FETCH_OBJ);

                                            // Contador de filas
                                            $cnt = 1;

                                            // Recorre resultados y los muestra en la tabla
                                            if ($query->rowCount() > 0) {
                                                foreach ($results as $result) { ?>
                                                    <tr>
                                                        <td><?php echo htmlentities($cnt); ?></td>
                                                        <td><?php echo htmlentities($result->StudentName); ?></td>
                                                        <td><?php echo htmlentities($result->RollId); ?></td>
                                                        <td><?php echo htmlentities($result->ClassName); ?> (<?php echo htmlentities($result->Section); ?>)</td>
                                                        <td><?php echo htmlentities($result->RegDate); ?></td>
                                                        <td><?php echo $result->Status == 1 ? 'Activo' : 'Bloqueado'; ?></td>
                                                        <td>
                                                            <!-- Botón para editar resultados del estudiante -->
                                                            <a href="edit-result.php?stid=<?php echo htmlentities($result->StudentId); ?>" class="btn btn-info">
                                                                <i class="fa fa-edit" title="Editar Registro"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                            <?php $cnt++; }
                                            } ?>
                                        </tbody>
                                    </table>

                                </div>
                            </div>

                        </div>
                    </div> <!-- /.row -->
                </div> <!-- /.container-fluid -->
            </section> <!-- /.section -->
        </div> <!-- /.main-page -->
    </div> <!-- /.content-container -->
</div> <!-- /.content-wrapper -->

<!-- Incluye el pie de página -->
<?php include('includes/footer.php'); ?>
