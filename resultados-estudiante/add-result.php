<?php
// Inicia la sesión
session_start();

// Desactiva los reportes de error (útil en producción, pero no recomendado para desarrollo)
error_reporting(0);

// Incluye la configuración de la base de datos
include('includes/config.php');

// Verifica si el usuario ha iniciado sesión
if (strlen($_SESSION['alogin']) == "") {
    // Redirige al login si no ha iniciado sesión
    header("Location: index.php");
} else {
    // Si se envió el formulario
    if (isset($_POST['submit'])) {

        // Inicializa arreglo de calificaciones
        $marks = array();
        $class = $_POST['class']; // ID de clase (año y sección)
        $studentid = $_POST['studentid']; // ID del estudiante
        $mark = $_POST['marks']; // Array de calificaciones por materia

        // Consulta las materias correspondientes a la clase seleccionada
        $stmt = $dbh->prepare("SELECT tblsubjects.SubjectName,tblsubjects.id 
                               FROM tblsubjectcombination 
                               JOIN tblsubjects ON tblsubjects.id = tblsubjectcombination.SubjectId 
                               WHERE tblsubjectcombination.ClassId = :cid 
                               ORDER BY tblsubjects.SubjectName");
        $stmt->execute(array(':cid' => $class));

        // Almacena los IDs de las materias en el arreglo $sid1
        $sid1 = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($sid1, $row['id']);
        }

        // Inserta los resultados en la tabla tblresult por cada materia
        for ($i = 0; $i < count($mark); $i++) {
            $mar = $mark[$i]; // calificación
            $sid = $sid1[$i]; // ID de la materia correspondiente
            $sql = "INSERT INTO tblresult(StudentId, ClassId, SubjectId, marks) 
                    VALUES(:studentid, :class, :sid, :marks)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':studentid', $studentid, PDO::PARAM_STR);
            $query->bindParam(':class', $class, PDO::PARAM_STR);
            $query->bindParam(':sid', $sid, PDO::PARAM_STR);
            $query->bindParam(':marks', $mar, PDO::PARAM_STR);
            $query->execute();

            // Verifica si la inserción fue exitosa
            $lastInsertId = $dbh->lastInsertId();
            if ($lastInsertId) {
                $msg = "Resultado Agregado Correctamente";
            } else {
                $error = "Something went wrong. Please try again";
            }
        }
    }
?>

<!-- Script AJAX para obtener estudiantes y materias por clase -->
<script>
    function getStudent(val) {
        // Carga lista de estudiantes
        $.ajax({
            type: "POST",
            url: "get_student.php",
            data: 'classid=' + val,
            success: function(data) {
                $("#studentid").html(data);
            }
        });

        // Carga materias
        $.ajax({
            type: "POST",
            url: "get_student.php",
            data: 'classid1=' + val,
            success: function(data) {
                $("#subject").html(data);
            }
        });
    }
</script>

<!-- Script AJAX para cargar resultados actuales del estudiante -->
<script>
    function getresult(val, clid) {
        var clid = $(".clid").val(); // ID de clase
        var val = $(".stid").val(); // ID de estudiante
        var abh = clid + '$' + val;
        $.ajax({
            type: "POST",
            url: "get_student.php",
            data: 'studclass=' + abh,
            success: function(data) {
                $("#reslt").html(data);
            }
        });
    }
</script>

<!-- Incluye barra superior -->
<?php include('includes/topbar.php'); ?>

<!-- Contenedor principal -->
<div class="content-wrapper">
    <div class="content-container">

        <!-- Barra lateral dependiendo del rol -->
        <?php
        if ($_SESSION['rol'] == 'teacher') {
            include('includes/leftbar-teacher.php');
        } else {
            include('includes/leftbar.php'); // Para admin u otros roles
        }
        ?>

        <!-- Página principal -->
        <div class="main-page">
            <div class="container-fluid">
                <!-- Título de la página -->
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Agregar Resultado</h2>
                    </div>
                </div>

                <!-- Ruta de navegación -->
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li class="active">Agregar Resultado</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sección principal del formulario -->
            <section class="section">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2">
                            <div class="panel">
                                <div class="panel-body">

                                    <!-- Mensajes de éxito o error -->
                                    <?php if ($msg) { ?>
                                        <div class="alert alert-success left-icon-alert" role="alert">
                                            <strong>Proceso Correcto! </strong><?php echo htmlentities($msg); ?>
                                        </div>
                                    <?php } else if ($error) { ?>
                                        <div class="alert alert-danger left-icon-alert" role="alert">
                                            <strong>Algo salió mal! </strong> <?php echo htmlentities($error); ?>
                                        </div>
                                    <?php } ?>

                                    <!-- Formulario de ingreso de resultados -->
                                    <form method="post">
                                        <!-- Selección de clase -->
                                        <div class="form-group">
                                            <label for="default" class="control-label">Año</label>
                                            <select name="class" class="form-control clid" id="classid" onChange="getStudent(this.value);" required>
                                                <option value="">Seleccionar Año</option>
                                                <?php
                                                $sql = "SELECT * FROM tblclasses";
                                                $query = $dbh->prepare($sql);
                                                $query->execute();
                                                $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                if ($query->rowCount() > 0) {
                                                    foreach ($results as $result) {
                                                ?>
                                                        <option value="<?php echo htmlentities($result->id); ?>">
                                                            <?php echo htmlentities($result->ClassName); ?>&nbsp; Section-<?php echo htmlentities($result->Section); ?>
                                                        </option>
                                                <?php }
                                                } ?>
                                            </select>
                                        </div>

                                        <!-- Selección de estudiante -->
                                        <div class="form-group">
                                            <label for="date" class="control-label">Nombre del Estudiante</label>
                                            <select name="studentid" class="form-control stid" id="studentid" required onChange="getresult(this.value);">
                                            </select>
                                        </div>

                                        <!-- Resultados existentes -->
                                        <div class="form-group">
                                            <div id="reslt"></div>
                                        </div>

                                        <!-- Materias disponibles -->
                                        <div class="form-group">
                                            <label for="date" class="control-label">Materia</label>
                                            <div id="subject"></div>
                                        </div>

                                        <!-- Botón para enviar -->
                                        <div class="form-group">
                                            <button type="submit" name="submit" id="submit" class="btn btn-success">Mostrar Resultados</button>
                                        </div>
                                    </form>
                                    <!-- Fin del formulario -->

                                </div>
                            </div>
                        </div>
                        <!-- /.col-md-12 -->
                    </div>
                </div>
            </section>
        </div>
        <!-- /.main-page -->
    </div>
    <!-- /.content-container -->
</div>
<!-- /.content-wrapper -->

<!-- Pie de página -->
<?php include('includes/footer.php'); ?>
<?php include('includes/p_footer.php'); ?>

<?php } ?>
