<?php
// Inicia la sesión
session_start();

// Desactiva los reportes de error (útil en producción, pero no recomendado para desarrollo)
error_reporting(0);

// Incluye la configuración de la base de datos
include(__DIR__ . '/includes/config.php');

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
        $trimestre = $_POST['trimestre'] ?? null; // NUEVO: Período (trimestre o bimestre)
        
        // VALIDACIÓN 1: Verificar que se seleccionó un período
        if (empty($trimestre)) {
            $error = "Por favor selecciona un período (Trimestre/Bimestre).";
        } else {
            // VALIDACIÓN 2: Obtener educationLevel del grupo para validar período
            $sql_level = "SELECT educationLevel FROM tblclasses WHERE id = :cid";
            $query_level = $dbh->prepare($sql_level);
            $query_level->execute([':cid' => $class]);
            $class_data = $query_level->fetch(PDO::FETCH_OBJ);
            
            if (!$class_data) {
                $error = "El grupo seleccionado no existe.";
            } else {
                // Validar que el período corresponde al nivel educativo
                $valid_periods = [];
                if ($class_data->educationLevel == 'infantil') {
                    $valid_periods = ['Bimestre 1', 'Bimestre 2', 'Bimestre 3', 'Bimestre 4', 'Bimestre 5'];
                } else {
                    // primaria y secundaria
                    $valid_periods = ['Trimestre 1', 'Trimestre 2', 'Trimestre 3'];
                }
                
                if (!in_array($trimestre, $valid_periods)) {
                    $error = "El período seleccionado no es válido para este nivel educativo.";
                } else {
                    // Consulta las materias correspondientes a la clase seleccionada
                    $stmt = $dbh->prepare("SELECT tblsubjects.SubjectName, tblsubjects.id 
                                           FROM tblsubjectcombination 
                                           JOIN tblsubjects ON tblsubjects.id = tblsubjectcombination.SubjectId 
                                           WHERE tblsubjectcombination.ClassId = :cid AND tblsubjectcombination.status = 1
                                           ORDER BY tblsubjects.SubjectName");
                    $stmt->execute(array(':cid' => $class));

                    // Almacena los IDs de las materias en el arreglo $sid1
                    $sid1 = array();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        array_push($sid1, $row['id']);
                    }
                    
                    // VALIDACIÓN 3: Verificar que la materia esté asignada al profesor (si hay FK)
                    // Esta validación se realiza en get_student.php cuando se muestran las materias
                    
                    if (empty($sid1)) {
                        $error = "No hay materias asignadas a este grupo. "
                               . "Asigna materias en 'Gestionar Materias' primero.";
                    } else {
                        // Inserta los resultados en la tabla tblresult por cada materia
                        $insert_success = true;
                        for ($i = 0; $i < count($mark); $i++) {
                            if (!empty($mark[$i])) { // Solo insertar si hay calificación
                                $mar = $mark[$i]; // calificación
                                $sid = $sid1[$i]; // ID de la materia correspondiente
                                
                                // SQL mejorado: rechazar si hay un resultado duplicado en el mismo período
                                $sql = "INSERT INTO tblresult(StudentId, ClassId, SubjectId, marks, Trimestre) 
                                        VALUES(:studentid, :class, :sid, :marks, :trimestre)";
                                $query = $dbh->prepare($sql);
                                $query->bindParam(':studentid', $studentid, PDO::PARAM_STR);
                                $query->bindParam(':class', $class, PDO::PARAM_STR);
                                $query->bindParam(':sid', $sid, PDO::PARAM_STR);
                                $query->bindParam(':marks', $mar, PDO::PARAM_STR);
                                $query->bindParam(':trimestre', $trimestre, PDO::PARAM_STR);
                                
                                if (!$query->execute()) {
                                    $insert_success = false;
                                    $error = "Error al guardar calificación de materia ID {$sid}. "
                                           . "Es posible que ya existe un resultado para esta materia en este período.";
                                    break;
                                }
                            }
                        }
                        
                        if ($insert_success) {
                            $msg = "Resultados agregados correctamente para el $trimestre.";
                        }
                    }
                }
            }
        }
    }
?>

<!-- Script AJAX para obtener estudiantes y materias por clase, y actualizar períodos -->
<script>
    function getStudent(val) {
        // Obtener el educationLevel del option seleccionado
        var selectedOption = document.querySelector('#classid option:checked');
        var educationLevel = selectedOption.getAttribute('data-level');
        
        // Actualizar selector de trimestre/bimestre
        var trimestreSelect = document.getElementById('trimestre');
        trimestreSelect.innerHTML = '';
        
        if (educationLevel === 'infantil') {
            var options = ['Bimestre 1', 'Bimestre 2', 'Bimestre 3', 'Bimestre 4', 'Bimestre 5'];
        } else {
            // primaria y secundaria usan trimestres
            var options = ['Trimestre 1', 'Trimestre 2', 'Trimestre 3'];
        }
        
        // Agregar opción vacía
        var emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = 'Seleccionar Período';
        trimestreSelect.appendChild(emptyOption);
        
        // Agregar opciones
        options.forEach(function(period) {
            var option = document.createElement('option');
            option.value = period;
            option.textContent = period;
            trimestreSelect.appendChild(option);
        });
        
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
                                            <label for="default" class="control-label">Año/Grupo</label>
                                            <select name="class" class="form-control clid" id="classid" onChange="getStudent(this.value);" required>
                                                <option value="">Seleccionar Año/Grupo</option>
                                                <?php
                                                $sql = "SELECT id, ClassName, Section, educationLevel FROM tblclasses ORDER BY AcademicYear DESC, ClassName ASC";
                                                $query = $dbh->prepare($sql);
                                                $query->execute();
                                                $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                if ($query->rowCount() > 0) {
                                                    foreach ($results as $result) {
                                                ?>
                                                        <option value="<?php echo htmlentities($result->id); ?>" data-level="<?php echo htmlentities($result->educationLevel); ?>">
                                                            <?php echo htmlentities($result->ClassName); ?>&nbsp;(<?php echo htmlentities($result->Section); ?>)&nbsp;-&nbsp;<?php echo htmlentities(ucfirst($result->educationLevel)); ?>
                                                        </option>
                                                <?php }
                                                } ?>
                                            </select>
                                        </div>

                                        <!-- Selección de período (NUEVO: dinámico según educationLevel) -->
                                        <div class="form-group">
                                            <label for="trimestre" class="control-label">Período (Trimestre/Bimestre)</label>
                                            <select name="trimestre" id="trimestre" class="form-control" required>
                                                <option value="">Selecciona primero un grupo</option>
                                            </select>
                                            <small class="form-text text-muted">El período se ajusta automáticamente según el nivel educativo.</small>
                                        </div>

                                        <!-- Selección de estudiante -->
                                        <div class="form-group">
                                            <label for="date" class="control-label">Nombre del Estudiante</label>
                                            <select name="studentid" class="form-control stid" id="studentid" required onChange="getresult(this.value);">
                                                <option value="">Selecciona un grupo primero</option>
                                            </select>
                                        </div>

                                        <!-- Resultados existentes -->
                                        <div class="form-group">
                                            <div id="reslt"></div>
                                        </div>

                                        <!-- Materias disponibles -->
                                        <div class="form-group">
                                            <label for="date" class="control-label">Materias y Calificaciones</label>
                                            <div id="subject"></div>
                                        </div>

                                        <!-- Botón para enviar -->
                                        <div class="form-group">
                                            <button type="submit" name="submit" id="submit" class="btn btn-success">Guardar Resultados</button>
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

