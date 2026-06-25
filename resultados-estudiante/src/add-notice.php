<?php
// Inicia la sesión
session_start();

// Oculta reportes de errores (no recomendado en producción sin control)
error_reporting(0);

// Incluye el archivo de configuración con la conexión a la base de datos
include(__DIR__ . '/includes/config.php');

// Verifica que el usuario haya iniciado sesión y que su rol sea 'admin'
if (!isset($_SESSION['alogin']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
} else {
    $error = "";

    // Genera un token CSRF para proteger el formulario
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Si el formulario ha sido enviado
    if (isset($_POST['submit'])) {
        // Obtiene y sanea los datos del formulario
        $ntitle = trim($_POST['noticetitle'] ?? '');
        $ndetails = trim($_POST['noticedetails'] ?? '');
        $audience_type = $_POST['audience_type'] ?? '';
        $class_id = (isset($_POST['class_id']) && $_POST['class_id'] != '') ? intval($_POST['class_id']) : NULL;
        $selected_students = (isset($_POST['selected_students']) && is_array($_POST['selected_students'])) ? $_POST['selected_students'] : [];

        // Validaciones del lado servidor
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $error = "Solicitud no válida. Recarga la página e inténtalo de nuevo.";
        } elseif ($ntitle === '' || $ndetails === '') {
            $error = "El título y la información del comunicado son obligatorios.";
        } elseif (!in_array($audience_type, ['all', 'class', 'selected'], true)) {
            $error = "Selecciona una audiencia válida.";
        } elseif ($audience_type === 'class' && !$class_id) {
            $error = "Selecciona la clase destinataria.";
        } elseif ($audience_type === 'selected' && empty($selected_students)) {
            $error = "Selecciona al menos un estudiante.";
        } else {
            // Obtener ID del administrador actual (para registro de creación)
            $admin_id = NULL;
            $sql_admin = "SELECT id FROM admin WHERE UserName = :username";
            $query_admin = $dbh->prepare($sql_admin);
            $query_admin->bindParam(':username', $_SESSION['alogin'], PDO::PARAM_STR);
            $query_admin->execute();
            $admin_result = $query_admin->fetch(PDO::FETCH_ASSOC);
            if ($admin_result) {
                $admin_id = $admin_result['id'];
            }

            try {
                // Comunicado + destinatarios se insertan de forma atómica
                $dbh->beginTransaction();

                $sql = "INSERT INTO tblnotice(noticeTitle, noticeDetails, audience_type, class_id, created_by, is_active)
                        VALUES(:ntitle, :ndetails, :audience_type, :class_id, :created_by, 1)";
                $query = $dbh->prepare($sql);
                $query->bindParam(':ntitle', $ntitle, PDO::PARAM_STR);
                $query->bindParam(':ndetails', $ndetails, PDO::PARAM_STR);
                $query->bindParam(':audience_type', $audience_type, PDO::PARAM_STR);
                $query->bindParam(':class_id', $class_id, $class_id === NULL ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $query->bindParam(':created_by', $admin_id, PDO::PARAM_INT);
                $query->execute();

                $lastInsertId = $dbh->lastInsertId();

                // Determinar destinatarios según la audiencia
                $students_to_notify = [];
                if ($audience_type == 'all') {
                    $sql_students = "SELECT StudentId FROM tblstudents WHERE Status = 1";
                    $query_students = $dbh->prepare($sql_students);
                    $query_students->execute();
                    $students_to_notify = $query_students->fetchAll(PDO::FETCH_COLUMN);
                } elseif ($audience_type == 'class' && $class_id) {
                    $sql_students = "SELECT StudentId FROM tblstudents WHERE ClassId = :class_id AND Status = 1";
                    $query_students = $dbh->prepare($sql_students);
                    $query_students->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                    $query_students->execute();
                    $students_to_notify = $query_students->fetchAll(PDO::FETCH_COLUMN);
                } elseif ($audience_type == 'selected' && !empty($selected_students)) {
                    // Valida que los IDs seleccionados sean alumnos reales y activos
                    $ids = array_map('intval', $selected_students);
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $sql_val = "SELECT StudentId FROM tblstudents WHERE Status = 1 AND StudentId IN ($placeholders)";
                    $query_val = $dbh->prepare($sql_val);
                    $query_val->execute($ids);
                    $students_to_notify = $query_val->fetchAll(PDO::FETCH_COLUMN);
                }

                // Insertar en notice_student para cada estudiante válido
                if (!empty($students_to_notify)) {
                    $sql_ns = "INSERT INTO notice_student(notice_id, student_id, is_viewed, created_date)
                               VALUES(:notice_id, :student_id, 0, NOW())";
                    $query_ns = $dbh->prepare($sql_ns);
                    foreach ($students_to_notify as $student_id) {
                        $query_ns->bindValue(':notice_id', $lastInsertId, PDO::PARAM_INT);
                        $query_ns->bindValue(':student_id', (int)$student_id, PDO::PARAM_INT);
                        $query_ns->execute();
                    }
                }

                $dbh->commit();

                echo '<script>alert("Comunicado agregado correctamente")</script>';
                echo "<script>window.location.href ='manage-notices.php'</script>";
            } catch (PDOException $e) {
                if ($dbh->inTransaction()) $dbh->rollBack();
                $error = "No se pudo agregar el comunicado. Intenta de nuevo.";
            }
        }
    }
?>

<!-- ========== BARRA SUPERIOR ========== -->
<?php include('includes/topbar.php'); ?>

<!-- Contenedor principal del contenido -->
<div class="content-wrapper">
    <div class="content-container">

        <!-- Barra lateral izquierda -->
        <?php include('includes/leftbar.php'); ?>

        <!-- Página principal -->
        <div class="main-page">
            <div class="container-fluid">
                <!-- Título de la página -->
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Agregar Comunicado</h2>
                    </div>
                </div>

                <!-- Ruta de navegación (breadcrumb) -->
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li><a href="#">Comunicado</a></li>
                            <li class="active">Agregar Comunicado</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sección principal -->
            <section class="section">
                <div class="container-fluid">

                    <!-- Formulario centrado -->
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Agregar Comunicado</h5>
                                    </div>
                                </div>

                                <div class="panel-body">
                                    <?php if (!empty($error)) { ?>
                                        <div class="alert alert-danger"><strong>Error:</strong> <?php echo htmlentities($error); ?></div>
                                    <?php } ?>
                                    <!-- Formulario para ingresar el comunicado -->
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">
                                        <!-- Campo para el título -->
                                        <div class="form-group has-success">
                                            <label for="success" class="control-label">Título de Comunicado</label>
                                            <div class="">
                                                <input type="text" name="noticetitle" class="form-control" required="required" id="noticetitle">
                                            </div>
                                        </div>

                                        <!-- Campo para el contenido del comunicado -->
                                        <div class="form-group has-success">
                                            <label for="success" class="control-label">Información de Comunicado</label>
                                            <div class="">
                                                <textarea class="form-control" name="noticedetails" required rows="5"></textarea>
                                            </div>
                                        </div>

                                        <!-- Campo para seleccionar el tipo de audiencia -->
                                        <div class="form-group has-success">
                                            <label class="control-label">¿A quién va dirigido este comunicado?</label>
                                            <div class="">
                                                <select name="audience_type" id="audience_type" class="form-control" required onchange="updateAudienceOptions()">
                                                    <option value="">Selecciona una opción</option>
                                                    <option value="all">A todos los estudiantes</option>
                                                    <option value="class">A una clase completa</option>
                                                    <option value="selected">A estudiantes específicos</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Campo para seleccionar clase (solo se muestra si se selecciona "class") -->
                                        <div class="form-group has-success" id="class_selection" style="display:none;">
                                            <label class="control-label">Selecciona la clase</label>
                                            <div class="">
                                                <select name="class_id" id="class_id" class="form-control">
                                                    <option value="">Selecciona una clase</option>
                                                    <?php
                                                    // Obtener todas las clases ordenadas
                                                    $sql_classes = "SELECT id, ClassName, Section FROM tblclasses ORDER BY ClassNameNumeric ASC, Section ASC";
                                                    $query_classes = $dbh->prepare($sql_classes);
                                                    $query_classes->execute();
                                                    $classes = $query_classes->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach ($classes as $class) {
                                                        echo '<option value="' . htmlentities($class['id']) . '">' . htmlentities($class['ClassName']) . ' - Sección ' . htmlentities($class['Section']) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Campo para seleccionar estudiantes específicos (solo se muestra si se selecciona "selected") -->
                                        <div class="form-group has-success" id="students_selection" style="display:none;">
                                            <label class="control-label">Selecciona los estudiantes</label>
                                            <div class="">
                                                <div style="border: 1px solid #ddd; padding: 10px; height: 250px; overflow-y: auto; background-color: #f9f9f9;">
                                                    <?php
                                                    // Obtener todos los estudiantes organizados por clase
                                                    $sql_students = "SELECT ts.StudentId, ts.StudentName, tc.ClassName, tc.Section 
                                                                    FROM tblstudents ts
                                                                    JOIN tblclasses tc ON ts.ClassId = tc.id
                                                                    WHERE ts.Status = 1
                                                                    ORDER BY tc.ClassNameNumeric ASC, tc.Section ASC, ts.StudentName ASC";
                                                    $query_students = $dbh->prepare($sql_students);
                                                    $query_students->execute();
                                                    $students = $query_students->fetchAll(PDO::FETCH_ASSOC);
                                                    
                                                    $current_class = '';
                                                    foreach ($students as $student) {
                                                        // Mostrar encabezado de clase si cambió
                                                        if ($current_class != $student['ClassName'] . ' - ' . $student['Section']) {
                                                            $current_class = $student['ClassName'] . ' - ' . $student['Section'];
                                                            echo '<div style="background-color: #e8f4f8; padding: 8px; margin-top: 10px; font-weight: bold; border-radius: 3px;">' . htmlentities($current_class) . '</div>';
                                                        }
                                                        echo '<div style="padding: 5px; margin-left: 10px;">';
                                                        echo '<label style="font-weight: normal; margin: 0;">';
                                                        echo '<input type="checkbox" name="selected_students[]" value="' . htmlentities($student['StudentId']) . '"> ';
                                                        echo htmlentities($student['StudentName']);
                                                        echo '</label></div>';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Botón para enviar el formulario -->
                                        <div class="form-group has-success">
                                            <div class="">
                                                <button type="submit" name="submit" class="btn btn-success">Enviar</button>
                                            </div>
                                        </div>
                                    </form>
                                    <!-- Fin del formulario -->
                                </div>
                            </div>
                        </div>
                        <!-- /.col-md-8 col-md-offset-2 -->
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

<!-- Pie de página -->
<?php include('includes/footer.php'); ?>

<!-- Script JavaScript para controlar la visibilidad de campos -->
<script>
    // Función para actualizar las opciones visibles según el tipo de audiencia seleccionado
    function updateAudienceOptions() {
        var audienceType = document.getElementById('audience_type').value;
        var classSelection = document.getElementById('class_selection');
        var studentsSelection = document.getElementById('students_selection');

        // Ocultar todos los campos inicialmente
        classSelection.style.display = 'none';
        studentsSelection.style.display = 'none';

        // Mostrar el campo correspondiente según la selección
        if (audienceType === 'class') {
            classSelection.style.display = 'block';
            // Hacer el selector de clase requerido
            document.getElementById('class_id').required = true;
        } else if (audienceType === 'selected') {
            studentsSelection.style.display = 'block';
        } else if (audienceType === 'all') {
            // Sin campos adicionales requeridos
            document.getElementById('class_id').required = false;
        }
    }

    // Inicializar la visibilidad al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        updateAudienceOptions();
    });
</script>

<?php } ?>

