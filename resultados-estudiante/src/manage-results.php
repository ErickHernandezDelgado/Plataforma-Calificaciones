<?php 
/**
 * manage-results.php
 * Gestión y edición de calificaciones de estudiantes
 */
include(__DIR__ . '/includes/check-login.php');

// Verificación de rol: pantalla compartida admin + docente.
if (!in_array($_SESSION['role'] ?? '', ['admin', 'teacher'], true)) {
    header("Location: index.php");
    exit;
}

// Procesar actualización de calificaciones si se envía el formulario
$msg = "";
$error = "";

$teacherId = $_SESSION['teacherid'] ?? null;
$teacherRole = $_SESSION['role'] ?? null;

// Genera un token CSRF para proteger la actualización de calificaciones
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_POST['update_marks'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Solicitud no válida. Recarga la página e inténtalo de nuevo.";
    } else {
        $mark_ids = $_POST['mark_id'] ?? [];
        $mark_values = $_POST['mark_value'] ?? [];
        $fuera_rango = false;

        // Si es docente, solo puede actualizar notas de SUS materias.
        // Se prepara un verificador de propiedad por mark_id.
        $ownCheck = null;
        if ($teacherRole === 'teacher' && $teacherId) {
            $ownCheck = $dbh->prepare(
                "SELECT COUNT(*) FROM tblresult r
                 WHERE r.id = :id
                 AND r.SubjectId IN (SELECT SubjectId FROM tblteacher_subject WHERE TeacherId = :tid AND ClassId = r.ClassId)"
            );
        }

        try {
            $dbh->beginTransaction();

            foreach ($mark_ids as $idx => $mark_id) {
                $mark_id = intval($mark_id);
                $raw = $mark_values[$idx] ?? '';

                // Validación de rango: la calificación debe ser un entero 0-100
                if (!is_numeric($raw) || intval($raw) < 0 || intval($raw) > 100) {
                    $fuera_rango = true;
                    continue;
                }
                $mark_value = intval($raw);

                // Un docente solo edita notas de sus materias
                if ($ownCheck !== null) {
                    $ownCheck->execute([':id' => $mark_id, ':tid' => $teacherId]);
                    if ((int)$ownCheck->fetchColumn() === 0) {
                        continue; // No es suya: se omite
                    }
                }

                $sql = "UPDATE tblresult SET marks = :marks WHERE id = :id";
                $stmt = $dbh->prepare($sql);
                $stmt->execute([':marks' => $mark_value, ':id' => $mark_id]);
            }

            $dbh->commit();
            $msg = $fuera_rango
                ? "Calificaciones actualizadas. Algunas estaban fuera del rango 0-100 y no se guardaron."
                : "Calificaciones actualizadas correctamente.";
        } catch (PDOException $e) {
            if ($dbh->inTransaction()) $dbh->rollBack();
            $error = "Error al actualizar las calificaciones. Intenta de nuevo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IPT | Gestionar Resultados</title>
    <link rel="stylesheet" href="css/bootstrap.min.css" media="screen">
    <link rel="stylesheet" href="css/font-awesome.min.css" media="screen">
    <link rel="stylesheet" href="css/main.css" media="screen">
    <style>
        /* ====== GENERAL ====== */
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        * { box-sizing: border-box; }
        
        /* ====== FILTER PANEL ====== */
        .filter-panel { 
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f5f9 100%);
            padding: 30px 25px;
            border-radius: 12px; 
            margin-bottom: 30px; 
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        
        .filter-panel .form-group {
            margin-bottom: 15px;
        }
        
        .filter-panel label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        /* ====== FORM CONTROLS ====== */
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px; 
            border: 1px solid #e2e8f0;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: white;
            color: #1e293b;
        }
        
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            background-color: #ffffff;
            outline: none;
        }
        
        .form-control:disabled,
        .form-control[disabled] {
            background-color: #f1f5f9;
            color: #94a3b8;
        }
        
        /* ====== RESULT PANEL ====== */
        .result-panel { 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 30px 25px; 
            margin-top: 30px;
            overflow-x: auto;
        }
        
        .result-panel > div:first-child {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .result-panel h4 {
            margin: 0 0 8px 0;
            color: #1e293b;
            font-weight: 700;
            font-size: 18px;
        }
        
        .result-panel h5 {
            color: #334155;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 20px;
            margin-top: 25px;
            display: flex;
            align-items: center;
        }
        
        .result-panel h5 i {
            margin-right: 10px;
            color: #3b82f6;
        }
        
        .result-panel p {
            margin: 5px 0;
            color: #64748b;
            font-size: 14px;
        }
        
        /* ====== MARK ROWS ====== */
        .mark-row-period {
            background: #f8fafc;
            padding: 20px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3b82f6;
        }
        
        .mark-row-period h6 {
            color: #0f172a;
            font-weight: 700;
            margin: 0 0 20px 0;
            font-size: 14px;
        }
        
        .mark-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .mark-row:last-child {
            border-bottom: none;
        }
        
        .mark-row label {
            font-weight: 600;
            color: #334155;
            margin: 0;
            flex: 1 1 60%;
            min-width: 150px;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .mark-input {
            flex: 0 0 auto;
            min-width: 100px;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .mark-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        
        /* ====== BUTTONS ====== */
        .btn-update {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .btn-update:active {
            transform: translateY(0);
        }
        
        .btn-pdf {
            background: linear-gradient(135deg, #059669 0%, #059669 100%);
            color: black;
            border: solid 1px #059669;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            justify-content: center;
        }
        
        .btn-pdf:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
            background: linear-gradient(135deg, #0596684a 0%, #047857 100%);
        }
        
        .btn-pdf:active {
            transform: translateY(0);
        }
        
        .btn-pdf:disabled {
            background: #cbe1d7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* ====== ALERTS ====== */
        .alert-custom {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success.alert-custom {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-danger.alert-custom {
            background-color: #fee2e2;
            color: #7f1d1d;
            border-left: 4px solid #ef4444;
        }
        
        .alert-info {
            background-color: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
            border-radius: 8px;
            padding: 15px 20px;
            margin-top: 20px;
        }
        
        /* ====== TEXT UTILITIES ====== */
        .text-muted {
            color: #64748b;
            font-size: 14px;
        }
        
        .text-muted-custom {
            color: #94a3b8;
            font-size: 14px;
        }
        
        .label {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* ====== RESPONSIVE DESIGN ====== */
        @media (max-width: 768px) {
            .filter-panel {
                padding: 20px 15px;
            }
            
            .filter-panel .row > div {
                margin-bottom: 15px;
            }
            
            .result-panel {
                padding: 20px 15px;
            }
            
            .mark-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .mark-row label {
                flex: 1 1 100%;
            }
            
            .mark-input {
                width: 100%;
            }
            
            .btn-update {
                width: 100%;
                justify-content: center;
            }
            
            .result-panel h4 {
                font-size: 16px;
            }
            
            .result-panel h5 {
                font-size: 14px;
            }
        }
        
        @media (max-width: 480px) {
            .filter-panel {
                padding: 15px 10px;
            }
            
            .result-panel {
                padding: 15px 10px;
            }
            
            .mark-row-period {
                padding: 15px 15px;
            }
            
            .form-control {
                font-size: 16px; /* Prevent zoom on iOS */
            }
        }
    </style>
</head>
<body class="top-navbar-fixed">
    <div class="main-wrapper">
        <?php include('includes/topbar.php'); ?>
        <div class="content-wrapper">
            <div class="content-container">
                <?php 
                if ($teacherRole == 'teacher') {
                    include('includes/leftbar-teacher.php');
                } else {
                    include('includes/leftbar.php');
                }
                ?>

                <div class="main-page">
                    <div class="container-fluid">
                        <div class="row page-title-div">
                            <div class="col-md-12">
                                <h2 class="title">Gestionar Calificaciones de Estudiantes</h2>
                                <p class="text-muted" style="margin-top: 10px;">Selecciona un grupo y estudiante para ver y editar sus calificaciones</p>
                            </div>
                        </div>
                    </div>

                    <section class="section">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-md-10 col-md-offset-1">
                                    
                                    <?php if ($msg) { ?>
                                        <div class="alert alert-success alert-custom"><i class="fa fa-check-circle"></i> <?php echo htmlentities($msg); ?></div>
                                    <?php } else if ($error) { ?>
                                        <div class="alert alert-danger alert-custom"><i class="fa fa-times-circle"></i> <?php echo htmlentities($error); ?></div>
                                    <?php } ?>

                                    <!-- Panel de Filtros -->
                                    <div class="filter-panel">
                                        <form id="filterForm" method="post">
                                            <div class="row">
                                                <div class="col-xs-12 col-sm-6 col-md-6">
                                                    <div class="form-group">
                                                        <label for="classid"><strong>Grado y Grupo</strong></label>
                                                        <select id="classid" name="classid" class="form-control" required onChange="getStudents(this.value);">
                                                            <option value="">-- Selecciona un grupo --</option>
                                                            <?php
                                                            $sql = "SELECT id, ClassName, Section FROM tblclasses ORDER BY AcademicYear DESC, ClassName ASC";
                                                            $query = $dbh->prepare($sql);
                                                            $query->execute();
                                                            foreach ($query->fetchAll(PDO::FETCH_OBJ) as $class) { ?>
                                                                <option value="<?php echo $class->id; ?>">
                                                                    <?php echo htmlentities($class->ClassName . " (" . $class->Section . ")"); ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-xs-12 col-sm-6 col-md-6">
                                                    <div class="form-group">
                                                        <label for="studentid"><strong>Estudiante</strong></label>
                                                        <select id="studentid" name="studentid" class="form-control" required onChange="getStudentResults(this.value);">
                                                            <option value="">-- Selecciona un estudiante --</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-xs-12">
                                                    <button type="button" class="btn btn-pdf" onClick="generateGroupPDF();">
                                                        <i class="fa fa-file-pdf-o"></i> Generar PDF del Grupo
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Panel de Resultados -->
                                    <div id="resultsPanel"></div>

                                </div>
                            </div>
                        </div>
                    </section>
                    <?php include('includes/footer.php'); ?>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jquery/jquery-2.2.4.min.js"></script>
    <script src="js/bootstrap/bootstrap.min.js"></script>
    <script>
        
        function getStudents(classid) {
            if (classid === '') {
                $('#studentid').html('<option value="">-- Selecciona un estudiante --</option>');
                $('#resultsPanel').html('');
                return;
            }
            
            $.post("get_student.php?lang=es", {classid: classid}, function(data) {
                $('#studentid').html(data);
                $('#resultsPanel').html('');
            });
        }

        function getStudentResults(studentid) {
            if (studentid === '') {
                $('#resultsPanel').html('');
                return;
            }
            
            var classid = $('#classid').val();
            if (classid === '') {
                alert('Por favor selecciona un grupo primero');
                return;
            }

            $.post("get_student_results.php?lang=es", {
                studentid: studentid,
                classid: classid
            }, function(data) {
                $('#resultsPanel').html(data);
            });
        }

        function generateGroupPDF() {
            var classid = $('#classid').val();
            
            if (!classid || classid === '') {
                alert('Por favor selecciona un grupo primero');
                return;
            }
            
            // Abrir el generador de PDF en nueva ventana
            var pdfUrl = 'generate-group-grades.php?classid=' + classid + '&lang=es';
            window.open(pdfUrl, '_blank');
        }
    </script>
</body>
</html>