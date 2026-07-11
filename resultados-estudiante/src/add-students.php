<?php
/**
 * add-students.php
 * Sistema de Gestión de Calificaciones IPT
 * Agregar estudiantes de forma individual o masiva
 */

// Validación centralizada de sesión
include(__DIR__ . '/includes/check-login.php');

// Verificación de rol admin (check-login.php solo valida sesión, no rol)
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Cargar dependencias
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

error_reporting(0);
ini_set('display_errors', 0);

// Genera un token CSRF para proteger los formularios
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Variables de control
$msg = "";
$error = "";
$tutor_credentials = "";

// Relaciones válidas según el ENUM de student_tutor
$relaciones_validas = ['padre', 'madre', 'tutor_legal', 'abuelo'];

/** * LÓGICA DE FUNCIONES AUXILIARES 
 */

// Generar clave legible
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

// Crear tutor con contraseña cifrada en bcrypt (el login acepta bcrypt y MD5 legacy)
function createTutor($dbh, $email, $name) {
    $raw_pass = generatePassword(8);
    $hash_pass = password_hash($raw_pass, PASSWORD_DEFAULT);
    $role = 'tutor';

    $check = $dbh->prepare("SELECT id FROM admin WHERE UserName = :u");
    $check->execute([':u' => $email]);
    if ($check->rowCount() > 0) return 'exists';

    $sql = "INSERT INTO admin (UserName, Password, role) VALUES(:u, :p, :r)";
    $query = $dbh->prepare($sql);
    if ($query->execute([':u' => $email, ':p' => $hash_pass, ':r' => $role])) {
        return ['id' => $dbh->lastInsertId(), 'email' => $email, 'pass' => $raw_pass];
    }
    return null;
}

// Vincular alumno y tutor
function linkTutor($dbh, $sid, $tid, $rel) {
    $sql = "INSERT INTO student_tutor (StudentId, TutorId, RelationshipType, PrimaryContact) VALUES(:sid, :tid, :rel, 1)";
    $q = $dbh->prepare($sql);
    $q->execute([':sid' => $sid, ':tid' => $tid, ':rel' => $rel]);
    
    $upd = $dbh->prepare("UPDATE tblstudents SET primary_tutor_id = :tid WHERE StudentId = :sid");
    return $upd->execute([':tid' => $tid, ':sid' => $sid]);
}

/** * PROCESAMIENTO DE FORMULARIOS 
 */

// Registro Individual
if (isset($_POST['submit'])) {
    // Validación CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Solicitud no válida. Recarga la página e inténtalo de nuevo.";
    } else {
        $name = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['emailid'] ?? '');
        $curp = trim($_POST['curp'] ?? '');
        $classid = intval($_POST['class'] ?? 0);
        $tutor_option = $_POST['tutor_option'] ?? '';
        $relationship = $_POST['relationship_type'] ?? 'padre';

        // Validación del lado servidor
        if ($name === '' || $email === '') {
            $error = "El nombre y el correo del estudiante son obligatorios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "El correo del estudiante no es válido.";
        } elseif ($classid < 1) {
            $error = "Selecciona un grupo/grado válido.";
        } elseif (!in_array($relationship, $relaciones_validas, true)) {
            $error = "Selecciona una relación de tutor válida.";
        } else {
            // Verifica que el grupo exista
            $chkClass = $dbh->prepare("SELECT id FROM tblclasses WHERE id = :id");
            $chkClass->execute([':id' => $classid]);
            if (!$chkClass->fetch()) {
                $error = "El grupo/grado seleccionado no existe.";
            } else {
                // Transacción: alumno + tutor se crean juntos o no se crea nada
                try {
                    $dbh->beginTransaction();

                    // Ciclo vigente (del nivel del grupo) para marcar el año del alumno y su matrícula.
                    $cvStmt = $dbh->prepare(
                        "SELECT sc.AcademicYear FROM tblclasses c
                         JOIN tblschool_config sc ON sc.educationLevel = c.educationLevel WHERE c.id = :cl"
                    );
                    $cvStmt->execute([':cl' => $classid]);
                    $cicloNuevo = (string)($cvStmt->fetchColumn() ?: date('Y'));

                    $sql = "INSERT INTO tblstudents(StudentName, StudentEmail, Curp, ClassId, AcademicYear, Status) VALUES(:n, :e, :c, :cl, :ay, 1)";
                    $query = $dbh->prepare($sql);
                    $query->execute([':n' => $name, ':e' => $email, ':c' => $curp, ':cl' => $classid, ':ay' => $cicloNuevo]);
                    $student_id = $dbh->lastInsertId();

                    // Matrícula del alumno en su grupo para el ciclo vigente (historial).
                    $dbh->prepare(
                        "INSERT IGNORE INTO tblenrollment (StudentId, ClassId, AcademicYear) VALUES (:sid, :cl, :ay)"
                    )->execute([':sid' => $student_id, ':cl' => $classid, ':ay' => $cicloNuevo]);

                    if ($tutor_option === 'create') {
                        // OPCIÓN 1: CREAR TUTOR NUEVO
                        $t_email = trim($_POST['tutor_email'] ?? '');
                        if (!filter_var($t_email, FILTER_VALIDATE_EMAIL)) {
                            throw new RuntimeException("El correo del tutor no es válido.");
                        }
                        $t_info = createTutor($dbh, $t_email, trim($_POST['tutor_name'] ?? ''));

                        if ($t_info === 'exists') {
                            throw new RuntimeException("El correo del tutor ya existe en el sistema.");
                        } elseif ($t_info) {
                            linkTutor($dbh, $student_id, $t_info['id'], $relationship);
                            $dbh->commit();
                            $tutor_credentials = "
                            <div class='alert alert-warning' style='border: 2px solid #856404;'>
                                <strong>🔑 CREDENCIALES DEL TUTOR:</strong><br>
                                Usuario: <b>" . htmlentities($t_info['email']) . "</b> | Contraseña: <b style='color:red;'>" . htmlentities($t_info['pass']) . "</b>
                            </div>";
                            $msg = "Estudiante y Tutor creados con éxito.";
                        } else {
                            throw new RuntimeException("No se pudo crear el tutor.");
                        }
                    } elseif ($tutor_option === 'existing') {
                        // OPCIÓN 2: VINCULAR TUTOR EXISTENTE
                        $existing_tutor_id = intval($_POST['existing_tutor_id'] ?? 0);
                        if ($existing_tutor_id < 1) {
                            throw new RuntimeException("Debe seleccionar un tutor válido.");
                        }
                        $tutor_query = $dbh->prepare("SELECT UserName FROM admin WHERE id = :tid AND role = 'tutor'");
                        $tutor_query->execute([':tid' => $existing_tutor_id]);
                        $tutor_info = $tutor_query->fetch(PDO::FETCH_OBJ);

                        if (!$tutor_info) {
                            throw new RuntimeException("El tutor seleccionado no existe en el sistema.");
                        }
                        linkTutor($dbh, $student_id, $existing_tutor_id, $relationship);
                        $dbh->commit();
                        $msg = "Estudiante vinculado con tutor existente correctamente.";
                        $tutor_credentials = "
                        <div class='alert alert-success' style='border: 2px solid #155724;'>
                            <strong>✓ TUTOR EXISTENTE VINCULADO:</strong><br>
                            Email del Tutor: <b>" . htmlentities($tutor_info->UserName) . "</b><br>
                            Relación: <b>" . htmlentities(ucfirst($relationship)) . "</b><br>
                            <em>El tutor puede acceder a las calificaciones de este estudiante</em>
                        </div>";
                    } else {
                        throw new RuntimeException("Selecciona una opción de tutor.");
                    }
                } catch (PDOException $e) {
                    // PDOException debe ir ANTES que RuntimeException porque es una subclase de ella;
                    // si no, el catch de RuntimeException la atraparía y mostraría el mensaje SQL crudo.
                    if ($dbh->inTransaction()) $dbh->rollBack();
                    if ($e->getCode() == 23000) {
                        $error = "Ya existe un estudiante o tutor registrado con ese correo electrónico.";
                    } else {
                        $error = "No se pudo registrar al estudiante. Intenta de nuevo.";
                    }
                } catch (RuntimeException $e) {
                    if ($dbh->inTransaction()) $dbh->rollBack();
                    $error = $e->getMessage();
                }
            }
        }
    }
}

// ─── IMPORTACIÓN MASIVA DESDE EXCEL (.xlsx) ───────────────────────────────────
// Reglas (decididas con el usuario):
//  - El GRUPO se elige en la pantalla; todo el Excel se inscribe en ese grupo.
//  - Columnas por POSICIÓN: A Nombre alumno | B Correo alumno | C CURP | D Correo tutor | E Nombre tutor | F Relación
//  - TODO O NADA: si UNA fila tiene error, no se importa NINGUNA (transacción + validación previa).
//  - Login = correo del ALUMNO + contraseña del TUTOR. Se crea tutor (o se reutiliza si el correo ya existe),
//    con vínculo CanViewGrades=1 y matrícula (tblenrollment) + AcademicYear del ciclo vigente.
//  - Al final se muestran las credenciales generadas (correo alumno + contraseña del tutor).
$import_rows_result = null; // para la vista (tabla de credenciales o errores)
if (isset($_POST['import_excel'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Solicitud no válida. Recarga la página e inténtalo de nuevo.";
    } elseif (empty($_FILES['excel_file']['name']) || ($_FILES['excel_file']['error'] ?? 1) !== UPLOAD_ERR_OK) {
        $error = "No se recibió el archivo. Selecciona un .xlsx válido.";
    } else {
        $import_class = intval($_POST['import_class'] ?? 0);
        $ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));

        // Validar grupo destino.
        $chkImpClass = $dbh->prepare("SELECT id, educationLevel FROM tblclasses WHERE id = :id");
        $chkImpClass->execute([':id' => $import_class]);
        $impClassRow = $chkImpClass->fetch(PDO::FETCH_ASSOC);

        if ($import_class < 1 || !$impClassRow) {
            $error = "Selecciona un grupo/grado válido para la carga masiva.";
        } elseif ($ext !== 'xlsx') {
            $error = "El archivo debe ser .xlsx (Excel).";
        } elseif (($_FILES['excel_file']['size'] ?? 0) > 5 * 1024 * 1024) {
            $error = "El archivo es demasiado grande (máx. 5 MB).";
        } else {
            try {
                $spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);
                $data = $spreadsheet->getActiveSheet()->toArray();

                // Quitar la fila de encabezados (fila 1).
                array_shift($data);
                // Quitar filas totalmente vacías (incluida la de ejemplo si la dejaron vacía).
                $data = array_values(array_filter($data, function ($r) {
                    return trim((string)($r[0] ?? '')) !== '' || trim((string)($r[1] ?? '')) !== '';
                }));

                if (empty($data)) {
                    $error = "El archivo no tiene filas de alumnos (solo encabezados).";
                } else {
                    // Ciclo vigente del nivel del grupo destino.
                    $cvStmt = $dbh->prepare("SELECT AcademicYear FROM tblschool_config WHERE educationLevel = :lvl");
                    $cvStmt->execute([':lvl' => $impClassRow['educationLevel']]);
                    $cicloVigente = (string)($cvStmt->fetchColumn() ?: date('Y'));

                    // ── PASADA 1: VALIDACIÓN (sin tocar BD). Todo o nada. ──
                    $errores = [];          // ["fila N: motivo", ...]
                    $filas = [];            // filas normalizadas y válidas
                    $emailsAlumnoEnArchivo = []; // para detectar duplicados dentro del propio Excel

                    // Prepara verificadores de existencia.
                    $existAlumno = $dbh->prepare("SELECT 1 FROM tblstudents WHERE StudentEmail = :e");
                    $existAdmin  = $dbh->prepare("SELECT id FROM admin WHERE UserName = :e");

                    foreach ($data as $i => $row) {
                        $nfila = $i + 2; // +2: fila 1 eran encabezados y $i arranca en 0
                        $nombre   = trim((string)($row[0] ?? ''));
                        $correoAl = strtolower(trim((string)($row[1] ?? '')));
                        $curp     = trim((string)($row[2] ?? ''));
                        $correoTu = strtolower(trim((string)($row[3] ?? '')));
                        $nombreTu = trim((string)($row[4] ?? ''));
                        $relacion = strtolower(trim((string)($row[5] ?? ''))) ?: 'tutor_legal';

                        if ($nombre === '')                              { $errores[] = "Fila {$nfila}: falta el nombre del alumno."; continue; }
                        if ($correoAl === '' || !filter_var($correoAl, FILTER_VALIDATE_EMAIL)) { $errores[] = "Fila {$nfila}: correo del alumno inválido."; continue; }
                        if ($correoTu === '' || !filter_var($correoTu, FILTER_VALIDATE_EMAIL)) { $errores[] = "Fila {$nfila}: correo del tutor inválido."; continue; }
                        if (!in_array($relacion, $relaciones_validas, true)) { $errores[] = "Fila {$nfila}: relación '{$relacion}' no válida (padre/madre/tutor_legal/abuelo)."; continue; }
                        if ($curp !== '' && strlen($curp) > 18)          { $errores[] = "Fila {$nfila}: CURP demasiado largo."; continue; }

                        // Duplicado dentro del archivo.
                        if (isset($emailsAlumnoEnArchivo[$correoAl])) {
                            $errores[] = "Fila {$nfila}: el correo del alumno '{$correoAl}' está repetido en el archivo.";
                            continue;
                        }
                        // Correo de alumno ya existe en el sistema.
                        $existAlumno->execute([':e' => $correoAl]);
                        if ($existAlumno->fetch()) {
                            $errores[] = "Fila {$nfila}: ya existe un alumno con el correo '{$correoAl}'.";
                            continue;
                        }

                        $emailsAlumnoEnArchivo[$correoAl] = true;
                        $filas[] = [
                            'nombre' => $nombre, 'correoAl' => $correoAl, 'curp' => $curp,
                            'correoTu' => $correoTu, 'nombreTu' => $nombreTu, 'relacion' => $relacion,
                        ];
                    }

                    if (!empty($errores)) {
                        // TODO O NADA: hay errores → no se importa nada.
                        $error = "No se importó ningún alumno. Corrige estos errores y vuelve a subir el archivo:";
                        $import_rows_result = ['tipo' => 'errores', 'items' => $errores];
                    } else {
                        // ── PASADA 2: GUARDADO en una sola transacción. ──
                        try {
                            $dbh->beginTransaction();
                            $credenciales = []; // para mostrar al final
                            // Cache de tutores creados/existentes por correo (evita duplicar en el mismo lote).
                            $tutorPorCorreo = [];

                            $insAlumno = $dbh->prepare(
                                "INSERT INTO tblstudents(StudentName, StudentEmail, Curp, ClassId, AcademicYear, Status)
                                 VALUES(:n, :e, :c, :cl, :ay, 1)"
                            );
                            $insEnroll = $dbh->prepare(
                                "INSERT IGNORE INTO tblenrollment (StudentId, ClassId, AcademicYear) VALUES (:sid, :cl, :ay)"
                            );

                            foreach ($filas as $f) {
                                // 1) Alumno.
                                $insAlumno->execute([
                                    ':n' => $f['nombre'], ':e' => $f['correoAl'], ':c' => ($f['curp'] ?: null),
                                    ':cl' => $import_class, ':ay' => $cicloVigente,
                                ]);
                                $sid = (int)$dbh->lastInsertId();

                                // 2) Tutor: reusar si el correo ya existe (en el sistema o en este lote); si no, crear.
                                $claveMostrar = null;
                                if (isset($tutorPorCorreo[$f['correoTu']])) {
                                    $tid = $tutorPorCorreo[$f['correoTu']];
                                    $claveMostrar = '(cuenta existente)';
                                } else {
                                    $existAdmin->execute([':e' => $f['correoTu']]);
                                    $adminRow = $existAdmin->fetch(PDO::FETCH_ASSOC);
                                    if ($adminRow) {
                                        $tid = (int)$adminRow['id'];
                                        $claveMostrar = '(cuenta existente)';
                                    } else {
                                        $t = createTutor($dbh, $f['correoTu'], $f['nombreTu']);
                                        if (!is_array($t)) { throw new RuntimeException("No se pudo crear el tutor '{$f['correoTu']}'."); }
                                        $tid = (int)$t['id'];
                                        $claveMostrar = $t['pass'];
                                    }
                                    $tutorPorCorreo[$f['correoTu']] = $tid;
                                }

                                // 3) Vínculo alumno↔tutor (CanViewGrades=1 explícito para que el login funcione).
                                $dbh->prepare(
                                    "INSERT INTO student_tutor (StudentId, TutorId, RelationshipType, PrimaryContact, CanViewGrades)
                                     VALUES (:sid, :tid, :rel, 1, 1)"
                                )->execute([':sid' => $sid, ':tid' => $tid, ':rel' => $f['relacion']]);
                                $dbh->prepare("UPDATE tblstudents SET primary_tutor_id = :tid WHERE StudentId = :sid")
                                    ->execute([':tid' => $tid, ':sid' => $sid]);

                                // 4) Matrícula del ciclo vigente.
                                $insEnroll->execute([':sid' => $sid, ':cl' => $import_class, ':ay' => $cicloVigente]);

                                $credenciales[] = [
                                    'alumno' => $f['nombre'], 'correoAlumno' => $f['correoAl'],
                                    'correoTutor' => $f['correoTu'], 'clave' => $claveMostrar,
                                ];
                            }

                            $dbh->commit();
                            $msg = "Se importaron " . count($credenciales) . " alumno(s) correctamente en el grupo seleccionado.";
                            $import_rows_result = ['tipo' => 'credenciales', 'items' => $credenciales];
                        } catch (Throwable $e) {
                            if ($dbh->inTransaction()) $dbh->rollBack();
                            $error = "Ocurrió un error al guardar; no se importó ningún alumno. Intenta de nuevo.";
                        }
                    }
                }
            } catch (Throwable $e) {
                $error = "No se pudo leer el archivo Excel. Asegúrate de que sea un .xlsx válido con el formato de la plantilla.";
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
    <title>IPT | Agregar Estudiante</title>
    <link rel="stylesheet" href="css/bootstrap.min.css" media="screen">
    <link rel="stylesheet" href="css/font-awesome.min.css" media="screen">
    <link rel="stylesheet" href="css/main.css" media="screen">
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
                            <div class="col-md-6"><h2 class="title">Inscripción de Alumnos</h2></div>
                        </div>
                        
                        <section class="section">
                            <div class="panel">
                                <div class="panel-body">
                                    <?php if($msg) echo "<div class='alert alert-success'>" . htmlentities($msg) . "</div>"; ?>
                                    <?php if($error) echo "<div class='alert alert-danger'>" . htmlentities($error) . "</div>"; ?>
                                    <?php /* Credenciales del tutor ocultas (2026-07-09): el tutor entra solo con el
                                             correo del alumno, sin clave. La cuenta se sigue creando; solo no se muestra
                                             la contraseña. Reactivar quitando "false &&". */ ?>
                                    <?php if(false && $tutor_credentials) echo $tutor_credentials; ?>

                                    <form method="post" class="row">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">
                                        <div class="form-group col-md-6">
                                            <label>Nombre Completo</label>
                                            <input type="text" name="fullname" class="form-control" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Correo Electrónico</label>
                                            <input type="email" name="emailid" class="form-control" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>CURP</label>
                                            <input type="text" name="curp" class="form-control" maxlength="18">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Clase/Grado</label>
                                            <select name="class" class="form-control" required>
                                                <option value="">Seleccione...</option>
                                                <?php 
                                                $q = $dbh->prepare("SELECT * FROM vw_classes_for_enrollment");
                                                $q->execute();
                                                foreach($q->fetchAll(PDO::FETCH_OBJ) as $c) {
                                                    echo "<option value='" . (int)$c->id . "'>" . htmlentities($c->ClassName_display) . "</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <div class="col-md-12"><hr><h4>Datos del Tutor</h4></div>
                                        <div class="form-group col-md-12">
                                            <label for="opt1">Nuevo</label>
                                            <input type="radio" name="tutor_option" value="create" checked id="opt1" onchange="toggleTutor('c')"> 
                                            <label for="opt2">Existente</label>
                                            <input type="radio" name="tutor_option" value="existing" id="opt2" onchange="toggleTutor('e')"> 
                                        </div>

                                        <div id="c_fields" class="col-md-12">
                                            <div class="row">
                                                <div class="form-group col-md-4"><label>Nombre</label><input type="text" name="tutor_name" class="form-control"></div>
                                                <div class="form-group col-md-4"><label>Email</label><input type="email" name="tutor_email" class="form-control"></div>
                                                <div class="form-group col-md-4">
                                                    <label>Relación</label>
                                                    <select name="relationship_type" class="form-control">
                                                        <option value="padre">Padre</option>
                                                        <option value="madre">Madre</option>
                                                        <option value="tutor_legal">Tutor Legal</option>
                                                        <option value="abuelo">Abuelo/a</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div id="e_fields" class="col-md-12" style="display:none;">
                                            <label>Seleccionar Tutor</label>
                                            <select name="existing_tutor_id" class="form-control">
                                                <?php
                                                $qt = $dbh->prepare("SELECT id, UserName FROM admin WHERE role='tutor'");
                                                $qt->execute();
                                                foreach($qt->fetchAll(PDO::FETCH_OBJ) as $t) echo "<option value='" . (int)$t->id . "'>" . htmlentities($t->UserName) . "</option>";
                                                ?>
                                            </select>
                                        </div>

                                        <div class="col-md-12"><br><button type="submit" name="submit" class="btn btn-primary">Registrar</button></div>
                                    </form>

                                    <hr>
                                    <h4>Carga Masiva desde Excel</h4>
                                    <p class="text-muted" style="font-size:13px;">
                                        Descarga la plantilla, llénala (un alumno por fila) y súbela eligiendo el grupo destino.
                                        Todos los alumnos del archivo se inscriben en ese grupo. Si una fila tiene error, no se importa ninguna.
                                    </p>

                                    <div style="margin-bottom:12px;">
                                        <a href="descargar-plantilla-alumnos.php" class="btn btn-default">
                                            <i class="fa fa-download"></i> Descargar plantilla de ejemplo
                                        </a>
                                    </div>

                                    <form method="post" enctype="multipart/form-data" class="row">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">
                                        <div class="form-group col-md-5">
                                            <label>Grupo destino</label>
                                            <select name="import_class" class="form-control" required>
                                                <option value="">Seleccione el grupo...</option>
                                                <?php
                                                $qImp = $dbh->prepare("SELECT * FROM vw_classes_for_enrollment");
                                                $qImp->execute();
                                                foreach ($qImp->fetchAll(PDO::FETCH_OBJ) as $c) {
                                                    echo "<option value='" . (int)$c->id . "'>" . htmlentities($c->ClassName_display) . "</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-5">
                                            <label>Archivo Excel (.xlsx)</label>
                                            <input type="file" name="excel_file" accept=".xlsx" class="form-control" required>
                                        </div>
                                        <div class="form-group col-md-2" style="display:flex; align-items:flex-end;">
                                            <button type="submit" name="import_excel" class="btn btn-info btn-block">
                                                <i class="fa fa-upload"></i> Importar
                                            </button>
                                        </div>
                                    </form>

                                    <?php if ($import_rows_result !== null): ?>
                                        <?php if ($import_rows_result['tipo'] === 'errores'): ?>
                                            <div class="alert alert-danger" style="margin-top:15px;">
                                                <strong>Errores encontrados (no se importó nada):</strong>
                                                <ul style="margin:8px 0 0; padding-left:20px;">
                                                    <?php foreach ($import_rows_result['items'] as $err): ?>
                                                        <li><?php echo htmlentities($err); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php else: ?>
                                            <div id="import-result" style="margin-top:20px; border:2px solid #0F9B3A; border-radius:10px; padding:18px; background:#f0fff4;">
                                                <div class="alert alert-success" style="margin-bottom:12px;">
                                                    <i class="fa fa-check-circle"></i> <strong>Alumnos importados.</strong> Anótalos o imprímelos como comprobante.
                                                    Cada familia inicia sesión SOLO con el <strong>correo del alumno</strong> (sin contraseña).
                                                </div>
                                                <div style="margin-bottom:10px;">
                                                    <button type="button" class="btn btn-default btn-sm" onclick="imprimirCredenciales();">
                                                        <i class="fa fa-print"></i> Imprimir lista
                                                    </button>
                                                </div>
                                                <div style="overflow-x:auto;">
                                                    <table id="tabla-credenciales" class="table table-bordered" style="font-size:13px; background:#fff;">
                                                        <thead>
                                                            <tr style="background:#0F9B3A; color:#fff;">
                                                                <th>Alumno</th>
                                                                <th>Correo del alumno (acceso)</th>
                                                                <th>Correo del tutor</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($import_rows_result['items'] as $cred): ?>
                                                                <tr>
                                                                    <td><?php echo htmlentities($cred['alumno']); ?></td>
                                                                    <td><?php echo htmlentities($cred['correoAlumno']); ?></td>
                                                                    <td><?php echo htmlentities($cred['correoTutor']); ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
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
        function toggleTutor(mode) {
            document.getElementById('c_fields').style.display = (mode === 'c') ? 'block' : 'none';
            document.getElementById('e_fields').style.display = (mode === 'e') ? 'block' : 'none';
        }

        // Tras un import exitoso, llevar la vista a la tabla de credenciales (para que no pase desapercibida).
        document.addEventListener('DOMContentLoaded', function () {
            var res = document.getElementById('import-result');
            if (res) { res.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });

        // Imprimir solo la tabla de credenciales (ventana aparte).
        function imprimirCredenciales() {
            var tabla = document.getElementById('tabla-credenciales');
            if (!tabla) return;
            var w = window.open('', '_blank');
            w.document.write('<html><head><title>Credenciales de acceso - IPT</title>');
            w.document.write('<style>body{font-family:Arial,sans-serif;padding:20px;} h3{color:#0F9B3A;} table{width:100%;border-collapse:collapse;font-size:13px;} th,td{border:1px solid #333;padding:8px;text-align:left;} th{background:#0F9B3A;color:#fff;}</style>');
            w.document.write('</head><body>');
            w.document.write('<h3>Credenciales de acceso al portal - Instituto Panamericano de Tampico</h3>');
            w.document.write('<p>Cada familia inicia sesión con el <b>correo del alumno</b> y la contraseña indicada.</p>');
            w.document.write(tabla.outerHTML);
            w.document.write('</body></html>');
            w.document.close();
            w.focus();
            w.print();
        }
    </script>
</body>
</html>
<?php include('includes/footer.php'); ?>