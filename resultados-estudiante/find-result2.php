<?php
session_start();
error_reporting(0);
include('includes/config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['StudentEmail'], FILTER_SANITIZE_EMAIL);
    $classid = filter_var($_POST['class'], FILTER_SANITIZE_NUMBER_INT);

    if (empty($email) || empty($classid)) {
        $_SESSION['error'] = "Por favor ingresa correo y grupo válidos.";
        header("Location: find-result2.php");
        exit;
    }

    $_SESSION['email'] = $email;
    $_SESSION['classid'] = $classid;

    header("Location: result.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
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
<body>

<?php
if (!empty($_SESSION['error'])) {
    echo '<div style="color:red;">' . htmlentities($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}
?>

<form method="POST" action="find-result.php">
    <label>Correo Institucional</label>
    <input type="email" name="StudentEmail" required placeholder="ejemplo@ipt.edu.mx" />

    <label>Grado y Grupo</label>
    <select name="class" required>
        <option value="">Selecciona el Grado y Grupo</option>
        <?php
        $sql = "SELECT * FROM tblclasses";
        $query = $dbh->prepare($sql);
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_OBJ);
        foreach ($results as $result) {
            echo '<option value="' . htmlentities($result->id) . '">' . htmlentities($result->ClassName) . ' - Sección ' . htmlentities($result->Section) . '</option>';
        }
        ?>
    </select>

    <button type="submit">Buscar</button>
</form>
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
