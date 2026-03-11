<?php
// Inicia sesión y oculta errores (no recomendable en desarrollo)
session_start();
error_reporting(0);

// Incluye archivo de conexión a la base de datos
include(__DIR__ . '/includes/config.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Configuraciones meta y enlaces a estilos CSS -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resultados Estudiante</title>

    <!-- Librerías CSS (Bootstrap, fuentes, animaciones, etc.) -->
    <link rel="stylesheet" href="css/bootstrap.min.css" media="screen">
    <link rel="stylesheet" href="css/font-awesome.min.css" media="screen">
    <link rel="stylesheet" href="css/animate-css/animate.min.css" media="screen">
    <link rel="stylesheet" href="css/lobipanel/lobipanel.min.css" media="screen">
    <link rel="stylesheet" href="css/prism/prism.css" media="screen">
    <link rel="stylesheet" href="css/main.css" media="screen">
    <script src="js/modernizr/modernizr.min.js"></script>
    <link rel="stylesheet" href="./assets/css/resultados/style.css"> <!-- Estilo personalizado -->
</head>

<body>
    <div class="main-wrapper">
        <div class="content-wrapper">
            <div class="content-container">

                <!-- Título principal -->
                <div class="main-page">
                    <div class="container-fluid">
                        <h1><span class="blue">&lt;</span>Resultados<span class="blue">&gt;</span> <span class="yellow">Estudiante</span></h1>
                    </div>

                    <!-- Contenedor de resultados -->
                    <section class="section" id="exampl">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-md-8 col-md-offset-2">
                                    <div class="panel">
                                        <div class="panel-heading">
                                            <div class="panel-title">
                                                <hr />
                                                <?php
                                                // Se obtienen email y clase desde formulario POST
                                                $email = $_POST['email'];
                                                $classid = $_POST['class'];
                                                $_SESSION['email'] = $email;
                                                $_SESSION['classid'] = $classid;

                                                // Consulta para obtener la información del estudiante
                                                $query = "SELECT tblstudents.StudentName, tblstudents.StudentEmail, tblstudents.RegDate, tblstudents.StudentId, tblstudents.Status, tblclasses.ClassName, tblclasses.Section 
                                                          FROM tblstudents 
                                                          JOIN tblclasses ON tblclasses.id=tblstudents.ClassId 
                                                          WHERE tblstudents.StudentEmail=:email AND tblstudents.ClassId=:classid";
                                                $stmt = $dbh->prepare($query);
                                                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                                                $stmt->bindParam(':classid', $classid, PDO::PARAM_STR);
                                                $stmt->execute();
                                                $resultss = $stmt->fetchAll(PDO::FETCH_OBJ);

                                                if ($stmt->rowCount() > 0) {
                                                    foreach ($resultss as $row) {
                                                        ?>
                                                        <!-- Muestra los datos del estudiante -->
                                                        <p><b>Nombre de Estudiante:</b> <?php echo htmlentities($row->StudentName); ?></p>
                                                        <p><b>Email:</b> <?php echo htmlentities($row->StudentEmail); ?></p>
                                                        <p><b>Año Lectivo:</b> <?php echo htmlentities($row->ClassName); ?> (<?php echo htmlentities($row->Section); ?>)</p>
                                                <?php
                                                    }
                                                ?>
                                            </div>

                                            <!-- Tabla de resultados -->
                                            <div class="panel-body p-20">
                                                <table class="table table-hover table-bordered" border="1" width="100%">
                                                    <thead>
                                                        <tr style="text-align: center">
                                                            <th>#</th>
                                                            <th>Materia</th>
                                                            <th>Calificaciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        // Consulta para obtener las calificaciones del estudiante
                                                        $query = "SELECT t.StudentName, t.StudentEmail, t.ClassId, t.marks, SubjectId, tblsubjects.SubjectName 
                                                                  FROM (
                                                                    SELECT sts.StudentName, sts.StudentEmail, sts.ClassId, tr.marks, SubjectId 
                                                                    FROM tblstudents AS sts 
                                                                    JOIN tblresult AS tr ON tr.StudentId=sts.StudentId
                                                                  ) AS t 
                                                                  JOIN tblsubjects ON tblsubjects.id=t.SubjectId 
                                                                  WHERE (t.StudentEmail=:email AND t.ClassId=:classid)";
                                                        $query = $dbh->prepare($query);
                                                        $query->bindParam(':email', $email, PDO::PARAM_STR);
                                                        $query->bindParam(':classid', $classid, PDO::PARAM_STR);
                                                        $query->execute();
                                                        $results = $query->fetchAll(PDO::FETCH_OBJ);

                                                        $cnt = 1;
                                                        $totlcount = 0;

                                                        if ($query->rowCount() > 0) {
                                                            foreach ($results as $result) {
                                                                ?>
                                                                <tr>
                                                                    <th scope="row" style="text-align: center"><?php echo htmlentities($cnt); ?></th>
                                                                    <td style="text-align: center"><?php echo htmlentities($result->SubjectName); ?></td>
                                                                    <td style="text-align: center"><?php echo htmlentities($totalmarks = $result->marks); ?></td>
                                                                </tr>
                                                                <?php
                                                                $totlcount += $totalmarks;
                                                                $cnt++;
                                                            }

                                                            // Cálculo total y porcentaje
                                                            $outof = ($cnt - 1) * 100;
                                                        ?>
                                                            <tr>
                                                                <th colspan="2" style="text-align: center">Total</th>
                                                                <td style="text-align: center"><b><?php echo htmlentities($totlcount); ?></b> de <b><?php echo htmlentities($outof); ?></b></td>
                                                            </tr>
                                                            <tr>
                                                                <th colspan="2" style="text-align: center">Porcentaje</th>
                                                                <td style="text-align: center"><b><?php echo htmlentities(round($totlcount * 100 / $outof, 2)); ?> %</b></td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="3" align="center">
                                                                    <!-- Botón para imprimir -->
                                                                    <i class="fa fa-print fa-2x" aria-hidden="true" style="cursor:pointer" OnClick="CallPrint(this.value)"></i>
                                                                </td>
                                                            </tr>
                                                        <?php
                                                        } else {
                                                            // Si no hay resultados encontrados
                                                            ?>
                                                            <div class="alert alert-warning left-icon-alert" role="alert">
                                                                <strong>Importante!</strong> Aun no se han declarado tus resultados
                                                            </div>
                                                        <?php
                                                        }
                                                    } else {
                                                        // Si no se encuentra el estudiante
                                                        ?>
                                                        <div class="alert alert-danger left-icon-alert" role="alert">
                                                            <strong>Hubo inconvenientes!</strong> Email inválido o estudiante no encontrado.
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Enlace para volver -->
                                    <div class="form-group">
                                        <div class="col-sm-6">
                                            <a href="index.php" style="color:white;">Volver</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts JS -->
    <script src="js/jquery/jquery-2.2.4.min.js"></script>
    <script src="js/bootstrap/bootstrap.min.js"></script>

    <!-- Función para imprimir los resultados -->
    <script>
        function CallPrint(strid) {
            var prtContent = document.getElementById("exampl");
            var WinPrint = window.open('', '', 'left=0,top=0,width=800,height=900,toolbar=0,scrollbars=0,status=0');
            WinPrint.document.write(prtContent.innerHTML);
            WinPrint.document.close();
            WinPrint.focus();
            WinPrint.print();
            WinPrint.close();
        }
    </script>
</body>

</html>

