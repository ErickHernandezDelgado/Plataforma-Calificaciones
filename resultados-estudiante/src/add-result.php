<?php
/**
 * add-result.php
 * Sistema de Gestión de Calificaciones IPT
 * Registro de resultados por materia y periodo (Normalizado)
 */

include(__DIR__ . '/includes/check-login.php');
require_once(__DIR__ . '/includes/result-audit.php'); // P2: auto-cierre del periodo al guardar

// Verificación de Sesión y rol.
// Esta pantalla es compartida por admin y docentes; ambos pueden capturar notas.
if (!isset($_SESSION['alogin']) || strlen($_SESSION['alogin']) == 0
    || !in_array($_SESSION['role'] ?? '', ['admin', 'teacher'], true)) {
    header("Location: index.php");
    exit;
}

$msg = "";
$error = "";

// Genera un token CSRF para proteger el guardado de calificaciones
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// PROCESAMIENTO DEL FORMULARIO — CAPTURA POR GRUPO COMPLETO (P1).
// Los arrays vienen indexados por [studentId][subjectId]: marks[sid][subj] (número)
// y letters[sid][subj] (letra). Reglas: exigir TODAS las celdas llenas, SOBREESCRIBIR
// las existentes (auditando el cambio), y AUTO-CERRAR el grupo+periodo si es docente.
if (isset($_POST['submit'])) {
    $class        = intval($_POST['class'] ?? 0);
    $marksIn      = $_POST['marks']   ?? []; // [studentId][subjectId] => nota numérica
    $lettersIn    = $_POST['letters'] ?? []; // [studentId][subjectId] => letra
    $periodo_data = $_POST['periodo_data'] ?? null; // "period_type|term_number"

    $session_role      = $_SESSION['role']      ?? null;
    $session_teacherid = $_SESSION['teacherid'] ?? null;

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Solicitud no válida. Recarga la página e inténtalo de nuevo.";
    } elseif ($class <= 0) {
        $error = "Selecciona un grupo.";
    } elseif (empty($periodo_data)) {
        $error = "Por favor selecciona un período.";
    } elseif (empty($marksIn) && empty($lettersIn)) {
        $error = "No se recibió ninguna calificación. Carga el grupo primero.";
    } else {
        $periodo_parts = explode("|", $periodo_data);
        $period_type = intval($periodo_parts[0] ?? 0);
        $term_number = intval($periodo_parts[1] ?? 0);

        if ($period_type == 0 || $term_number == 0) {
            $error = "Período inválido.";
        } else {
            $trimestre_text = ($period_type == 1) ? "Bimestre {$term_number}" : "Trimestre {$term_number}";

            // Nivel del grupo (para la escala de letras en maternal).
            $lvlStmt = $dbh->prepare("SELECT educationLevel FROM tblclasses WHERE id = :cid");
            $lvlStmt->execute([':cid' => $class]);
            $grupoLevel = $lvlStmt->fetchColumn();
            $isMaternal = ($grupoLevel === 'maternal');

            // Materias VÁLIDAS del grupo (del docente si teacher; todas si admin), en ES.
            // Sirve para: (a) validar SubjectId recibidos, (b) saber cuántas celdas se exigen.
            if ($session_role === 'teacher' && $session_teacherid) {
                $stmt = $dbh->prepare(
                    "SELECT id, subject_type FROM tblsubjects
                     WHERE id IN (SELECT SubjectId FROM tblsubjectcombination WHERE ClassId = :cid AND status = 1)
                       AND id IN (SELECT SubjectId FROM tblteacher_subject WHERE TeacherId = :tid AND ClassId = :cid)
                       AND Language = 'es' ORDER BY id"
                );
                $stmt->execute([':cid' => $class, ':tid' => $session_teacherid]);
            } else {
                $stmt = $dbh->prepare(
                    "SELECT id, subject_type FROM tblsubjects
                     WHERE id IN (SELECT SubjectId FROM tblsubjectcombination WHERE ClassId = :cid AND status = 1)
                       AND Language = 'es' ORDER BY id"
                );
                $stmt->execute([':cid' => $class]);
            }
            $subjectRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $subjectType = [];                    // subjectId => tipo
            foreach ($subjectRows as $sr) { $subjectType[(int)$sr['id']] = $sr['subject_type'] ?? 'normal'; }
            $validSubjects = array_keys($subjectType);

            // Alumnos activos del grupo (las filas que se exigen).
            $alStmt = $dbh->prepare("SELECT StudentId FROM tblstudents WHERE ClassId = :cid AND Status = 1");
            $alStmt->execute([':cid' => $class]);
            $validStudents = array_map('intval', $alStmt->fetchAll(PDO::FETCH_COLUMN));

            // Escala de letras según tipo/idioma (materia ES).
            $escalaValida = function (int $subId) use ($subjectType): array {
                $t = $subjectType[$subId] ?? 'normal';
                if ($t === 'behavior') return ['E','VG','G','S','N'];
                if ($t === 'extra')    return ['E','MB','B','S','I'];
                // normal (maternal): letra española.
                return ['E','MB','B','S','I'];
            };
            // ¿Esta materia se captura con letra?
            $usaLetra = function (int $subId) use ($subjectType, $isMaternal): bool {
                $t = $subjectType[$subId] ?? 'normal';
                return $isMaternal || $t === 'behavior' || $t === 'extra';
            };

            // Candado: si es DOCENTE y el período está cerrado, no puede capturar aquí.
            // El admin sí puede (sobrescribe con aviso), igual que en la edición.
            $periodoCerrado = period_is_locked($dbh, (int)$class, $trimestre_text);

            if (empty($validSubjects)) {
                $error = "No hay materias asignadas a este grupo.";
            } elseif (empty($validStudents)) {
                $error = "No hay alumnos activos en este grupo.";
            } elseif ($session_role === 'teacher' && $periodoCerrado) {
                $error = "Este período está cerrado y no se guardó ningún cambio. Solo el administrador puede reabrirlo.";
            } else {
                // ── 1) VALIDACIÓN: todas las celdas llenas y válidas (antes de tocar la BD) ──
                $faltantes = 0;
                $fueraRango = false;
                $letraInvalida = false;
                // celdas[sid][subj] = ['tipo'=>'num'|'let', 'val'=>...]
                $celdas = [];

                foreach ($validStudents as $sid) {
                    foreach ($validSubjects as $subId) {
                        if ($usaLetra($subId)) {
                            $raw = $lettersIn[$sid][$subId] ?? '';
                            $L = strtoupper(trim((string)$raw));
                            if ($L === '') { $faltantes++; continue; }
                            if (!in_array($L, $escalaValida($subId), true)) { $letraInvalida = true; continue; }
                            $celdas[$sid][$subId] = ['tipo' => 'let', 'val' => $L];
                        } else {
                            $raw = $marksIn[$sid][$subId] ?? '';
                            if ($raw === '' || $raw === null) { $faltantes++; continue; }
                            if (!is_numeric($raw) || intval($raw) < 0 || intval($raw) > 100) { $fueraRango = true; continue; }
                            $celdas[$sid][$subId] = ['tipo' => 'num', 'val' => intval($raw)];
                        }
                    }
                }

                if ($faltantes > 0) {
                    $error = "Faltan {$faltantes} celda(s) por capturar. Debes llenar la nota de TODOS los alumnos en TODAS las materias antes de guardar.";
                } elseif ($fueraRango) {
                    $error = "Hay calificaciones fuera del rango 0-100. Corrígelas antes de guardar.";
                } elseif ($letraInvalida) {
                    $error = "Hay letras fuera de la escala permitida. Corrígelas antes de guardar.";
                } else {
                    // ── 2) GUARDADO: sobreescribe existentes (auditando) o inserta nuevas ──
                    $existsStmt = $dbh->prepare(
                        "SELECT id, StudentId, ClassId, SubjectId, Trimestre, marks, grade_letter
                         FROM tblresult WHERE StudentId = :sid AND SubjectId = :subid AND ClassId = :cid AND Trimestre = :trim"
                    );
                    $insStmt = $dbh->prepare(
                        "INSERT INTO tblresult(StudentId, ClassId, SubjectId, marks, grade_letter, Trimestre, term, PostingDate)
                         VALUES(:sid, :cid, :subid, :marks, :gl, :trim, :term, NOW())"
                    );

                    $dbh->beginTransaction();
                    try {
                        $nuevas = 0;
                        $actualizadas = 0;

                        foreach ($celdas as $sid => $porMateria) {
                            foreach ($porMateria as $subId => $c) {
                                $newMarks  = ($c['tipo'] === 'num') ? $c['val'] : null;
                                $newLetter = ($c['tipo'] === 'let') ? $c['val'] : null;

                                $existsStmt->execute([':sid' => $sid, ':subid' => $subId, ':cid' => $class, ':trim' => $trimestre_text]);
                                $old = $existsStmt->fetch(PDO::FETCH_ASSOC);

                                if ($old) {
                                    // Sobreescribe solo si cambió; audita el cambio (rastro P2).
                                    $changed = ((int)$old['marks'] !== (int)$newMarks && !($old['marks'] === null && $newMarks === null))
                                            || ((string)$old['grade_letter'] !== (string)$newLetter);
                                    if ($changed) {
                                        $upd = $dbh->prepare("UPDATE tblresult SET marks = :m, grade_letter = :gl WHERE id = :id");
                                        $upd->execute([':m' => $newMarks, ':gl' => $newLetter, ':id' => (int)$old['id']]);
                                        log_result_change($dbh, (int)$old['id'], $old, $newMarks, $newLetter);
                                        $actualizadas++;
                                    }
                                } else {
                                    $insStmt->execute([
                                        ':sid' => $sid, ':cid' => $class, ':subid' => $subId,
                                        ':marks' => $newMarks, ':gl' => $newLetter,
                                        ':trim' => $trimestre_text, ':term' => $term_number,
                                    ]);
                                    $nuevas++;
                                }
                            }
                        }

                        // Auto-cierre: si un DOCENTE guarda el grupo completo, se cierra el periodo.
                        if ($session_role === 'teacher') {
                            set_period_lock($dbh, (int)$class, $trimestre_text, 1, 'auto', true);
                        }

                        $dbh->commit();

                        $msg = "Calificaciones guardadas. Nuevas: {$nuevas}"
                             . ($actualizadas > 0 ? ", actualizadas: {$actualizadas}" : "") . ".";
                        if ($session_role === 'teacher') {
                            $msg .= " El periodo quedó cerrado; para corregir, pide al administrador que lo reabra.";
                        }
                    } catch (PDOException $e) {
                        if ($dbh->inTransaction()) $dbh->rollBack();
                        $error = "Error al guardar las calificaciones. Intenta de nuevo.";
                    }
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
    <title>IPT | Agregar Resultado</title>
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
                if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
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
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">
                                                <div class="form-section">
                                                    <span class="form-section-title">1. Contexto Académico</span>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Grado y Grupo</label>
                                                                <select name="class" class="form-control clid" id="classid" onChange="getPeriodos(this.value);" required>
                                                                    <option value="">Seleccionar...</option>
                                                                    <?php
                                                                    // Orden pedagógico: maternal → kinder → preprimaria → primaria → secundaria,
                                                                    // luego por número de grado y sección.
                                                                    $sql = "SELECT id, ClassName, Section, educationLevel FROM tblclasses
                                                                            ORDER BY AcademicYear DESC,
                                                                                     FIELD(educationLevel,'maternal','kinder','preprimaria','primaria','secundaria'),
                                                                                     ClassNameNumeric ASC, Section ASC";
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
                                                                <select name="periodo_data" id="periodo_data" class="form-control" required onChange="onPeriodoChange();">
                                                                    <option value="">Selecciona un grupo primero</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-section" style="border-bottom: none;">
                                                    <span class="form-section-title">2. Calificaciones del Grupo</span>
                                                    <div id="grid" class="subject-container">
                                                        <p class="text-center text-muted m-0">Selecciona grupo y período para cargar a todos los alumnos.</p>
                                                    </div>
                                                </div>

                                                <div class="p-25">
                                                    <div class="row">
                                                        <div class="col-md-6 col-md-offset-3">
                                                            <button type="submit" name="submit" id="submit" class="btn-save" disabled>
                                                                <i class="fa fa-save"></i> Guardar Calificaciones del Grupo
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
    // Al elegir grupo: genera los períodos según el nivel y (re)carga la matriz.
    function getPeriodos(val) {
        var level = $('#classid option:selected').data('level');
        var $t = $('#periodo_data').empty().append('<option value="">Seleccionar Período</option>');

        // Primaria/secundaria: 3 trimestres. Maternal/kinder/preprimaria: 5 bimestres.
        if (level === 'primaria' || level === 'secundaria') {
            for (var i = 1; i <= 3; i++) $t.append('<option value="2|' + i + '">Trimestre ' + i + '</option>');
        } else {
            for (var i = 1; i <= 5; i++) $t.append('<option value="1|' + i + '">Bimestre ' + i + '</option>');
        }
        // Cambió el grupo: limpiar la matriz hasta que se elija período.
        $('#grid').html('<p class="text-center text-muted m-0">Selecciona el período para cargar a los alumnos.</p>');
        $('#submit').attr('disabled', true);
    }

    // Carga la matriz alumnos × materias del grupo+período elegidos (captura por grupo).
    function loadGrid() {
        var cid = $('#classid').val();
        var periodo = $('#periodo_data').val();

        if (cid === '' || periodo === '') {
            $('#grid').html('<p class="text-center text-muted m-0">Selecciona grupo y período para cargar a todos los alumnos.</p>');
            $('#submit').attr('disabled', true);
            return;
        }

        $('#grid').html('<p class="text-center text-muted m-0"><i class="fa fa-spinner fa-spin"></i> Cargando alumnos...</p>');
        $('#submit').attr('disabled', true);

        $.post('get_group_grid.php?lang=es', { classid: cid, periodo: periodo }, function (data) {
            $('#grid').html(data);
            // Habilitar guardar solo si hay inputs Y el período no está bloqueado para el rol.
            var state = $('#grid').find('[data-grid-state]').data('grid-state');
            var hasInputs = $('#grid').find('.grid-input').length > 0;
            if (hasInputs && state !== 'locked') {
                $('#submit').attr('disabled', false);
            } else {
                $('#submit').attr('disabled', true);
            }
        });
    }

    // onChange inline del período (patrón robusto en esta plantilla heredada).
    function onPeriodoChange() {
        loadGrid();
    }
    </script>
</body>
</html>
<?php include('includes/footer.php'); ?>
<?php include('includes/p_footer.php'); ?>