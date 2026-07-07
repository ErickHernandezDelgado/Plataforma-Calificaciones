<?php 
/**
 * manage-results.php
 * Gestión y edición de calificaciones de estudiantes
 */
include(__DIR__ . '/includes/check-login.php');
require_once(__DIR__ . '/includes/result-audit.php'); // P2: auditoría + candado de periodos

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

// EDICIÓN POR GRUPO COMPLETO: recibe la matriz marks[studentId][subjectId] /
// letters[studentId][subjectId] de UN grupo+periodo. Guarda SOLO lo que cambió
// (celda vacía = no tocar), audita cada cambio, respeta el candado (docente no
// edita periodos cerrados) y NO auto-cierra (es corrección, no captura).
if (isset($_POST['update_marks'])) {
    $class        = intval($_POST['class'] ?? 0);
    $periodo_data = $_POST['periodo_data'] ?? '';
    $marksIn      = $_POST['marks']   ?? []; // [studentId][subjectId] => número
    $lettersIn    = $_POST['letters'] ?? []; // [studentId][subjectId] => letra

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Solicitud no válida. Recarga la página e inténtalo de nuevo.";
    } elseif ($class <= 0 || $periodo_data === '') {
        $error = "Selecciona grupo y período.";
    } else {
        $pp = explode('|', $periodo_data);
        $p_type = intval($pp[0] ?? 0);
        $p_num  = intval($pp[1] ?? 0);
        if ($p_type === 0 || $p_num === 0) {
            $error = "Período inválido.";
        } else {
            $trimestre_text = ($p_type === 1) ? "Bimestre {$p_num}" : "Trimestre {$p_num}";

            // Candado: el docente no edita períodos cerrados; el admin sí.
            $periodoCerrado = period_is_locked($dbh, $class, $trimestre_text);

            if ($teacherRole === 'teacher' && $periodoCerrado) {
                $error = "Este período está cerrado y no se guardó ningún cambio. Solo el administrador puede reabrirlo.";
            } else {
                // Escalas válidas por idioma español (esta pantalla es ES).
                $letras_validas_es = ['E', 'MB', 'B', 'S', 'I'];

                // Un docente solo edita SUS materias. Verificador de propiedad por SubjectId.
                $ownCheck = null;
                if ($teacherRole === 'teacher' && $teacherId) {
                    $ownCheck = $dbh->prepare(
                        "SELECT COUNT(*) FROM tblteacher_subject WHERE TeacherId = :tid AND ClassId = :cid AND SubjectId = :subid"
                    );
                }

                // Localiza la nota existente de un alumno+materia+periodo (para comparar/auditar).
                $findStmt = $dbh->prepare(
                    "SELECT id, StudentId, ClassId, SubjectId, Trimestre, marks, grade_letter
                     FROM tblresult WHERE StudentId = :sid AND SubjectId = :subid AND ClassId = :cid AND Trimestre = :trim"
                );

                $fuera_rango = false;
                $letra_invalida = false;
                $noExistian = 0; // celdas con valor pero sin nota previa (no se crean en edición)

                try {
                    $dbh->beginTransaction();
                    $actualizadas = 0;

                    // Combina las celdas numéricas y de letra en un solo recorrido por [sid][subj].
                    $todo = [];
                    foreach ($marksIn as $sid => $porMat) {
                        foreach ($porMat as $subj => $val) { $todo[(int)$sid][(int)$subj]['num'] = $val; }
                    }
                    foreach ($lettersIn as $sid => $porMat) {
                        foreach ($porMat as $subj => $val) { $todo[(int)$sid][(int)$subj]['let'] = $val; }
                    }

                    foreach ($todo as $sid => $porMat) {
                        foreach ($porMat as $subj => $vals) {
                            // Determina el valor nuevo (número o letra) y valida.
                            $esLetra = array_key_exists('let', $vals);
                            if ($esLetra) {
                                $raw = strtoupper(trim((string)$vals['let']));
                                if ($raw === '') continue; // vacía = no tocar
                                if (!in_array($raw, $letras_validas_es, true)) { $letra_invalida = true; continue; }
                                $newMarks = null; $newLetter = $raw;
                            } else {
                                $raw = $vals['num'];
                                if ($raw === '' || $raw === null) continue; // vacía = no tocar
                                if (!is_numeric($raw) || intval($raw) < 0 || intval($raw) > 100) { $fuera_rango = true; continue; }
                                $newMarks = intval($raw); $newLetter = null;
                            }

                            // Docente: solo sus materias.
                            if ($ownCheck !== null) {
                                $ownCheck->execute([':tid' => $teacherId, ':cid' => $class, ':subid' => $subj]);
                                if ((int)$ownCheck->fetchColumn() === 0) continue;
                            }

                            // Nota existente (la edición NO crea notas nuevas: si no existe, se omite).
                            $findStmt->execute([':sid' => $sid, ':subid' => $subj, ':cid' => $class, ':trim' => $trimestre_text]);
                            $old = $findStmt->fetch(PDO::FETCH_ASSOC);
                            if (!$old) { $noExistian++; continue; }

                            // Solo actualiza/audita si el valor realmente cambió.
                            $changed = ((int)$old['marks'] !== (int)$newMarks && !($old['marks'] === null && $newMarks === null))
                                    || ((string)$old['grade_letter'] !== (string)$newLetter);
                            if (!$changed) continue;

                            $upd = $dbh->prepare("UPDATE tblresult SET marks = :m, grade_letter = :gl WHERE id = :id");
                            $upd->execute([':m' => $newMarks, ':gl' => $newLetter, ':id' => (int)$old['id']]);
                            log_result_change($dbh, (int)$old['id'], $old, $newMarks, $newLetter);
                            $actualizadas++;
                        }
                    }

                    $dbh->commit();

                    if ($actualizadas > 0) {
                        $msg = "Cambios guardados: {$actualizadas} calificación(es) actualizada(s).";
                    } else {
                        $msg = "No hubo cambios que guardar.";
                    }
                    if ($fuera_rango)    $msg .= " Algunas notas estaban fuera del rango 0-100 y no se guardaron.";
                    if ($letra_invalida) $msg .= " Algunas letras estaban fuera de la escala (E/MB/B/S/I) y no se guardaron.";
                    if ($noExistian > 0) $msg .= " {$noExistian} celda(s) sin calificación previa se omitieron (usa Agregar Resultado para capturar nuevas).";
                } catch (PDOException $e) {
                    if ($dbh->inTransaction()) $dbh->rollBack();
                    $error = "Error al actualizar las calificaciones. Intenta de nuevo.";
                }
            }
        }
    }
}

// Historial de cierres/aperturas de periodos (registro de bloqueos).
// Admin: todo. Docente: solo de los grupos donde tiene materias.
if ($teacherRole === 'teacher' && $teacherId) {
    $logStmt = $dbh->prepare(
        "SELECT l.changed_at, l.action, l.scope, l.Trimestre, l.changed_by_name, l.changed_by_role,
                c.ClassName, c.Section
         FROM tblperiod_lock_log l
         LEFT JOIN tblclasses c ON c.id = l.ClassId
         WHERE l.ClassId IN (SELECT DISTINCT ClassId FROM tblteacher_subject WHERE TeacherId = :tid)
         ORDER BY l.changed_at DESC LIMIT 50"
    );
    $logStmt->execute([':tid' => $teacherId]);
} else {
    $logStmt = $dbh->query(
        "SELECT l.changed_at, l.action, l.scope, l.Trimestre, l.changed_by_name, l.changed_by_role,
                c.ClassName, c.Section
         FROM tblperiod_lock_log l
         LEFT JOIN tblclasses c ON c.id = l.ClassId
         ORDER BY l.changed_at DESC LIMIT 50"
    );
}
$lockLog = $logStmt->fetchAll(PDO::FETCH_OBJ);
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
                                <p class="text-muted" style="margin-top: 10px;">Selecciona un grupo y período para ver y editar las calificaciones de todo el grupo</p>
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

                                    <!-- Panel de Filtros: grupo + período (edición por grupo completo) -->
                                    <div class="filter-panel">
                                        <div class="row">
                                            <div class="col-xs-12 col-sm-6 col-md-6">
                                                <div class="form-group">
                                                    <label for="classid"><strong>Grado y Grupo</strong></label>
                                                    <select id="classid" class="form-control" onChange="onGrupoChange(this.value);">
                                                        <option value="">-- Selecciona un grupo --</option>
                                                        <?php
                                                        // Orden pedagógico: maternal → kinder → preprimaria → primaria → secundaria.
                                                        $sql = "SELECT id, ClassName, Section, educationLevel FROM tblclasses
                                                                ORDER BY AcademicYear DESC,
                                                                         FIELD(educationLevel,'maternal','kinder','preprimaria','primaria','secundaria'),
                                                                         ClassNameNumeric ASC, Section ASC";
                                                        $query = $dbh->prepare($sql);
                                                        $query->execute();
                                                        foreach ($query->fetchAll(PDO::FETCH_OBJ) as $g) { ?>
                                                            <option value="<?php echo (int)$g->id; ?>" data-level="<?php echo htmlspecialchars($g->educationLevel, ENT_QUOTES); ?>">
                                                                <?php echo htmlentities($g->ClassName . " (" . $g->Section . ")"); ?>
                                                            </option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-xs-12 col-sm-6 col-md-6">
                                                <div class="form-group">
                                                    <label for="periodo_data"><strong>Período</strong></label>
                                                    <select id="periodo_data" class="form-control" onChange="loadGrid();">
                                                        <option value="">Selecciona un grupo primero</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Panel de edición por grupo (matriz alumnos × materias) -->
                                    <form method="post" id="editGridForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">
                                        <input type="hidden" name="class" id="form_class" value="">
                                        <input type="hidden" name="periodo_data" id="form_periodo" value="">
                                        <input type="hidden" name="update_marks" value="1">
                                        <div id="resultsPanel"></div>
                                        <div id="saveBar" style="margin-top:20px; text-align:right; display:none;">
                                            <button type="submit" class="btn-update">
                                                <i class="fa fa-save"></i> Guardar Cambios del Grupo
                                            </button>
                                        </div>
                                    </form>

                                    <!-- Historial de cierres/aperturas de periodos -->
                                    <div class="result-panel" style="margin-top:30px;">
                                        <div>
                                            <h4><i class="fa fa-history"></i> Historial de cierres de periodos</h4>
                                            <p>Registro de quién cerró o abrió cada periodo y cuándo.</p>
                                        </div>
                                        <?php if (empty($lockLog)): ?>
                                            <p class="text-muted-custom">Aún no hay cierres ni aperturas registrados.</p>
                                        <?php else: ?>
                                        <div style="overflow-x:auto;">
                                            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                                                <thead>
                                                    <tr style="background:#f8fafc; text-align:left;">
                                                        <th style="padding:9px 10px; border-bottom:2px solid #e2e8f0;">Fecha y hora</th>
                                                        <th style="padding:9px 10px; border-bottom:2px solid #e2e8f0;">Acción</th>
                                                        <th style="padding:9px 10px; border-bottom:2px solid #e2e8f0;">Grupo</th>
                                                        <th style="padding:9px 10px; border-bottom:2px solid #e2e8f0;">Periodo</th>
                                                        <th style="padding:9px 10px; border-bottom:2px solid #e2e8f0;">Quién</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($lockLog as $lg): ?>
                                                    <tr>
                                                        <td style="padding:8px 10px; border-bottom:1px solid #eef2f7;"><?php echo date('d/m/Y H:i', strtotime($lg->changed_at)); ?></td>
                                                        <td style="padding:8px 10px; border-bottom:1px solid #eef2f7;">
                                                            <?php if ($lg->action === 'cerro'): ?>
                                                                <span style="color:#991b1b; font-weight:700;"><i class="fa fa-lock"></i> Cerró</span>
                                                            <?php else: ?>
                                                                <span style="color:#065f46; font-weight:700;"><i class="fa fa-unlock"></i> Abrió</span>
                                                            <?php endif; ?>
                                                            <?php if ($lg->scope === 'auto'): ?><small class="text-muted">(auto)</small><?php elseif ($lg->scope === 'unidad'): ?><small class="text-muted">(unidad)</small><?php endif; ?>
                                                        </td>
                                                        <td style="padding:8px 10px; border-bottom:1px solid #eef2f7;"><?php echo htmlentities(($lg->ClassName ?? '—') . ' (' . ($lg->Section ?? '') . ')'); ?></td>
                                                        <td style="padding:8px 10px; border-bottom:1px solid #eef2f7;"><?php echo htmlentities($lg->Trimestre ?? '—'); ?></td>
                                                        <td style="padding:8px 10px; border-bottom:1px solid #eef2f7;"><?php echo htmlentities($lg->changed_by_name ?? '—'); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php endif; ?>
                                    </div>

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
        
        // Al elegir grupo: genera los períodos según el nivel y limpia la matriz.
        function onGrupoChange(classid) {
            var level = $('#classid option:selected').data('level');
            var $t = $('#periodo_data').empty().append('<option value="">Seleccionar Período</option>');
            if (level === 'primaria' || level === 'secundaria') {
                for (var i = 1; i <= 3; i++) $t.append('<option value="2|' + i + '">Trimestre ' + i + '</option>');
            } else {
                for (var i = 1; i <= 5; i++) $t.append('<option value="1|' + i + '">Bimestre ' + i + '</option>');
            }
            $('#resultsPanel').html('');
            $('#saveBar').hide();
        }

        // Carga la matriz alumnos × materias del grupo+período para EDITAR.
        function loadGrid() {
            var cid = $('#classid').val();
            var periodo = $('#periodo_data').val();

            if (cid === '' || periodo === '') {
                $('#resultsPanel').html('');
                $('#saveBar').hide();
                return;
            }

            $('#resultsPanel').html('<p class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Cargando calificaciones del grupo...</p>');
            $('#saveBar').hide();

            $.post('get_group_grid.php?lang=es&mode=edit', { classid: cid, periodo: periodo }, function (data) {
                $('#resultsPanel').html(data);
                // Sincroniza los hidden del form con la selección actual.
                $('#form_class').val(cid);
                $('#form_periodo').val(periodo);
                // Muestra el botón guardar solo si hay inputs y no está bloqueado para el rol.
                var state = $('#resultsPanel').find('[data-grid-state]').data('grid-state');
                var hasInputs = $('#resultsPanel').find('.grid-input').length > 0;
                if (hasInputs && state !== 'locked') {
                    $('#saveBar').show();
                } else {
                    $('#saveBar').hide();
                }
            });
        }
    </script>
</body>
</html>