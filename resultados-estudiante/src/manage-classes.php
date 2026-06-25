<?php
session_start();
error_reporting(0);
include(__DIR__ . '/includes/config.php');

if (!isset($_SESSION['alogin']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
} else {

// Inicializa los mensajes para evitar variables indefinidas en la vista
$msg = '';
$error = '';

// Genera un token CSRF para proteger la eliminación (formulario POST)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Eliminar año: solo vía POST y con token CSRF válido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Solicitud no válida. Recarga la página e inténtalo de nuevo.";
    } elseif (!is_numeric($_POST['delete'])) {
        $error = "Identificador de año no válido.";
    } else {
        $classId = (int) $_POST['delete'];

        // Verificar dependencias antes de eliminar.
        // tblsubjectcombination se filtra con SubjectId IS NOT NULL porque puede
        // contener filas corruptas con SubjectId NULL que no deben bloquear el borrado.
        $checks = [
            'tblstudents'           => ['label' => 'estudiantes inscritos',     'extra' => ''],
            'tblsubjectcombination' => ['label' => 'materias asignadas',        'extra' => ' AND SubjectId IS NOT NULL'],
            'tblteacher_subject'    => ['label' => 'docentes asignados',        'extra' => ''],
            'tblresult'             => ['label' => 'calificaciones registradas','extra' => ''],
        ];
        $blocking = [];
        foreach ($checks as $table => $info) {
            $chk = $dbh->prepare("SELECT COUNT(*) FROM `$table` WHERE ClassId = :id" . $info['extra']);
            $chk->bindParam(':id', $classId, PDO::PARAM_INT);
            $chk->execute();
            if ($chk->fetchColumn() > 0) {
                $blocking[] = $info['label'];
            }
        }

        if (!empty($blocking)) {
            $error = "No se puede eliminar este año porque tiene: " . implode(', ', $blocking) . ". Elimina esos registros primero.";
        } else {
            $sql = "DELETE FROM tblclasses WHERE id = :id";
            $query = $dbh->prepare($sql);
            $query->bindParam(':id', $classId, PDO::PARAM_INT);
            if ($query->execute()) {
                $msg = "Año eliminado correctamente.";
            } else {
                $error = "No se pudo eliminar el año. Intenta de nuevo.";
            }
        }
    }
}
?>

    <!-- Incluye estilos para DataTables -->
    <link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css" />

    <!-- Incluye la barra superior de navegación -->
    <?php include('includes/topbar.php'); ?>

    <!-- Contenedor para barras laterales y contenido principal -->
    <div class="content-wrapper">
        <div class="content-container">

            <!-- Incluye barra lateral izquierda -->
            <?php include('includes/leftbar.php'); ?>

            <div class="main-page">
                <div class="container-fluid">

                    <!-- Título de la página -->
                    <div class="row page-title-div">
                        <div class="col-md-6">
                            <h2 class="title">Gestionar Años</h2>
                        </div>
                    </div>

                    <!-- Breadcrumb de navegación -->
                    <div class="row breadcrumb-div">
                        <div class="col-md-6">
                            <ul class="breadcrumb">
                                <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                                <li> Años</li>
                                <li class="active">Gestionar Años</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Sección principal -->
                <section class="section">
                    <div class="container-fluid">

                        <div class="row">
                            <div class="col-md-12">

                                <div class="panel">

                                    <!-- Encabezado del panel -->
                                    <div class="panel-heading">
                                        <div class="panel-title">
                                            <h5>Ver información de Año</h5>
                                        </div>
                                    </div>

                                    <!-- Muestra mensaje de éxito si $msg está definido -->
                                    <?php if ($msg) { ?>
                                        <div class="alert alert-success left-icon-alert" role="alert">
                                            <strong>Bien hecho</strong> <?php echo htmlentities($msg); ?>
                                        </div>

                                    <!-- Muestra mensaje de error si $error está definido -->
                                    <?php } else if ($error) { ?>
                                        <div class="alert alert-danger left-icon-alert" role="alert">
                                            <strong>Inconvenientes</strong> <?php echo htmlentities($error); ?>
                                        </div>
                                    <?php } ?>

                                    <!-- Cuerpo del panel -->
                                    <div class="panel-body p-20">

                                        <!-- Tabla para mostrar años -->
                                        <table id="example" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Nombre de Año</th>
                                                    <th>Año en número</th>
                                                    <th>Sección</th>
                                                    <th>Fecha de Creación</th>
                                                    <th>Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Consulta para obtener todos los registros de la tabla tblclasses
                                                $sql = "SELECT * from tblclasses";
                                                $query = $dbh->prepare($sql);
                                                $query->execute();

                                                // Obtiene todos los resultados como objetos
                                                $results = $query->fetchAll(PDO::FETCH_OBJ);

                                                // Contador para numerar filas
                                                $cnt = 1;

                                                // Si hay resultados, recorre cada uno para mostrarlos en la tabla
                                                if (!empty($results)) {
                                                    foreach ($results as $result) { ?>
                                                        <tr>
                                                            <!-- Número consecutivo -->
                                                            <td><?php echo htmlentities($cnt); ?></td>

                                                            <!-- Nombre del año -->
                                                            <td><?php echo htmlentities($result->ClassName); ?></td>

                                                            <!-- Año en número -->
                                                            <td><?php echo htmlentities($result->ClassNameNumeric); ?></td>

                                                            <!-- Sección -->
                                                            <td><?php echo htmlentities($result->Section); ?></td>

                                                            <!-- Fecha de creación del registro -->
                                                            <td><?php echo htmlentities($result->CreationDate); ?></td>

                                                            <!-- Acciones -->
                                                            <td>
                                                                <a href="class-setup.php?classid=<?php echo htmlentities($result->id); ?>" class="btn btn-info" title="Configurar grupo">
                                                                    <i class="fa fa-cogs"></i>
                                                                </a>
                                                                <form method="post" action="manage-classes.php" style="display:inline;"
                                                                      onsubmit="return confirm('¿Seguro que deseas eliminar el año <?php echo htmlspecialchars($result->ClassName . ' ' . $result->Section, ENT_QUOTES); ?>? Esta acción no se puede deshacer.');">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">
                                                                    <input type="hidden" name="delete" value="<?php echo htmlentities($result->id); ?>">
                                                                    <button type="submit" class="btn btn-danger" title="Eliminar">
                                                                        <i class="fa fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                <?php
                                                        // Incrementa contador
                                                        $cnt = $cnt + 1;
                                                    }
                                                }
                                                ?>
                                            </tbody>
                                        </table>

                                        <!-- /.col-md-12 -->
                                    </div>
                                </div>
                            </div>
                            <!-- /.col-md-6 -->
                        </div>
                        <!-- /.col-md-12 -->
                    </div>
            </div>
            <!-- /.panel -->
        </div>
        <!-- /.col-md-6 -->
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

    <!-- Incluye pie de página -->
    <?php include('includes/footer.php'); ?>

<?php } ?>

