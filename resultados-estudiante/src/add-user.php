<?php
session_start();
error_reporting(0);
include(__DIR__ . '/includes/config.php');

// Procesar formulario cuando se envía
if (isset($_POST['submit'])) {
    $username = $_POST['username'];                            // Email del usuario
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Contraseña segura
    $role = $_POST['role'];                                    // Rol: admin o teacher
    $teacher_id = $role === 'teacher' ? $_POST['teacher_id'] : null; // ID del maestro si es teacher

    // Verificar si el usuario ya existe
    $check = $dbh->prepare("SELECT id FROM admin WHERE UserName = :username");
    $check->bindParam(':username', $username, PDO::PARAM_STR);
    $check->execute();

    if ($check->rowCount() > 0) {
        $error = "El nombre de usuario ya está registrado.";
    } else {
        // Insertar usuario nuevo
        $sql = "INSERT INTO admin (UserName, Password, role, teacher_id) VALUES (:username, :password, :role, :teacher_id)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':username', $username, PDO::PARAM_STR);
        $query->bindParam(':password', $password, PDO::PARAM_STR);
        $query->bindParam(':role', $role, PDO::PARAM_STR);
        $query->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);

        if ($query->execute()) {
            $msg = "Usuario creado correctamente.";
        } else {
            $error = "Error al crear el usuario.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agregar Usuario</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .footer {
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            background-color: #3d85ed;
            color: white;
            text-align: center;
            padding: 10px 0;
        }
    </style>
</head>
<body>

<!-- TOP NAVBAR -->
<?php include('includes/topbar.php'); ?>

<div class="content-wrapper">
    <div class="content-container">

        <!-- LEFT SIDEBAR -->
        <?php include('includes/leftbar.php'); ?>

        <!-- MAIN PAGE -->
        <div class="main-page">
            <div class="container-fluid">
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Agregar Usuario</h2>
                    </div>
                </div>

                <!-- FORM SECTION -->
                <section class="section">
                    <div class="container-fluid">
                        <?php if ($msg) { ?>
                            <div class="alert alert-success" role="alert">
                                <strong>¡Bien hecho!</strong> <?php echo htmlentities($msg); ?>
                            </div>
                        <?php } elseif ($error) { ?>
                            <div class="alert alert-danger" role="alert">
                                <strong>Error:</strong> <?php echo htmlentities($error); ?>
                            </div>
                        <?php } ?>

                        <!-- FORMULARIO -->
                        <form method="post">
                            <div class="form-group col-md-6">
                                <label for="username">Nombre de Usuario (correo)</label>
                                <input type="email" name="username" class="form-control" id="username" required autocomplete="off">
                            </div>

                            <div class="form-group col-md-6">
                                <label for="password">Contraseña</label>
                                <input type="password" name="password" class="form-control" id="password" required autocomplete="off">
                            </div>

                            <div class="form-group col-md-6">
                                <label for="role">Rol</label>
                                <select name="role" id="role" class="form-control" required onchange="toggleTeacherId()">
                                    <option value="">-- Selecciona Rol --</option>
                                    <option value="admin">Administrador</option>
                                    <option value="teacher">Maestro</option>
                                </select>
                            </div>

                            <div id="teacherSelect" style="display:none;" class="form-group col-md-6">
                                <label for="teacher_id">Selecciona Maestro</label>
                                <select name="teacher_id" id="teacher_id" class="form-control">
                                    <option value="">-- Selecciona --</option>
                                    <?php
                                    $stmt = $dbh->query("SELECT TeacherId, TeacherName FROM tblteachers");
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<option value='" . $row['TeacherId'] . "'>" . htmlentities($row['TeacherName']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group col-md-12">
                                <button type="submit" name="submit" class="btn btn-primary">Crear Usuario</button>
                            </div>
                        </form>
                    </div>
                </section>

            </div>
        </div>
    </div>
</div>

<!-- FOOTER -->
<div class="footer">
    <p>&copy; 2025 Todos los derechos reservados.</p>
</div>

<!-- SCRIPTS -->
<script>
    function toggleTeacherId() {
        const role = document.getElementById('role').value;
        const teacherSelect = document.getElementById('teacherSelect');
        teacherSelect.style.display = (role === 'teacher') ? 'block' : 'none';
    }

    // Mostrar campo maestro si ya estaba seleccionado al recargar
    window.addEventListener('DOMContentLoaded', () => {
        toggleTeacherId();
    });
</script>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
