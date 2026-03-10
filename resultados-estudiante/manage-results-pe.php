<!-- FORMULARIO PARA GENERAR PDF DE BOLETAS PRIMARIA ESPAÑOL-->
<?php 
// Inicia la sesión
session_start();

// Desactiva la visualización de errores
error_reporting(0);

// Incluye archivo de configuración (conexión a base de datos, etc.)
include('includes/config.php');

// Verifica si el usuario ha iniciado sesión como administrador o maestro
if (strlen($_SESSION['alogin']) == "") {
    // Si no ha iniciado sesión, redirige a la página de login
    header("Location: index.php");
    exit;
}

// Obtiene el ID y rol del maestro desde la sesión
$teacherId = $_SESSION['teacherid'] ?? null;
$teacherRole = $_SESSION['role'] ?? null;
$teacherSubjectId = null;

// Si el usuario es maestro y tiene ID asignado
if ($teacherRole === 'teacher' && $teacherId) {
    // Consulta para obtener la materia que imparte el maestro
    $sql = "SELECT SubjectId FROM tblteacher_subject WHERE TeacherId = :teacherid LIMIT 1";
    $query = $dbh->prepare($sql);
    $query->bindParam(':teacherid', $teacherId, PDO::PARAM_INT);
    $query->execute();
    $res = $query->fetch(PDO::FETCH_ASSOC);

    // Si encontró materia, la asigna a la variable
    if ($res) {
        $teacherSubjectId = intval($res['SubjectId']);
    }
}

// Variables para mensajes (vacías por ahora)
$msg = "";
$error = "";
?>

<!-- Incluye estilos para DataTables -->
<link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css" />

<!-- Incluye la barra superior -->
<?php include('includes/topbar.php'); ?>

<div class="content-wrapper">
    <div class="content-container">

        <!-- Incluye barra lateral izquierda -->
        <?php include('includes/leftbar.php'); ?>

        <div class="main-page">
            <div class="container-fluid">

                <!-- Título principal -->
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Gestionar Resultados</h2>
                    </div>
                </div>

                <!-- Breadcrumb -->
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

                        <!-- Formulario que envía GET a generate-report-cards.php y abre en nueva pestaña -->
                        <form method="GET" action="generate-report-cards.php" target="_blank" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 6px;">
                            <div class="form-group">

                                <!-- Etiqueta para selector de grupo -->
                                <label for="classid"><strong>Seleccionar Grupo para Generar Boletas PDF:</strong></label>

                                <!-- Selector desplegable para grupos -->
                                <select name="classid" id="classid" class="form-control" required style="display: inline-block; width: auto; margin-right: 10px;">
                                    <option value="">-- Seleccionar Grupo --</option>
                                    <?php
                                    // Consulta para obtener grupos de primaria (1 a 6) y secciones A, B, C
                                    $sql = "SELECT id, ClassName, Section FROM tblclasses 
                                            WHERE ClassNameNumeric BETWEEN 1 AND 6 
                                            AND Section IN ('A', 'B', 'C')
                                            ORDER BY ClassNameNumeric, Section";
                                    $query = $dbh->prepare($sql);
                                    $query->execute();

                                    // Obtiene todos los grupos
                                    $classes = $query->fetchAll(PDO::FETCH_ASSOC);

                                    // Recorre y crea opciones del select con nombre y sección
                                    foreach ($classes as $cls) {
                                        echo '<option value="' . $cls['id'] . '">' . htmlentities($cls['ClassName'] . ' ' . $cls['Section']) . '</option>';
                                    }
                                    ?>
                                </select>

                                <!-- Botón para enviar formulario y generar PDF -->
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
