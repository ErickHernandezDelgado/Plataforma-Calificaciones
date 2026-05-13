<?php
/**
 * edit-result.php
 * Actualización de calificaciones por periodo específico
 */
include(__DIR__ . '/includes/check-login.php');

// Recibimos el ID de la calificación específica
$resultid = intval($_GET['resultid'] ?? 0);

if ($resultid === 0) {
    // Fallback para parámetros antiguos
    $stid = intval($_GET['stid'] ?? 0);
    $classid = intval($_GET['classid'] ?? 0);
} else {
    // Obtener los datos desde la calificación
    $sql_info = "SELECT StudentId, ClassId FROM tblresult WHERE id = :id LIMIT 1";
    $stmt = $dbh->prepare($sql_info);
    $stmt->execute([':id' => $resultid]);
    $info = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($info) {
        $stid = $info->StudentId;
        $classid = $info->ClassId;
    } else {
        header("Location: manage-results.php");
        exit;
    }
}

$msg = "";
$error = "";

// PROCESAR ACTUALIZACIÓN
if (isset($_POST['submit'])) {
    $rowids = $_POST['id'];     // IDs únicos de la tabla tblresult
    $marks = $_POST['marks'];   // Nuevas calificaciones

    try {
        $dbh->beginTransaction();
        foreach ($rowids as $count => $id) {
            $mrks = $marks[$count];
            $iid = $id;

            $sql = "UPDATE tblresult SET marks = :mrks WHERE id = :iid";
            $query = $dbh->prepare($sql);
            $query->bindParam(':mrks', $mrks, PDO::PARAM_STR);
            $query->bindParam(':iid', $iid, PDO::PARAM_INT);
            $query->execute();
        }
        $dbh->commit();
        $msg = "Calificaciones del periodo actualizadas con éxito";
    } catch (Exception $e) {
        $dbh->rollBack();
        $error = "Error al actualizar: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IPT | Editar Resultados</title>
    <link rel="stylesheet" href="css/bootstrap.min.css" media="screen">
    <link rel="stylesheet" href="css/font-awesome.min.css" media="screen">
    <link rel="stylesheet" href="css/main.css" media="screen">
    <style>
        /* ====== GENERAL ====== */
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        
        /* ====== PANEL ====== */
        .panel {
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        
        .panel-heading {
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f5f9 100%);
            border-bottom: 1px solid #e2e8f0;
            padding: 25px;
        }
        
        .panel-title {
            color: #1e293b;
            font-weight: 700;
            margin: 0;
            font-size: 16px;
        }
        
        .panel-title h5 {
            margin: 0;
            color: #1e293b;
            font-weight: 700;
            font-size: 16px;
        }
        
        .panel-title p {
            margin: 8px 0 0 0;
            color: #64748b;
            font-size: 14px;
        }
        
        .panel-body {
            padding: 30px 25px;
        }
        
        /* ====== ALERTS ====== */
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            color: #7f1d1d;
            border-left: 4px solid #ef4444;
        }
        
        .alert-warning {
            background-color: #fef3c7;
            color: #78350f;
            border-left: 4px solid #f59e0b;
        }
        
        .alert-info {
            background-color: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
        
        /* ====== FORM GROUPS ====== */
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group:last-child {
            margin-bottom: 0;
        }
        
        label {
            font-weight: 600;
            color: #334155;
            margin-bottom: 10px;
            display: block;
            font-size: 14px;
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
        
        /* ====== BUTTONS ====== */
        .btn {
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            padding: 12px 24px;
            transition: all 0.2s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .btn-default {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
        }
        
        .btn-default:hover {
            background: #e2e8f0;
            color: #1e293b;
        }
        
        .btn-labeled {
            padding-left: 0;
        }
        
        .btn-label {
            padding-right: 12px;
            padding-left: 15px;
        }
        
        .btn-label-right {
            padding-right: 15px;
            padding-left: 12px;
        }
        
        /* ====== BREADCRUMB ====== */
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
            font-size: 13px;
        }
        
        .breadcrumb li {
            display: inline;
            color: #64748b;
        }
        
        .breadcrumb li a {
            color: #3b82f6;
            text-decoration: none;
        }
        
        .breadcrumb li a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb li.active {
            color: #94a3b8;
        }
        
        .breadcrumb li + li:before {
            content: " / ";
            padding: 0 8px;
            color: #cbd5e1;
        }
        
        /* ====== RESPONSIVE ====== */
        @media (max-width: 768px) {
            .panel-heading {
                padding: 20px 15px;
            }
            
            .panel-body {
                padding: 20px 15px;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .panel-heading {
                padding: 15px 10px;
            }
            
            .panel-body {
                padding: 15px 10px;
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
                <?php include('includes/leftbar.php'); ?>

                <div class="main-page">
                    <div class="container-fluid">
                        <div class="row page-title-div">
                            <div class="col-md-6">
                                <h2 class="title">Editar Calificaciones</h2>
                            </div>
                        </div>
                        <div class="row breadcrumb-div">
                            <div class="col-md-6">
                                <ul class="breadcrumb">
                                    <li><a href="dashboard.php">Inicio</a></li>
                                    <li><a href="manage-results.php">Resultados</a></li>
                                    <li class="active">Editar</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <section class="section">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-md-8 col-md-offset-2">
                                    <div class="panel">
                                        <div class="panel-heading">
                                            <div class="panel-title">
                                                <?php
                                                // Obtener información del estudiante y calificación usando vista
                                                $info_sql = "SELECT s.StudentName, c.ClassName, c.Section, vr.term_name
                                                            FROM tblstudents s 
                                                            JOIN tblclasses c ON c.id = :classid
                                                            LEFT JOIN vw_result_with_terms vr ON vr.StudentId = s.StudentId AND vr.ClassId = :classid
                                                            WHERE s.StudentId = :stid 
                                                            LIMIT 1";
                                                $info_stmt = $dbh->prepare($info_sql);
                                                $info_stmt->execute([':stid' => $stid, ':classid' => $classid]);
                                                $data = $info_stmt->fetch(PDO::FETCH_OBJ);
                                                ?>
                                                <h5>Actualizando: <strong><?php echo htmlentities($data->StudentName ?? 'N/A'); ?></strong></h5>
                                                <p class="text-primary"><?php echo htmlentities(($data->ClassName ?? 'N/A') . " " . ($data->Section ?? '') . " | " . ($data->term_name ?? 'N/A')); ?></p>
                                            </div>
                                        </div>
                                        <div class="panel-body">
                                            <?php if($msg){ ?>
                                                <div class="alert alert-success"><strong>Éxito!</strong> <?php echo htmlentities($msg); ?></div>
                                            <?php } ?>
                                            
                                            <form class="form-horizontal" method="post">
                                                <?php
                                                // CORRECCIÓN: Usar vista vw_result_with_terms y tabla tblsubjects
                                                $sql = "SELECT sub.SubjectName, vr.marks, vr.id as resultid, vr.term_name
                                                        FROM vw_result_with_terms vr
                                                        JOIN tblsubjects sub ON sub.id = vr.SubjectId 
                                                        WHERE vr.StudentId = :stid 
                                                        AND vr.ClassId = :classid
                                                        ORDER BY sub.SubjectName";
                                                
                                                $query = $dbh->prepare($sql);
                                                $query->execute([
                                                    ':stid' => $stid,
                                                    ':classid' => $classid
                                                ]);
                                                $results = $query->fetchAll(PDO::FETCH_OBJ);

                                                if (count($results) > 0) {
                                                    foreach ($results as $result) { ?>
                                                        <div class="form-group">
                                                            <label class="col-sm-4 control-label">
                                                                <?php echo htmlentities($result->SubjectName); ?>
                                                                <small class="text-muted">(<?php echo htmlentities($result->term_name); ?>)</small>
                                                            </label>
                                                            <div class="col-sm-6">
                                                                <input type="hidden" name="id[]" value="<?php echo $result->resultid; ?>">
                                                                <input type="number" step="0.01" name="marks[]" class="form-control" 
                                                                       value="<?php echo htmlentities($result->marks); ?>" min="0" max="100" required>
                                                            </div>
                                                        </div>
                                                    <?php }
                                                } else {
                                                    echo '<div class="alert alert-warning">No hay calificaciones registradas para este estudiante y clase.</div>';
                                                }
                                                ?>

                                                <div class="form-group">
                                                    <div class="col-sm-offset-4 col-sm-6">
                                                        <button type="submit" name="submit" class="btn btn-primary btn-labeled">
                                                            Actualizar Calificaciones<span class="btn-label btn-label-right"><i class="fa fa-check"></i></span>
                                                        </button>
                                                        <a href="manage-results.php" class="btn btn-default">Cancelar</a>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
    <script src="js/jquery/jquery-2.2.4.min.js"></script>
    <script src="js/bootstrap/bootstrap.min.js"></script>
</body>
</html>
<?php include('includes/footer.php'); ?>