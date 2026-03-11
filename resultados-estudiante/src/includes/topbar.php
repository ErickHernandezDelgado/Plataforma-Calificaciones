<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Configuración de codificación y compatibilidad del navegador -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- Vista adaptable a diferentes dispositivos (responsive) -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>Dashboard</title>
    
    <!-- Ícono del sitio web -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.png">a <!-- La 'a' al final parece un error tipográfico -->

    <!-- Archivos CSS requeridos por el sistema -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" media="screen"> <!-- Framework Bootstrap -->
    <link rel="stylesheet" href="assets/css/font-awesome.min.css" media="screen"> <!-- Íconos de FontAwesome -->
    <link rel="stylesheet" href="assets/css/animate-css/animate.min.css" media="screen"> <!-- Animaciones CSS -->
    <link rel="stylesheet" href="assets/css/lobipanel/lobipanel.min.css" media="screen"> <!-- Paneles dinámicos -->
    <link rel="stylesheet" href="assets/css/toastr/toastr.min.css" media="screen"> <!-- Notificaciones -->
    <link rel="stylesheet" href="assets/css/icheck/skins/line/blue.css"> <!-- Estilos para checkboxes (azul) -->
    <link rel="stylesheet" href="assets/css/icheck/skins/line/red.css">  <!-- Estilos para checkboxes (rojo) -->
    <link rel="stylesheet" href="assets/css/icheck/skins/line/green.css"> <!-- Estilos para checkboxes (verde) -->
    <link rel="stylesheet" href="assets/css/main.css" media="screen"> <!-- Hoja de estilos principal del sistema -->
    <link rel="stylesheet" href="assets/css/prism/prism.css" media="screen"> <!-- Sintaxis resaltada para código (Prism) -->

    <!-- Script para detección de funcionalidades del navegador -->
    <script src="assets/js/modernizr/modernizr.min.js"></script>

    <!-- Estilos personalizados para mensajes de error y éxito -->
    <style>
        .errorWrap {
            padding: 10px;
            margin: 0 0 20px 0;
            background: #fff;
            border-left: 4px solid #dd3d36; /* Rojo: error */
            -webkit-box-shadow: 0 1px 1px 0 rgba(0, 0, 0, .1);
            box-shadow: 0 1px 1px 0 rgba(0, 0, 0, .1);
        }

        .succWrap {
            padding: 10px;
            margin: 0 0 20px 0;
            background: #fff;
            border-left: 4px solid #5cb85c; /* Verde: éxito */
            -webkit-box-shadow: 0 1px 1px 0 rgba(0, 0, 0, .1);
            box-shadow: 0 1px 1px 0 rgba(0, 0, 0, .1);
        }
    </style>
</head>

<!-- Elementos base, posiblemente para control visual o loading -->
<div id="page"></div>
<div id="loading"></div>

<body class="top-navbar-fixed">
    <!-- Contenedor principal del sistema -->
    <div class="main-wrapper">
    
        <!-- Barra superior de navegación -->
        <nav class="navbar top-navbar bg-white">
            <div class="container-fluid">
                <div class="row">
                
                    <!-- Sección izquierda de la barra (íconos y botones móviles) -->
                    <div class="navbar-header no-padding">
                        <!-- Botón para mostrar/ocultar el menú lateral (solo en pantallas grandes) -->
                        <span class="small-nav-handle hidden-sm hidden-xs"><i class="fa fa-outdent"></i></span>

                        <!-- Botón para mostrar opciones del navbar en dispositivos móviles -->
                        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse-1" aria-expanded="false">
                            <span class="sr-only">Toggle navigation</span>
                            <i class="fa fa-ellipsis-v"></i>
                        </button>

                        <!-- Botón alternativo para abrir menú lateral en móviles -->
                        <button type="button" class="navbar-toggle mobile-nav-toggle">
                            <i class="fa fa-bars"></i>
                        </button>
                    </div>
                    <!-- /.navbar-header -->

                    <!-- Contenido colapsable del navbar (visible en móviles) -->
                    <div class="collapse navbar-collapse" id="navbar-collapse-1">
                    
                        <!-- Menú izquierdo dentro de la barra superior -->
                        <ul class="nav navbar-nav" data-dropdown-in="fadeIn" data-dropdown-out="fadeOut">
                            <!-- Botón para activar pantalla completa -->
                            <li class="hidden-sm hidden-xs">
                                <a href="#" class="full-screen-handle"><i class="fa fa-arrows-alt"></i></a>
                            </li>

                            <!-- Elemento vacío reservado para futuras funciones (ej: tareas) -->
                            <li class="hidden-xs hidden-xs">
                                <!-- <a href="#">My Tasks</a> -->
                            </li>
                        </ul>

                        <!-- Menú derecho (íconos y opciones de usuario) -->
                        <ul class="nav navbar-nav navbar-right" data-dropdown-in="fadeIn" data-dropdown-out="fadeOut">
                            <!-- Opción para cerrar sesión -->
                            <li>
                                <a href="logout.php" class="color-danger text-center">
                                    <i class="fa fa-sign-out"></i> Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                        <!-- /.navbar-nav navbar-right -->
                    </div>
                    <!-- /.navbar-collapse -->
                </div>
                <!-- /.row -->
            </div>
            <!-- /.container-fluid -->
        </nav>
