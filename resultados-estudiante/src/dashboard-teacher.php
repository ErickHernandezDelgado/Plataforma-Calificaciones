<?php
// Muestra todos los errores durante desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inicia sesión
session_start();
include(__DIR__ . '/includes/config.php'); // Conexión a la base de datos

// Verifica que el usuario haya iniciado sesión y que sea un maestro
if (!isset($_SESSION['alogin']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php"); // Si no es maestro o no ha iniciado sesión, redirige al login
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Profesor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Estilos principales (puedes añadir más si los necesitas) -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>

<body>
    <!-- Incluye la barra superior común -->
    <?php include('includes/topbar.php'); ?>

    <!-- Contenedor principal del dashboard -->
    <div class="content-wrapper">
        <div class="content-container">

            <!-- Menú lateral para el maestro -->
            <?php include('includes/leftbar-teacher.php'); ?>

            <!-- Contenido principal del dashboard -->
            <div class="main-page">
                <div class="container-fluid">

                    <!-- Título de la página -->
                    <div class="row page-title-div">
                        <div class="col-md-6">
                            <h2 class="title">Dashboard del Profesor</h2>
                        </div>
                    </div>

                    <!-- Breadcrumb o navegación secundaria -->
                    <div class="row breadcrumb-div">
                        <div class="col-md-6">
                            <ul class="breadcrumb">
                                <li><a href="dashboard-teacher.php"><i class="fa fa-home"></i> Inicio</a></li>
                                <li>Resultados</li>
                                <li class="active">Gestionar Resultados</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Sección de botones de acción -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Acciones de Resultados</h5>
                                    </div>
                                </div>
                                <div class="panel-body p-20">
                                    <!-- Botón para agregar nuevo resultado -->
                                    <a href="add-result2.php" class="btn btn-success">Agregar Nuevo Resultado</a>

                                    <!-- Botón para ver/gestionar resultados -->
                                    <a href="manage-results.php" class="btn btn-info">Ver Resultados</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Fin de acciones -->
                </div>
            </div>
        </div>
    </div>

    <!-- Pie de página -->
    <?php include('includes/footer.php'); ?>
</body>
</html>
