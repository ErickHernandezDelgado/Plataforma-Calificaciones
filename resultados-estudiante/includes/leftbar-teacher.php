<?php
// Inicia la sesión para poder acceder a variables de sesión
session_start();

// Verifica si la sesión no está activa o si el usuario no es un maestro
if (!isset($_SESSION['alogin']) || $_SESSION['role'] !== 'teacher') {
    // Si no cumple con los requisitos, redirige al usuario al índice (inicio de sesión)
    header("Location: ../index.php");
    exit; // Finaliza la ejecución del script
}
?>

<!-- Panel lateral izquierdo del maestro -->
<div class="left-sidebar box-shadow" style="background-color: #3d85ed;">
    <div class="sidebar-content">
        <div class="sidebar-nav">
            <ul class="side-nav color-gray">
                <!-- Encabezado de la sección de configuración -->
                <li class="nav-header"><span>Configuración</span></li>

                <!-- Menú con subelementos para la gestión de resultados -->
                <li class="has-children">
                    <!-- Título principal de la sección -->
                    <a href="#"><i class="fa fa-file-o"></i> <span>Resultados</span> <i class="fa fa-angle-right arrow"></i></a>
                    <ul class="child-nav">
                        <!-- Enlace para agregar nuevos resultados -->
                        <li><a href="add-result.php"><i class="fa fa-plus"></i> <span>Agregar Resultado</span></a></li>
                        <!-- Enlace para ver o modificar resultados existentes -->
                        <li><a href="manage-results.php"><i class="fa fa-bars"></i> <span>Gestionar Resultados</span></a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
