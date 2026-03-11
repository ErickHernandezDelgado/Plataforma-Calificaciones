<?php 
// Inicia la sesión
session_start();

// Desactiva la visualización de errores
error_reporting(0);

// Incluye archivo de configuración (conexión a la base de datos)
include(__DIR__ . '/includes/config.php');

// Verifica si el usuario ha iniciado sesión
if (strlen($_SESSION['alogin']) == "") {
    // Si no hay sesión activa, redirige al login
    header("Location: index.php");
    exit;
}

// Obtiene el ID del maestro y su rol desde la sesión
$teacherId = $_SESSION['teacherid'] ?? null;
$teacherRole = $_SESSION['role'] ?? null;
$teacherSubjectId = null;

// Si el usuario tiene rol de maestro y tiene un ID válido
if ($teacherRole === 'teacher' && $teacherId) {
    // Consulta para obtener el ID de la materia asignada al maestro
    $sql = "SELECT SubjectId FROM tblteacher_subject WHERE TeacherId = :teacherid LIMIT 1";
    $query = $dbh->prepare($sql);
    $query->bindParam(':teacherid', $teacherId, PDO::PARAM_INT);
    $query->execute();
    $res = $query->fetch(PDO::FETCH_ASSOC);

    // Si se encontró una materia asignada, la guarda en la variable
    if ($res) {
        $teacherSubjectId = intval($res['SubjectId']);
    }
}

// Variables de mensajes vacías (pueden usarse para mostrar notificaciones)
$msg = "";
$error = "";
?>

<!-- Incluye hoja de estilos de DataTables -->
<link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css" />

<!-- Incluye la barra superior -->
<?php include('includes/topbar.php'); ?>

<div class="content-wrapper">
    <div class="content-container">

        <!-- Incluye la barra lateral izquierda -->
        <?php include('includes/leftbar.php'); ?>

        <div class="main-page">
            <div class="container-fluid">

                <!-- Título principal de la página -->
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Gestionar Resultados</h2>
                    </div>
                </div>

                <!-- Breadcrumb para navegación -->
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

                        <!-- Formulario que envía el grupo seleccionado a generate-report-cards.php -->
                        <form method="GET" action="generate-report-cards.php" target="_blank" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 6px;">
                            <div class="form-group">

                                <!-- Etiqueta para el selector de grupo -->
                                <label for="classid"><strong>Seleccionar Grupo para Generar Boletas PDF:</strong></label>

                                <!-- Selector desplegable de clases para secundaria (grados 7 a 9) -->
                                <select name="classid" id="classid" class="form-control" required style="display: inline-block; width: auto; margin-right: 10px;">
                                    <option value="">-- Seleccionar Grupo --</option>
                                    <?php
                                    // Consulta para obtener los grupos de secundaria en secciones A, B y C
                                    $sql = "SELECT id, ClassName, Section FROM tblclasses 
                                            WHERE ClassNameNumeric BETWEEN 7 AND 9
                                            AND Section IN ('A', 'B', 'C')
                                            ORDER BY ClassNameNumeric, Section";
                                    $query = $dbh->prepare($sql);
                                    $query->execute();
                                    $classes = $query->fetchAll(PDO::FETCH_ASSOC);

                                    // Recorre los resultados y genera las opciones del select
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

        </div> <!-- /.main-page -->
    </div> <!-- /.content-container -->
</div> <!-- /.content-wrapper -->

<!-- Incluye pie de página -->
<?php include('includes/footer.php'); ?>
