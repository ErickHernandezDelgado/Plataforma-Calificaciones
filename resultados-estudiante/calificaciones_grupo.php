<?php
include("/includes/config.php"); 

$classId = $_GET['classId']; // ID del grupo seleccionado
$teacherId = $_GET['teacherId']; // ID del maestro autenticado

// Obtener estudiantes del grupo
$studentsQuery = $conn->prepare("SELECT id, studentName FROM tblstudents WHERE classId = ?");
$studentsQuery->execute([$classId]);
$students = $studentsQuery->fetchAll(PDO::FETCH_ASSOC);

// Obtener materias del grupo
$subjectsQuery = $conn->prepare("
    SELECT s.id, s.subjectName 
    FROM tblsubjectcombination sc
    JOIN tblsubjects s ON sc.subjectId = s.id
    WHERE sc.classId = ?
");
$subjectsQuery->execute([$classId]);
$subjects = $subjectsQuery->fetchAll(PDO::FETCH_ASSOC);

// Obtener materias que el maestro imparte
$teacherSubjectsQuery = $conn->prepare("
    SELECT subjectId FROM tblteacher_subject WHERE teacherId = ?
");
$teacherSubjectsQuery->execute([$teacherId]);
$teacherSubjects = $teacherSubjectsQuery->fetchAll(PDO::FETCH_COLUMN); // Solo los IDs

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Calificaciones del grupo</title>
  <style>
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    input[disabled] { background-color: #eee; }
  </style>
</head>
<body>
  <h2>Calificaciones del grupo</h2>

  <form action="guardar_calificaciones.php" method="post">
    <input type="hidden" name="classId" value="<?= $classId ?>">
    <input type="hidden" name="teacherId" value="<?= $teacherId ?>">

    <table>
      <thead>
        <tr>
          <th>Alumno</th>
          <?php foreach ($subjects as $subject): ?>
            <th><?= htmlspecialchars($subject['subjectName']) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $student): ?>
          <tr>
            <td><?= htmlspecialchars($student['studentName']) ?></td>
            <?php foreach ($subjects as $subject): 
              $editable = in_array($subject['id'], $teacherSubjects);
              ?>
              <td>
                <input 
                  type="number" 
                  name="calificaciones[<?= $student['id'] ?>][<?= $subject['id'] ?>]" 
                  min="0" max="10"
                  <?= $editable ? '' : 'disabled' ?>
                >
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <br>
    <button type="submit">Guardar todo</button>
  </form>
</body>
</html>
