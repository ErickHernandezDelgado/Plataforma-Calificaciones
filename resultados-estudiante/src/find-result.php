<?php
session_start();
//error_reporting(0);
include(__DIR__ . '/includes/config.php');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consulta Calificaciones</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.png">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" media="screen">
    <link rel="stylesheet" href="assets/css/font-awesome.min.css" media="screen">
    <link rel="stylesheet" href="assets/css/animate-css/animate.min.css" media="screen">
    <link rel="stylesheet" href="assets/css/icheck/skins/flat/blue.css">
    <link rel="stylesheet" href="assets/css/main.css" media="screen">
    <script src="assets/js/modernizr/modernizr.min.js"></script>
</head>

<body style="background-image: url(assets/images/IPT_fondo.jpeg); background-color: #ffffff; background-size: cover; background-position: center; background-repeat: no-repeat; height: 100%;">
    <div class="main-wrapper">
        <div class="login-bg-color">
            <div class="row">
                <div class="col-md-4 col-md-offset-7">
                    <div class="panel login-box" style="background: rgb(35, 141, 21);">
                        <div class="panel-heading">
                            <div class="panel-title text-center">
                                <h3 class="text-white">Verifica tus Calificaciones</h3>
                            </div>
                        </div>
                        <div class="panel-body p-20">

                            <?php if (!empty($_SESSION['error'])): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php
                                    echo htmlentities($_SESSION['error']);
                                    unset($_SESSION['error']);
                                    ?>
                                </div>
                            <?php endif; ?>

                            <form action="result.php" method="post" class="admin-login">
                                <div class="form-group">
                                    <label for="emailid" class="control-label">Correo Institucional</label>
                                    <input type="email" class="form-control" id="emailid" name="emailid" placeholder="ejemplo@escuela.edu.mx" required>
                                </div>

                                <div class="form-group">
                                    <label for="class" class="control-label">Grado y Grupo</label>
                                    <select name="class" class="form-control" id="class" required>
                                        <option value="">Selecciona el Grado y Grupo</option>
                                        <?php
                                        $sql = "SELECT * FROM tblclasses";
                                        $query = $dbh->prepare($sql);
                                        $query->execute();
                                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                                        if ($query->rowCount() > 0) {
                                            foreach ($results as $result) {
                                                echo '<option value="' . htmlentities($result->id) . '">' . htmlentities($result->ClassName) . ' - Sección ' . htmlentities($result->Section) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="form-group mt-20">
                                    <button type="submit" class="btn btn-light text-dark">Buscar</button>
                                </div>

                                <div class="col-sm-6">
                                    <a href="index.php" class="text-white">Volver</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- /.panel -->
                </div>
            </div>
        </div>
    </div>

    <!-- JS comunes -->
    <script src="assets/js/jquery/jquery-2.2.4.min.js"></script>
    <script src="assets/js/jquery-ui/jquery-ui.min.js"></script>
    <script src="assets/js/bootstrap/bootstrap.min.js"></script>
    <script src="assets/js/pace/pace.min.js"></script>
    <script src="assets/js/lobipanel/lobipanel.min.js"></script>
    <script src="assets/js/iscroll/iscroll.js"></script>
    <script src="assets/js/icheck/icheck.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        $(function () {
            $('input.flat-blue-style').iCheck({
                checkboxClass: 'icheckbox_flat-blue'
            });
        });
    </script>
</body>

</html>

