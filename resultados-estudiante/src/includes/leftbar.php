<?php
// Inicia la sesión para poder trabajar con variables de sesión
session_start();

// Verifica que el usuario haya iniciado sesión y que su rol sea 'admin'
if (!isset($_SESSION['alogin']) || $_SESSION['role'] !== 'admin') {
    // Si no es administrador o no ha iniciado sesión, lo redirige al inicio de sesión
    header("Location: ../index.php");
    exit; // Termina la ejecución del script
}
?>

<!-- Menú lateral izquierdo exclusivo para el rol de administrador -->
<div class="left-sidebar box-shadow" style="background-color: #3d85ed;">
    <div class="sidebar-content">
        <div class="sidebar-nav">
            <ul class="side-nav color-gray">

                <!-- Encabezado del panel de administración -->
                <li class="nav-header"><span>Panel de Control</span></li>

                <!-- Acceso al dashboard principal -->
                <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>

                <!-- Encabezado de la sección de configuración -->
                <li class="nav-header"><span>Configuración</span></li>

                <!-- Menú para la gestión de años escolares -->
                <li class="has-children">
                    <a href="#"><i class="fa fa-file-text"></i> <span>Gestión de Años Escolares</span> <i class="fa fa-angle-right arrow"></i></a>
                    <ul class="child-nav">
                        <li><a href="create-class.php"><i class="fa fa-plus"></i> <span>Crear Años</span></a></li>
                        <li><a href="manage-classes.php"><i class="fa fa-bars"></i> <span>Gestionar Años</span></a></li>
                    </ul>
                </li>

                <!-- Menú para la gestión de profesores -->
                <li class="has-children">
                    <a href="#"><i class="fa fa-file-text"></i> <span>Profesores</span> <i class="fa fa-angle-right arrow"></i></a>
                    <ul class="child-nav">
                        <li><a href="add-teacher.php"><i class="fa fa-plus"></i> <span>Agregar Maestro</span></a></li>
                        <li><a href="manage-teacher.php"><i class="fa fa-bars"></i> <span>Gestionar Maestros</span></a></li>
                        <li><a href="assign-teacher-subject.php"><i class="fa fa-link"></i> <span>Asignar a Materias</span></a></li>
                    </ul>
                </li>

                <!-- Menú para la gestión de materias -->
                <li class="has-children">
                    <a href="#"><i class="fa fa-book"></i> <span>Materias</span> <i class="fa fa-angle-right arrow"></i></a>
                    <ul class="child-nav">
                        <li><a href="create-subject.php"><i class="fa fa-plus"></i> <span>Crear Materias</span></a></li>
                        <li><a href="manage-subjects.php"><i class="fa fa-bars"></i> <span>Gestionar Materias</span></a></li>
                        <li><a href="add-subjectcombination.php"><i class="fa fa-plus"></i> <span>Gestionar Relación Materias</span></a></li>
                        <li><a href="manage-subjectcombination.php"><i class="fa fa-bars"></i> <span>Ver Relación de Materias</span></a></li>
                    </ul>
                </li>

                <!-- Menú para la gestión de estudiantes -->
                <li class="has-children">
                    <a href="#"><i class="fa fa-users"></i> <span>Estudiantes</span> <i class="fa fa-angle-right arrow"></i></a>
                    <ul class="child-nav">
                        <li><a href="add-students.php"><i class="fa fa-plus"></i> <span>Agregar Estudiantes</span></a></li>
                        <li><a href="manage-students.php"><i class="fa fa-bars"></i> <span>Gestionar Estudiantes</span></a></li>
                    </ul>
                </li>

                <!-- Menú para subir o gestionar calificaciones -->
                <li class="has-children">
                    <a href="#"><i class="fa fa-file-o"></i> <span>Calificaciones</span> <i class="fa fa-angle-right arrow"></i></a>
                    <ul class="child-nav">
                        <li><a href="add-result2.php"><i class="fa fa-plus"></i> <span>Subir Calificaciones</span></a></li>
                        <li><a href="manage-results.php"><i class="fa fa-bars"></i> <span>Gestionar Calificaciones</span></a></li>
                    </ul>
                </li>

                <!-- Menú para imprimir calificaciones por nivel educativo y idioma -->
                <li class="has-children">
                    <a href="#"><i class="fa fa-file-o"></i> <span>Imprimir Calificaciones</span> <i class="fa fa-angle-right arrow"></i></a>
                    <ul class="child-nav">
                        <li><a href="manage-results-pre-es.php"><i class="fa fa-bars"></i> <span> Preescolar Español</span></a></li>
                        <li><a href="manage-results-pre-in.php"><i class="fa fa-bars"></i> <span>Preescolar Ingles</span></a></li>
                        <li><a href="manage-results-pe.php"><i class="fa fa-bars"></i> <span> Primaria Español</span></a></li>
                        <li><a href="manage-results-pi.php"><i class="fa fa-bars"></i> <span>Primaria Ingles</span></a></li>
                        <li><a href="manage-results-sec.php"><i class="fa fa-bars"></i> <span>Secundaria</span></a></li>
                    </ul>
                </li>

                <!-- Menú para la gestión de comunicados -->
                <li class="has-children">
                    <a href="#"><i class="fa fa-bell"></i> <span>Comunicados</span> <i class="fa fa-angle-right arrow"></i></a>
                    <ul class="child-nav">
                        <li><a href="add-notice.php"><i class="fa fa-plus"></i> <span>Agregar Comunicado</span></a></li>
                        <li><a href="manage-notices.php"><i class="fa fa-bars"></i> <span>Gestionar Comunicado</span></a></li>
                    </ul>
                </li>

                <!-- Enlace para añadir nuevos usuarios al sistema -->
                <li><a href="add-user.php"><i class="fa fa-key"></i> <span>Añadir Usuarios</span></a></li>
            </ul>
        </div>
    </div>
</div>
