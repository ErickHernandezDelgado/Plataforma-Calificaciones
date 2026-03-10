<?php
include("conexion.php");

$classId = $_POST['classId'];
$teacherId = $_POST['teacherId'];
$calificaciones = $_POST['calificaciones'];

// Recorremos cada alumno
foreach ($calificaciones as $studentId => $materias) {
    foreach ($materias as $subjectId => $calificacion) {
        if ($calificacion !== '') {
            // Verificamos si ya hay una calificación
            $checkQuery = $conn->prepare("
                SELECT id FROM tblresult 
                WHERE studentId = ? AND classId = ? AND subjectId = ?
            ");
            $checkQuery->execute([$studentId, $classId, $subjectId]);

            if ($checkQuery->rowCount() > 0) {
                // Si ya existe, actualizamos
                $update = $conn->prepare("
                    UPDATE tblresult 
                    SET marks = ?, teacherId = ? 
                    WHERE studentId = ? AND classId = ? AND subjectId = ?
                ");
                $update->execute([$calificacion, $teacherId, $studentId, $classId, $subjectId]);
            } else {
                // Si no existe, insertamos
                $insert = $conn->prepare("
                    INSERT INTO tblresult (studentId, classId, subjectId, marks, teacherId) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $insert->execute([$studentId, $classId, $subjectId, $calificacion, $teacherId]);
            }
        }
    }
}

echo "<script>alert('Calificaciones guardadas correctamente'); window.location.href='calificaciones_grupo.php?classId=$classId&teacherId=$teacherId';</script>";
