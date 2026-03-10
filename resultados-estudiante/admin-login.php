<?php
// Mostrar errores para desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('includes/config.php');

$msg = "";

// Verificar si se envió el formulario de login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = md5($_POST['password']); // ⚠️ Recomendación: Usa password_hash() en producción

    // Buscar al usuario por nombre y contraseña
    $sql = "SELECT id, UserName, Password, Role, teacher_id 
            FROM admin 
            WHERE UserName = :username AND Password = :password 
            LIMIT 1";
    $query = $dbh->prepare($sql);
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->bindParam(':password', $password, PDO::PARAM_STR);
    $query->execute();

    $user = $query->fetch(PDO::FETCH_OBJ);

    if ($user) {
        // Autenticación exitosa
        $_SESSION['alogin'] = $user->UserName;
        $_SESSION['role'] = $user->Role;

        if ($user->Role === 'teacher') {
            // Si es maestro, verificar que tenga asignado un ID
            if (!is_null($user->teacher_id)) {
                $_SESSION['teacherid'] = $user->teacher_id;
                header("Location: dashboard-teacher.php");
                exit;
            } else {
                $msg = "Este usuario maestro no tiene asignado un ID de maestro.";
            }
        } elseif ($user->Role === 'admin') {
            // Si es admin, redirigir al dashboard normal
            header("Location: dashboard.php");
            exit;
        } else {
            $msg = "Rol no válido.";
        }
    } else {
        $msg = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
   <meta charset="utf-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <title>Acceso Admin</title>

   <!-- Estilos -->
   <link rel="stylesheet" href="assets/css/bootstrap.min.css">
   <link rel="stylesheet" href="assets/css/font-awesome.min.css">
   <link rel="stylesheet" href="assets/css/main.css">
</head>

<body style="background-image: url(assets/images/IPT_fondo.jpeg); background-size: cover; background-position: center;">
   <div class="main-wrapper">
      <div class="row justify-content-center">
         <div class="col-md-4 offset-md-4">
            <section class="section mt-5">
               <div class="panel login-box" style="background:rgb(35, 141, 21); border-radius: 10px; padding: 20px;">
                  <div class="panel-heading text-center mb-3">
                     <h5 style="color:white;"><strong>Acceso Administrativo</strong></h5>
                  </div>

                  <div class="panel-body">
                     <!-- Mostrar mensaje de error -->
                     <?php if ($msg): ?>
                         <div class="alert alert-danger" role="alert">
                             <?php echo htmlentities($msg); ?>
                         </div>
                     <?php endif; ?>

                     <!-- Formulario de inicio de sesión -->
                     <form method="post">
                        <div class="form-group">
                           <label for="username">Usuario</label>
                           <input type="text" name="username" class="form-control" placeholder="Nombre de usuario" required>
                        </div>
                        <div class="form-group">
                           <label for="password">Contraseña</label>
                           <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-success btn-block">Acceder</button>
                     </form>
                  </div>
               </div>
            </section>
         </div>
      </div>
   </div>

   <!-- Scripts -->
   <script src="assets/js/jquery/jquery-2.2.4.min.js"></script>
   <script src="assets/js/bootstrap/bootstrap.min.js"></script>
</body>
</html>
