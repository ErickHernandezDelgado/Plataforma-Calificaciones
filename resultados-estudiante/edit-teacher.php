<?php
session_start();
error_reporting(0);
include('includes/config.php');

if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php");
} else {
    if (isset($_POST['update'])) {
        $tid = intval($_GET['tid']);
        $name = $_POST['teachername'];
        $email = $_POST['teacheremail'];
        $gender = $_POST['teachergender'];
        $status = isset($_POST['status']) ? 1 : 0;

        $sql = "UPDATE tblteachers SET TeacherName = :name, TeacherEmail = :email, TeacherGender = :gender, Status = :status WHERE Id = :tid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':name', $name, PDO::PARAM_STR);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->bindParam(':gender', $gender, PDO::PARAM_STR);
        $query->bindParam(':status', $status, PDO::PARAM_INT);
        $query->bindParam(':tid', $tid, PDO::PARAM_INT);
        $query->execute();

        $msg = "Información del maestro actualizada exitosamente.";
    }
?>

<?php include('includes/topbar.php'); ?>
<div class="content-wrapper">
    <div class="content-container">
        <?php include('includes/leftbar.php'); ?>

        <div class="main-page">
            <div class="container-fluid">
                <div class="row page-title-div">
                    <div class="col-md-6">
                        <h2 class="title">Editar Maestro</h2>
                    </div>
                </div>

                <section class="section">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-10 col-md-offset-1">
                                <div class="panel">
                                    <div class="panel-heading">
                                        <div class="panel-title">
                                            <h5>Actualizar información del maestro</h5>
                                        </div>
                                    </div>

                                    <?php if ($msg) { ?>
                                        <div class="alert alert-success" role="alert">
                                            <strong>Hecho!</strong> <?php echo htmlentities($msg); ?>
                                        </div>
                                    <?php } ?>

                                    <div class="panel-body">
                                        <?php
                                        $tid = intval($_GET['tid']);
                                        $sql = "SELECT * FROM tblteachers WHERE Id = :tid";
                                        $query = $dbh->prepare($sql);
                                        $query->bindParam(':tid', $tid, PDO::PARAM_INT);
                                        $query->execute();
                                        $result = $query->fetch(PDO::FETCH_OBJ);
                                        ?>

                                        <form method="post">
                                            <div class="form-group">
                                                <label for="teachername">Nombre del Maestro</label>
                                                <input type="text" name="teachername" class="form-control" value="<?php echo htmlentities($result->TeacherName); ?>" required>
                                            </div>

                                            <div class="form-group">
                                                <label for="teacheremail">Correo Electrónico</label>
                                                <input type="email" name="teacheremail" class="form-control" value="<?php echo htmlentities($result->TeacherEmail); ?>" required>
                                            </div>

                                            <div class="form-group">
                                                <label for="teachergender">Género</label>
                                                <select name="teachergender" class="form-control" required>
                                                    <option value="Masculino" <?php if ($result->TeacherGender == "Masculino") echo "selected"; ?>>Masculino</option>
                                                    <option value="Femenino" <?php if ($result->TeacherGender == "Femenino") echo "selected"; ?>>Femenino</option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="joiningdate">Fecha de Ingreso</label>
                                                <input type="text" class="form-control" value="<?php echo htmlentities($result->JoiningDate); ?>" disabled>
                                            </div>

                                            <div class="form-group">
                                                <label for="status">Estado</label><br>
                                                <input type="checkbox" name="status" <?php if ($result->Status == 1) echo "checked"; ?>> Activo
                                            </div>

                                            <button type="submit" name="update" class="btn btn-primary">Actualizar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
<?php include('includes/footer.php'); ?>
<?php } ?>
