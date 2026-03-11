<?php
session_start(); // Inicia la sesión
error_reporting(0); // Oculta los errores (para producción; usar E_ALL para desarrollo)
include(__DIR__ . '/includes/config.php'); // Conexión a la base de datos

// Verifica si la sesión del admin está activa
if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php"); // Redirige al login si no hay sesión
} else {
    $stid = intval($_GET['stid']); // ID del estudiante desde la URL

    // Procesa el formulario al hacer clic en "Actualizar"
    if (isset($_POST['submit'])) {
        $rowid = $_POST['id'];    // IDs de las filas de resultados
        $marks = $_POST['marks']; // Calificaciones nuevas

        // Recorre cada fila y actualiza la calificación correspondiente
        foreach ($_POST['id'] as $count => $id) {
            $mrks = $marks[$count];     // Calificación nueva
            $iid = $rowid[$count];      // ID del resultado en tblresult

            $sql = "UPDATE tblresult SET marks = :mrks WHERE id = :iid";
            $query = $dbh->prepare($sql);
            $query->bindParam(':mrks', $mrks, PDO::PARAM_STR);
            $query->bindParam(':iid', $iid, PDO::PARAM_STR);
            $query->execute();

            $msg = "Información de resultados actualizada con éxito";
        }
    }
?>

<!-- Topbar de navegación -->
<?php include('includes/topbar.php'); ?>

<!-- Contenedor general -->
<div class="content-wrapper">
    <div class="content-container">

        <!-- Menú lateral -->
        <?php include('includes/leftbar.php'); ?>

        <!-- Contenido principal -->
        <div class="main-page">
            <div class="container-fluid">

                <!-- Título de la página -->
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Información del resultado del estudiante</h2>
                    </div>
                </div>

                <!-- Breadcrumb (navegación secundaria) -->
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li class="active">Información de Resultados</li>
                        </ul>
                    </div>
                </div>

            </div>

            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel">
                            <div class="panel-heading">
                                <div class="panel-title">
                                    <h5>Actualizar Información de Resultados</h5>
                                </div>
                            </div>

                            <div class="panel-body">
                                <!-- Muestra mensaje si hubo éxito o error -->
                                <?php if ($msg) { ?>
                                    <div class="alert alert-success left-icon-alert" role="alert">
                                        <strong>Proceso Correcto! </strong><?php echo htmlentities($msg); ?>
                                    </div>
                                <?php } elseif ($error) { ?>
                                    <div class="alert alert-danger left-icon-alert" role="alert">
                                        <strong>Algo salió mal! </strong><?php echo htmlentities($error); ?>
                                    </div>
                                <?php } ?>

                                <!-- Formulario para editar resultados -->
                                <form class="form-horizontal" method="post">

                                    <?php
                                    // Muestra información general del estudiante
                                    $ret = "SELECT tblstudents.StudentName, tblclasses.ClassName, tblclasses.Section 
                                            FROM tblresult 
                                            JOIN tblstudents ON tblresult.StudentId = tblstudents.StudentId 
                                            JOIN tblsubjects ON tblsubjects.id = tblresult.SubjectId 
                                            JOIN tblclasses ON tblclasses.id = tblstudents.ClassId 
                                            WHERE tblstudents.StudentId = :stid LIMIT 1";
                                    $stmt = $dbh->prepare($ret);
                                    $stmt->bindParam(':stid', $stid, PDO::PARAM_STR);
                                    $stmt->execute();
                                    $result = $stmt->fetchAll(PDO::FETCH_OBJ);

                                    if ($stmt->rowCount() > 0) {
                                        foreach ($result as $row) {
                                    ?>

                                    <!-- Clase y sección -->
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Año</label>
                                        <div class="col-sm-10">
                                            <?php echo htmlentities($row->ClassName) . " (" . htmlentities($row->Section) . ")"; ?>
                                        </div>
                                    </div>

                                    <!-- Nombre del estudiante -->
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Nombre Completo</label>
                                        <div class="col-sm-10">
                                            <?php echo htmlentities($row->StudentName); ?>
                                        </div>
                                    </div>

                                    <?php
                                        }
                                    }
                                    ?>

                                    <?php
                                    // Muestra materias y calificaciones
                                    $sql = "SELECT DISTINCT tblsubjects.SubjectName, tblresult.marks, tblresult.id as resultid 
                                            FROM tblresult 
                                            JOIN tblstudents ON tblstudents.StudentId = tblresult.StudentId 
                                            JOIN tblsubjects ON tblsubjects.id = tblresult.SubjectId 
                                            JOIN tblclasses ON tblclasses.id = tblstudents.ClassId 
                                            WHERE tblstudents.StudentId = :stid";
                                    $query = $dbh->prepare($sql);
                                    $query->bindParam(':stid', $stid, PDO::PARAM_STR);
                                    $query->execute();
                                    $results = $query->fetchAll(PDO::FETCH_OBJ);

                                    if ($query->rowCount() > 0) {
                                        foreach ($results as $result) {
                                    ?>
                                    <!-- Campo editable de calificación -->
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label"><?php echo htmlentities($result->SubjectName); ?></label>
                                        <div class="col-sm-10">
                                            <input type="hidden" name="id[]" value="<?php echo htmlentities($result->resultid); ?>">
                                            <input type="text" name="marks[]" class="form-control" value="<?php echo htmlentities($result->marks); ?>" maxlength="5" required>
                                        </div>
                                    </div>

                                    <?php
                                        }
                                    }
                                    ?>

                                    <!-- Botón para actualizar -->
                                    <div class="form-group">
                                        <div class="col-sm-offset-2 col-sm-10">
                                            <button type="submit" name="submit" class="btn btn-primary">Actualizar</button>
                                        </div>
                                    </div>

                                </form>
                                <!-- Fin del formulario -->

                            </div>
                        </div>
                    </div> <!-- /.col-md-12 -->
                </div>
            </div>

        </div> <!-- /.main-page -->
    </div> <!-- /.content-container -->
</div> <!-- /.content-wrapper -->

<?php include('includes/footer.php'); ?>
<?php } ?>

