<?php
/**
 * add-result.php
 * Sistema de Gestión de Calificaciones IPT
 * Registro de resultados por materia y periodo (Normalizado)
 */

session_start();
error_reporting(E_ALL); 
ini_set('display_errors', 1);

include(__DIR__ . '/includes/config.php');

// Verificación de Sesión
if (!isset($_SESSION['alogin']) || strlen($_SESSION['alogin']) == 0) {
    header("Location: index.php");
    exit;
}

$msg = "";
$error = "";

// PROCESAMIENTO DEL FORMULARIO
if (isset($_POST['submit'])) {
    $class = intval($_POST['class']);
    $studentid = intval($_POST['studentid']);
    $mark = $_POST['marks'] ?? []; // Array de calificaciones
    $periodo_data = $_POST['periodo_data'] ?? null; // Recibe formato "period_type|term_number"

    if (empty($periodo_data)) {
        $error = "Por favor selecciona un trimestre/bimestre.";
    } elseif (empty($mark)) {
        $error = "No hay materias asignadas a este grupo.";
    } else {
        // Parsear del formato "1|1" (Bimestre 1) o "2|3" (Trimestre 3)
        $periodo_parts = explode("|", $periodo_data);
        $period_type = intval($periodo_parts[0] ?? 0);  // 1=Bimestre, 2=Trimestre
        $term_number = intval($periodo_parts[1] ?? 0);  // 1-5 o 1-3

        if ($period_type == 0 || $term_number == 0) {
            $error = "Período inválido.";
        } else {
            // 1. Obtener materias asignadas a la clase (filtradas por ESPAÑOL)
            $stmt = $dbh->prepare("SELECT id 
                                   FROM tblsubjects 
                                   WHERE id IN (
                                       SELECT SubjectId FROM tblsubjectcombination 
                                       WHERE ClassId = :cid AND status = 1
                                   )
                                   AND Language = :lang
                                   ORDER BY SubjectName");
            $stmt->execute([':cid' => $class, ':lang' => 'es']);
            $subjectIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($subjectIds)) {
                $error = "No hay materias asignadas a este grupo.";
            } else {
                $dbh->beginTransaction();

                try {
                    for ($i = 0; $i < count($mark); $i++) {
                        if ($mark[$i] !== "" && isset($subjectIds[$i])) { 
                            $val = intval($mark[$i]);  // marks es INT en la BD
                            $sid = intval($subjectIds[$i]);

                            // Insertar con estructura correcta: StudentId, ClassId, SubjectId, marks, term
                            $sql = "INSERT INTO tblresult(StudentId, ClassId, SubjectId, marks, Trimestre, term, PostingDate) 
                                    VALUES(:studentid, :classid, :subjectid, :marks, :trimestre, :term, NOW())";
                            $query = $dbh->prepare($sql);
                            
                            // Construir el texto Trimestre/Bimestre
                            $trimestre_text = ($period_type == 1) ? "Bimestre " . $term_number : "Trimestre " . $term_number;
                            
                            $query->execute([
                                ':studentid' => $studentid,
                                ':classid' => $class,
                                ':subjectid' => $sid,
                                ':marks' => $val,
                                ':trimestre' => $trimestre_text,
                                ':term' => $term_number
                            ]);
                        }
                    }
                    $dbh->commit();
                    $msg = "✅ Resultados guardados correctamente.";
                } catch (Exception $e) {
                    $dbh->rollBack();
                    $error = "❌ Error al guardar: " . $e->getMessage();
                }
            }
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
    <title>IPT | Agregar Resultado - <?php echo $lang === 'en' ? 'English' : 'Español'; ?></title>
    <link rel="stylesheet" href="css/bootstrap.min.css" media="screen">
    <link rel="stylesheet" href="css/font-awesome.min.css" media="screen">
    <link rel="stylesheet" href="css/main.css" media="screen">
    
    <style>
        /* ====== GENERAL ====== */
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        
        /* ====== MAIN CARD ====== */
        .main-card { 
            background: #fff; 
            border-radius: 12px; 
            box-shadow: 0 5px 20px rgba(0,0,0,0.08); 
            border: 1px solid #e2e8f0;
            margin-bottom: 30px; 
            overflow: hidden;
        }
        
        .card-header-custom { 
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f5f9 100%);
            border-bottom: 1px solid #e2e8f0;
            padding: 25px; 
            border-radius: 12px 12px 0 0; 
        }
        
        .card-title { 
            color: #1e293b; 
            font-weight: 700; 
            margin: 0; 
            display: flex; 
            align-items: center;
            font-size: 18px;
        }
        
        .card-title i { 
            margin-right: 12px; 
            color: #3b82f6;
            font-size: 20px;
        }
        
        /* ====== FORM SECTIONS ====== */
        .form-section { 
            padding: 25px; 
            border-bottom: 1px solid #f1f5f9; 
        }
        
        .form-section:last-of-type {
            border-bottom: none;
        }
        
        .form-section-title { 
            font-size: 12px; 
            font-weight: 800; 
            color: #64748b; 
            text-transform: uppercase; 
            letter-spacing: 1.2px; 
            margin-bottom: 20px; 
            display: block;
            margin-top: 0;
        }
        
        /* ====== FORM CONTROLS ====== */
        .form-control { 
            border-radius: 8px; 
            border: 1px solid #e2e8f0; 
            padding: 12px 15px; 
            height: auto; 
            transition: all 0.2s ease;
            font-size: 14px;
            width: 100%;
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
        
        label { 
            font-weight: 600; 
            color: #334155; 
            margin-bottom: 10px;
            display: block;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        /* ====== SUBJECT CONTAINER ====== */
        .subject-container { 
            background: #f8fafc; 
            padding: 20px; 
            border-radius: 10px; 
            border: 1px dashed #cbd5e1;
            line-height: 1.6;
        }
        
        .subject-container .row {
            margin-bottom: 12px;
        }
        
        .subject-container .row:last-child {
            margin-bottom: 0;
        }
        
        .subject-container p {
            margin-top: 7px;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }
        
        .subject-container input {
            width: 100%;
        }
        
        /* ====== ALERTS ====== */
        .alert-modern { 
            border-radius: 10px; 
            border: none; 
            padding: 15px 20px; 
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success.alert-modern {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-danger.alert-modern {
            background-color: #fee2e2;
            color: #7f1d1d;
            border-left: 4px solid #ef4444;
        }
        
        /* ====== BUTTONS ====== */
        .btn-save { 
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white; 
            border: none; 
            padding: 14px 30px; 
            border-radius: 8px; 
            font-weight: 700; 
            transition: all 0.2s ease;
            width: 100%; 
            text-transform: uppercase;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-save:hover { 
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px); 
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); 
        }
        
        .btn-save:active {
            transform: translateY(0);
        }
        
        .btn-save:disabled { 
            background: #cbd5e1; 
            cursor: not-allowed; 
            transform: none; 
            box-shadow: none; 
        }
        
        /* ====== RESPONSIVE ====== */
        @media (max-width: 768px) {
            .form-section {
                padding: 20px 15px;
            }
            
            .card-header-custom {
                padding: 20px 15px;
            }
            
            .card-title {
                font-size: 16px;
            }
            
            .subject-container {
                padding: 15px;
            }
            
            .form-section .row > div {
                margin-bottom: 15px;
            }
        }
        
        @media (max-width: 480px) {
            .form-section {
                padding: 15px 10px;
            }
            
            .card-header-custom {
                padding: 15px 10px;
            }
            
            .form-control {
                font-size: 16px; /* Prevent zoom on iOS */
            }
            
            .btn-save {
                padding: 12px 20px;
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
                if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'teacher') {
                    include('includes/leftbar-teacher.php');
                } else {
                    include('includes/leftbar.php');
                }
                ?>

                <div class="main-page">
                    <div class="container-fluid">
                        <div class="row page-title-div">
                            <div class="col-md-12">
                                <h2 class="title">Carga de Calificaciones</h2>
                                <p class="text-muted">Ingresa los resultados académicos por materia y período</p>
                            </div>
                        </div>

                        <section class="section">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-md-10 col-md-offset-1">
                                        
                                        <?php if ($msg) { ?>
                                            <div class="alert alert-success alert-modern"><i class="fa fa-check-circle"></i> <?php echo htmlentities($msg); ?></div>
                                        <?php } else if ($error) { ?>
                                            <div class="alert alert-danger alert-modern"><i class="fa fa-times-circle"></i> <?php echo htmlentities($error); ?></div>
                                        <?php } ?>

                                        <div class="main-card">
                                            <div class="card-header-custom">
                                                <h4 class="card-title"><i class="fa fa-edit"></i> Panel de Evaluación</h4>
                                            </div>

                                            <form method="post">
                                                <div class="form-section">
                                                    <span class="form-section-title">1. Contexto Académico</span>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Grado y Grupo</label>
                                                                <select name="class" class="form-control clid" id="classid" onChange="getPeriodos(this.value);" required>
                                                                    <option value="">Seleccionar...</option>
                                                                    <?php
                                                                    $sql = "SELECT id, ClassName, Section, educationLevel FROM tblclasses ORDER BY AcademicYear DESC, ClassName ASC";
                                                                    $query = $dbh->prepare($sql);
                                                                    $query->execute();
                                                                    foreach ($query->fetchAll(PDO::FETCH_OBJ) as $result) { ?>
                                                                        <option value="<?php echo $result->id; ?>" data-level="<?php echo $result->educationLevel; ?>">
                                                                            <?php echo htmlentities($result->ClassName . " (" . $result->Section . ") - " . ucfirst($result->educationLevel)); ?>
                                                                        </option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Período Evaluativo</label>
                                                                <select name="periodo_data" id="periodo_data" class="form-control" required>
                                                                    <option value="">Selecciona un grupo primero</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-section">
                                                    <span class="form-section-title">2. Estudiante</span>
                                                    <div class="form-group">
                                                        <label>Nombre del Alumno</label>
                                                        <select name="studentid" class="form-control stid" id="studentid" required onChange="getresult(this.value);">
                                                            <option value="">Esperando grupo...</option>
                                                        </select>
                                                    </div>
                                                    <div id="reslt"></div>
                                                </div>

                                                <div class="form-section" style="border-bottom: none;">
                                                    <span class="form-section-title">3. Registro de Materias</span>
                                                    <div id="subject" class="subject-container">
                                                        <p class="text-center text-muted m-0">Selecciona un grupo para cargar la carga académica.</p>
                                                    </div>
                                                </div>

                                                <div class="p-25">
                                                    <div class="row">
                                                        <div class="col-md-4 col-md-offset-4">
                                                            <button type="submit" name="submit" id="submit" class="btn-save">
                                                                <i class="fa fa-save"></i> Guardar Calificaciones
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jquery/jquery-2.2.4.min.js"></script>
    <script src="js/bootstrap/bootstrap.min.js"></script>
    <script>
    
    function getPeriodos(val) {
        // Obtenemos el nivel desde el atributo data-level del option seleccionado
        var level = $('#classid option:selected').data('level');
        var $t = $('#periodo_data').empty().append('<option value="">Seleccionar Período</option>');
        
        // Generamos opciones según el nivel educativo
        // 1|X para Bimestre, 2|X para Trimestre
        if(level === 'infantil') {
            for(var i=1; i<=5; i++) $t.append(`<option value="1|${i}">Bimestre ${i}</option>`);
        } else {
            for(var i=1; i<=3; i++) $t.append(`<option value="2|${i}">Trimestre ${i}</option>`);
        }

        // Carga de la lista de estudiantes
        $.post("get_student.php?lang=es", {classid: val}, function(data) {
            $("#studentid").html(data);
        });

        // Carga de los inputs de materias
        $.post("get_student.php?lang=es", {classid1: val}, function(data) {
            $("#subject").html(data);
        });
    }

    function getresult(val) {
        var cid = $("#classid").val();
        var periodo = $("#periodo_data").val(); 

        if (periodo === "") {
            alert("Por favor, selecciona primero un período evaluativo.");
            $("#studentid").val(""); 
            return;
        }

        // Construir datos para validar duplicados: ClassId $ StudentId $ term
        var periodoParts = periodo.split('|');
        var term_number = periodoParts[1];  // Solo necesitamos el número del término
        var fullData = cid + '$' + val + '$' + term_number;

        $.post("get_student.php?lang=es", {
            studclass: fullData
        }, function(data) {
            $("#reslt").html(data);
            
            // Si el mensaje indica que ya existen resultados, deshabilitamos el botón
            if(data.toLowerCase().indexOf("ya cuenta con resultados") !== -1) {
                $("#submit").attr("disabled", true);
            } else {
                $("#submit").attr("disabled", false);
            }
        });
    }

    // Resetear validación si cambian el período después de elegir alumno
    $('#periodo_data').on('change', function() {
        var studentSelected = $("#studentid").val();
        if (studentSelected && studentSelected !== "") {
            getresult(studentSelected);
        }
    });
    </script>
</body>
</html>
<?php include('includes/footer.php'); ?>
<?php include('includes/p_footer.php'); ?>