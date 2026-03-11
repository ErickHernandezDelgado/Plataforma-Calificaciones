<?php
session_start();
error_reporting(0);
include(__DIR__ . '/includes/config.php');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Boleta de Calificaciones</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            margin: 30px;
        }

        .boleta {
            max-width: 1000px;
            margin: auto;
            padding: 20px;
            border: 1px solid #000;
        }

        .header {
            text-align: center;
        }

        .header h2, .header h3 {
            margin: 2px;
        }

        .student-info {
            margin-top: 20px;
        }

        .student-info td {
            padding: 4px 8px;
        }

        table.grades {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .grades th, .grades td {
            border: 1px solid #000;
            text-align: center;
            padding: 5px;
        }

        .scale {
            margin-top: 30px;
        }

        .footer-table {
            width: 100%;
            margin-top: 30px;
        }

        .footer-table td {
            padding-top: 30px;
            text-align: center;
        }

        .print-button {
            margin: 20px auto;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="boleta">
        <div class="header">
            <h2>MINISTERIO DE EDUCACIÓN</h2>
            <h3>MINISTRY OF EDUCATION</h3>
            <h3>BOLETA DE CALIFICACIONES / GRADE REPORT</h3>
        </div>

        <?php
        $rollid = $_POST['rollid'];
        $classid = $_POST['class'];
        $_SESSION['rollid'] = $rollid;
        $_SESSION['classid'] = $classid;

        $stmt = $dbh->prepare("SELECT tblstudents.StudentName, tblstudents.RollId, tblclasses.ClassName, tblclasses.Section 
                               FROM tblstudents 
                               JOIN tblclasses ON tblclasses.id = tblstudents.ClassId 
                               WHERE tblstudents.RollId = :rollid AND tblstudents.ClassId = :classid");
        $stmt->bindParam(':rollid', $rollid);
        $stmt->bindParam(':classid', $classid);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_OBJ);
        ?>

        <table class="student-info">
            <tr>
                <td><b>Nombre del Estudiante / Student Name:</b> <?php echo htmlentities($student->StudentName); ?></td>
                <td><b>Grado / Grade:</b> <?php echo htmlentities($student->ClassName); ?></td>
            </tr>
            <tr>
                <td><b>Sección / Section:</b> <?php echo htmlentities($student->Section); ?></td>
                <td><b>Ciclo Escolar / School Year:</b> 2025</td>
            </tr>
        </table>

        <table class="grades">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Asignatura / Subject</th>
                    <th>I Periodo<br>(1st Term)</th>
                    <th>II Periodo<br>(2nd Term)</th>
                    <th>III Periodo<br>(3rd Term)</th>
                    <th>Promedio Final<br>Final Average</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT subj.SubjectName, subj.id AS SubjectId,
                        MAX(CASE WHEN r.term = 1 THEN r.marks END) AS term1,
                        MAX(CASE WHEN r.term = 2 THEN r.marks END) AS term2,
                        MAX(CASE WHEN r.term = 3 THEN r.marks END) AS term3
                        FROM tblresult r
                        JOIN tblsubjects subj ON subj.id = r.SubjectId
                        WHERE r.StudentId = (SELECT StudentId FROM tblstudents WHERE RollId = :rollid AND ClassId = :classid)
                        GROUP BY subj.SubjectName, subj.id";
                $query = $dbh->prepare($sql);
                $query->bindParam(':rollid', $rollid);
                $query->bindParam(':classid', $classid);
                $query->execute();
                $results = $query->fetchAll(PDO::FETCH_OBJ);
                $cnt = 1;
                foreach ($results as $row) {
                    $avg = round(($row->term1 + $row->term2 + $row->term3) / 3, 0);
                    echo "<tr>
                        <td>{$cnt}</td>
                        <td>{$row->SubjectName}</td>
                        <td>{$row->term1}</td>
                        <td>{$row->term2}</td>
                        <td>{$row->term3}</td>
                        <td>{$avg}</td>
                    </tr>";
                    $cnt++;
                }
                ?>
            </tbody>
        </table>

        <div class="scale">
            <b>Escala de Evaluación / Evaluation Scale:</b>
            <p>90-100 Excelente / Excellent<br>
                80-89 Muy Bueno / Very Good<br>
                70-79 Bueno / Good<br>
                60-69 Satisfactorio / Satisfactory<br>
                0-59 Insuficiente / Insufficient</p>
        </div>

        <table class="footer-table">
            <tr>
                <td>_________________________<br>Firma del Docente / Teacher's Signature</td>
                <td>_________________________<br>Firma del Director / Principal's Signature</td>
            </tr>
        </table>
    </div>

    <div class="print-button">
        <button onclick="window.print()">Imprimir / Print</button>
    </div>
</body>

</html>

