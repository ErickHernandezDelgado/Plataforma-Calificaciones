<?php
error_reporting(0); // Suprime los errores para no mostrarlos al usuario
include('includes/config.php'); // Incluye la configuración y conexión a la base de datos
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Configuración del encabezado -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>UP-Result Software by Mayuri K.</title>

    <!-- Ícono del navegador -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />

    <!-- Estilos principales -->
    <link href="assets/css/styles.css" rel="stylesheet" />
</head>
<body>

<!-- Barra de navegación -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">SRMS-(Student Result Management System)</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" 
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Enlaces del menú -->
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="#!">Home</a></li>
                <li class="nav-item"><a class="nav-link active" href="find-result.php">Students</a></li>
                <li class="nav-item"><a class="nav-link active" href="admin-login.php">Admin</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Sección de contenido principal -->
<section class="py-5">
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <?php 
                // Obtiene el ID del aviso desde la URL
                $noticeid = $_GET['nid'];

                // Consulta para obtener los detalles del aviso
                $sql = "SELECT * FROM tblnotice WHERE id = '$noticeid'";
                $query = $dbh->prepare($sql);
                $query->execute();
                $results = $query->fetchAll(PDO::FETCH_OBJ);

                // Verifica si hay resultados
                if ($query->rowCount() > 0) {
                    foreach ($results as $result) { 
                ?>

                <!-- Título y fecha del aviso -->
                <h3><?php echo htmlentities($result->noticeTitle); ?></h3>
                <p><strong>Fecha de Publicación:</strong> <?php echo htmlentities($result->postingDate); ?></p>
                <hr color="#000" />

                <!-- Cuerpo del aviso -->
                <p><?php echo htmlentities($result->noticeDetails); ?></p>

                <?php 
                    } // Fin del foreach
                } // Fin del if
                ?>

            </div>
        </div>
    </div>
</section>

<!-- Pie de página -->
<footer class="py-5 bg-dark">
    <div class="container">
        <p class="m-0 text-center text-white">
            Copyright &copy; Student Result Management System <?php echo date('Y'); ?>
        </p>
    </div>
</footer>

<!-- Scripts de Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Scripts personalizados -->
<script src="assets/js/scripts.js"></script>

</body>
</html>
