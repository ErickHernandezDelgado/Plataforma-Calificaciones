<?php
// Inicia la sesión
session_start();

// Desactiva notificaciones de error (bueno para producción, pero podrías activarlo en desarrollo con error_reporting(E_ALL))
error_reporting(0);

// Incluye archivo de conexión a la base de datos
include('includes/config.php');

// Verifica si se recibió el parámetro 'id' por GET
if (!isset($_GET['id'])) {
    // Termina el script si no se proporciona el ID del alumno
    die("Student ID is required.");
}

// Convierte el parámetro a entero para seguridad
$studentId = intval($_GET['id']);

// ===========================
// Obtener información del alumno
// ===========================
$sql = "SELECT s.StudentName, s.RollId, s.DOB, s.ClassId, s.StudentEmail, s.Gender, s.Status, s.RegDate,
               c.ClassName, c.Section, c.ClassNameNumeric
        FROM tblstudents s
        JOIN tblclasses c ON c.id = s.ClassId
        WHERE s.StudentId = :studentId";
$query = $dbh->prepare($sql);
$query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
$query->execute();
$student = $query->fetch(PDO::FETCH_ASSOC);

// Verifica si se encontró al alumno
if (!$student) {
    die("Student not found."); // SUGERENCIA: Podrías mostrar un mensaje HTML en lugar de terminar el script
}

$classId = $student['ClassId'];

// ===========================
// Obtener materias asignadas a la clase del alumno
// ===========================
$sql = "SELECT sc.SubjectId, sub.SubjectName
        FROM tblsubjectcombination sc
        JOIN tblsubjects sub ON sub.id = sc.SubjectId
        WHERE sc.ClassId = :classId";
$query = $dbh->prepare($sql);
$query->bindParam(':classId', $classId, PDO::PARAM_INT);
$query->execute();
$subjects = $query->fetchAll(PDO::FETCH_ASSOC);

// ===========================
// Obtener calificaciones del alumno
// ===========================
// term representa el periodo (I, II, III)
$sql = "SELECT SubjectId, term, marks FROM tblresult WHERE StudentId = :studentId";
$query = $dbh->prepare($sql);
$query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
$query->execute();
$grades_raw = $query->fetchAll(PDO::FETCH_ASSOC);

// Organiza las calificaciones por materia y periodo en un array multidimensional
$grades = [];
foreach ($grades_raw as $row) {
    $grades[$row['SubjectId']][$row['term']] = $row['marks'];
}

// ===========================
// Obtener nombre del maestro de esa clase
// ===========================
// Solo se obtiene un maestro aunque una clase pueda tener varios por materia
$sql = "SELECT t.TeacherName FROM tblteacher_subject ts
        JOIN tblteachers t ON t.Id = ts.TeacherId
        WHERE ts.ClassId = :classId LIMIT 1";
$query = $dbh->prepare($sql);
$query->bindParam(':classId', $classId, PDO::PARAM_INT);
$query->execute();
$teacher = $query->fetch(PDO::FETCH_ASSOC);
$teacherName = $teacher ? $teacher['TeacherName'] : '__________________'; // Valor por defecto si no hay maestro

// Fecha actual para mostrar en el reporte
$today = date("F j, Y");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Report Card</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; }
        th { background-color: #f0f0f0; }
        .header { text-align: center; font-size: 20px; margin-bottom: 10px; }
        .subheader { text-align: center; font-size: 16px; margin-bottom: 20px; }
        .student-info { margin-bottom: 20px; }
        .student-info td { border: none; text-align: left; }
        .signature { margin-top: 40px; text-align: center; }
        .signature div { display: inline-block; margin: 0 40px; }
        .eval-scale td { text-align: left; }
        @media print {
            body { margin: 0; }
        }
    </style>
</head>
<body>

    <!-- Encabezado principal -->
    <div class="header">PANAMERICAN INSTITUTE</div>
    <div class="subheader">ENGLISH REPORT CARD - ELEMENTARY</div>

    <!-- Información del alumno -->
    <table class="student-info">
        <tr><td><strong>Pupil's Name:</strong> <?php echo htmlentities($student['StudentName']); ?></td></tr>
        <tr><td><strong>Grade:</strong> <?php echo htmlentities($student['ClassName']) . ' ' . htmlentities($student['Section']); ?></td></tr>
    </table>

    <!-- Tabla de materias y calificaciones -->
    <table>
        <tr>
            <th>SUBJECT</th>
            <th>I</th>
            <th>II</th>
            <th>III</th>
            <th>FINAL AVERAGE</th>
        </tr>
        <?php foreach ($subjects as $sub): 
            $sid = $sub['SubjectId'];

            // Obtiene las calificaciones por periodo, si existen
            $term1 = is_numeric($grades[$sid][1] ?? null) ? $grades[$sid][1] : null;
            $term2 = is_numeric($grades[$sid][2] ?? null) ? $grades[$sid][2] : null;
            $term3 = is_numeric($grades[$sid][3] ?? null) ? $grades[$sid][3] : null;

            // Calcula el promedio de los periodos que existan
            $terms = array_filter([$term1, $term2, $term3], fn($v) => $v !== null);
            $avg = count($terms) ? array_sum($terms) / count($terms) : null;

            $avg_display = $avg !== null ? number_format($avg, 2) : '';
        ?>
        <tr>
            <td><?php echo htmlentities($sub['SubjectName']); ?></td>
            <td><?php echo $term1; ?></td>
            <td><?php echo $term2; ?></td>
            <td><?php echo $term3; ?></td>
            <td><?php echo $avg_display; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- Escala de evaluación -->
    <table class="eval-scale">
        <tr><td colspan="2"><strong>Evaluation Scale</strong></td></tr>
        <tr><td>100</td><td>EXCELLENT</td></tr>
        <tr><td>90</td><td>VERY GOOD</td></tr>
        <tr><td>80</td><td>GOOD</td></tr>
        <tr><td>70</td><td>SATISFACTORY</td></tr>
        <tr><td>60</td><td>NEEDS IMPROVEMENT</td></tr>
        <tr><td>&lt;60</td><td>FAILING GRADE</td></tr>
    </table>

    <!-- Firmas -->
    <div class="signature">
        <div>_________________________<br>Teacher: <?php echo htmlentities($teacherName); ?></div>
        <div>_________________________<br>Date: <?php echo $today; ?></div>
    </div>

</body>
</html>
