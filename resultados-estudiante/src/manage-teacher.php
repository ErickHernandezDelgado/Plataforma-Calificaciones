<?php
session_start();
error_reporting(0);
include(__DIR__ . '/includes/config.php');

if (!isset($_SESSION['alogin']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
} else {
?>

<link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css" />

<style>
    /* Esto obliga a la tabla de Manage Teachers a ser verde IPT */
table.table thead th {
    background-color: #0F9B3A !important;
    color: white !important;
    border: none !important;
    text-align: center;
    text-transform: uppercase;
}

/* Color del botón 'Editar' para que no sea azul estándar */
.btn-primary {
    background-color: #0F9B3A !important;
    border-color: #0F9B3A !important;
}

/* Color del botón 'Eliminar' o estados */
.btn-danger {
    background-color: #d9534f !important;
}
    /* Estilo para el encabezado de la tabla */
    #example thead th {
        background-color: #0F9B3A; /* Color azul para el fondo */
        color: #ffffff !important;  /* Letras blancas */
        text-align: center;
        border: none;
    }

    /* Alineación vertical para todas las celdas */
    .table td, .table th {
        vertical-align: middle !important;
        text-align: center;
    }

    .text-left-important {
        text-align: left !important;
    }

    /* Estilo de los Badges */
    .badge {
        padding: 6px 10px;
        border-radius: 4px;
        font-weight: 500;
        display: inline-block;
    }
    .badge-success { background-color: #28a745; color: white; }
    .badge-danger { background-color: #dc3545; color: white; }
    .badge-info { background-color: #17a2b8; color: white; }
    
    /* Botones de acción uniformes */
    .btn-action {
        margin: 2px;
        width: 105px; 
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }
    .btn-action i { margin-right: 5px; }
    
    .no-subjects {
        color: #999;
        font-style: italic;
        font-size: 0.9em;
    }

    /* Mejora el espaciado de la tabla */
    .panel-body {
        padding: 25px !important;
    }
</style>

<?php include('includes/topbar.php'); ?>

<div class="content-wrapper">
    <div class="content-container">
        <?php include('includes/leftbar.php'); ?>

        <div class="main-page">
            <div class="container-fluid">
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Gestión de Maestros</h2>
                    </div>
                </div>
                <div class="row breadcrumb-div">
                    <div class="col-md-6">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li>Maestros</li>
                            <li class="active">Gestión de Maestros</li>
                        </ul>
                    </div>
                </div>
            </div>

            <section class="section">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Lista de Docentes Registrados</h5>
                                    </div>
                                </div>

                                <div class="panel-body p-20">
                                    <table id="example" class="display table table-striped table-hover table-bordered" cellspacing="0" width="100%">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Nombre del Maestro</th>
                                                <th>Correo Electrónico</th>
                                                <th>Materias</th>
                                                <th>Grupos</th>
                                                <th>Ingreso</th>
                                                <th>Estado</th>
                                                <th width="15%">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT t.Id, t.TeacherName, t.TeacherEmail, t.JoiningDate, t.Status,
                                                    COUNT(DISTINCT ts.ClassId) as num_classes,
                                                    GROUP_CONCAT(DISTINCT s.SubjectName ORDER BY s.SubjectName SEPARATOR ', ') as materias
                                                    FROM tblteachers t
                                                    LEFT JOIN tblteacher_subject ts ON t.Id = ts.TeacherId
                                                    LEFT JOIN tblsubjects s ON ts.SubjectId = s.id
                                                    GROUP BY t.Id
                                                    ORDER BY t.TeacherName ASC";
                                            $query = $dbh->prepare($sql);
                                            $query->execute();
                                            $results = $query->fetchAll(PDO::FETCH_OBJ);
                                            $cnt = 1;

                                            if ($query->rowCount() > 0) {
                                                foreach ($results as $result) { ?>
                                                    <tr>
                                                        <td><?php echo $cnt; ?></td>
                                                        <td class="text-left-important"><strong><?php echo htmlentities($result->TeacherName); ?></strong></td>
                                                        <td class="text-left-important"><?php echo htmlentities($result->TeacherEmail); ?></td>
                                                        <td class="text-left-important">
                                                            <?php 
                                                            if (empty($result->materias)) {
                                                                echo '<span class="no-subjects">Sin materias</span>';
                                                            } else {
                                                                echo '<small>' . htmlentities($result->materias) . '</small>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-info"><?php echo $result->num_classes; ?></span>
                                                        </td>
                                                        <td><?php echo date('d/m/Y', strtotime($result->JoiningDate)); ?></td>
                                                        <td>
                                                            <?php echo ($result->Status == 1) 
                                                                ? '<span class="badge badge-success"><i class="fa fa-check"></i> Activo</span>' 
                                                                : '<span class="badge badge-danger"><i class="fa fa-times"></i> Inactivo</span>'; 
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <a href="edit-teacher.php?tid=<?php echo $result->Id; ?>" class="btn btn-primary btn-sm btn-action">
                                                                <i class="fa fa-edit"></i> Editar
                                                            </a>
                                                        </td>
                                                    </tr>
                                            <?php $cnt++; } } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>
<?php } ?>