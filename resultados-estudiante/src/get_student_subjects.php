<?php
session_start();
include(__DIR__ . '/includes/config.php');

$classId = $_POST['classid'] ?? null;
$teacherId = $_SESSION['teacherid'] ?? null;
$teacherRole = $_SESSION['role'] ?? null;

if (!$classId) {
    echo "<div class='alert alert-warning'>No se proporcionó un grupo válido.</div>";
    exit;
}

// Obtener estudiantes
$sql = "SELECT StudentId, StudentName FROM tblstudents WHERE ClassId = :classid ORDER BY StudentName";
$query = $dbh->prepare($sql);
$query->bindParam(':classid', $classId, PDO::PARAM_INT);
$query->execute();
$students = $query->fetchAll(PDO::FETCH_ASSOC);

// Obtener única materia del maestro
$subject = null;
if ($teacherRole === 'teacher' && $teacherId) {
    $sql = "SELECT s.id, s.SubjectName FROM tblteacher_subject ts
            JOIN tblsubjects s ON ts.SubjectId = s.id
            WHERE ts.TeacherId = :teacherid LIMIT 1";
    $query = $dbh->prepare($sql);
    $query->bindParam(':teacherid', $teacherId, PDO::PARAM_INT);
    $query->execute();
    $subject = $query->fetch(PDO::FETCH_ASSOC);
}

if (empty($students)) {
    echo "<div class='alert alert-info'>No hay estudiantes en este grupo.</div>";
    exit;
}

if (!$subject) {
    echo "<div class='alert alert-info'>No se encontró materia asignada para este maestro.</div>";
    exit;
}

// Mostrar tabla con estudiantes y un input por calificación
echo "<div class='table-responsive'>";
echo "<table class='table table-bordered'>";
echo "<thead><tr><th>Estudiante</th><th>" . htmlentities($subject['SubjectName']) . "</th></tr></thead><tbody>";

foreach ($students as $stu) {
    echo "<tr>";
    echo "<td>" . htmlentities($stu['StudentName']) . "</td>";
    echo "<td><input type='number' class='form-control' name='marks[" . $stu['StudentId'] . "]' min='0' max='100' step='1' placeholder='Calif.'></td>";
    echo "</tr>";
}

echo "</tbody></table></div>";
?>

