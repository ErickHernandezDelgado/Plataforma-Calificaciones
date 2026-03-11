<?php
// Archivo para procesar y guardar calificaciones desde un formulario

// Incluir conexión a la base de datos
include('db_connection.php');

// Verificar que se hayan enviado calificaciones vía POST
if (isset($_POST['marks'])) {
    // Recorrer array de calificaciones: $_POST['marks'][student_id][subject_id] = mark
    foreach ($_POST['marks'] as $student_id => $marks) {
        foreach ($marks as $subject_id => $mark) {
            // Validar que la calificación no esté vacía
            if (!empty($mark)) {
                // Sanitizar entradas para evitar inyección SQL
                $student_id = mysqli_real_escape_string($con, $student_id);
                $subject_id = mysqli_real_escape_string($con, $subject_id);
                $mark = mysqli_real_escape_string($con, $mark);

                // Consultar si ya existe calificación para ese estudiante y materia
                $query_check = "SELECT * FROM tblresult 
                                WHERE StudentId = '$student_id' AND SubjectId = '$subject_id'";
                $result_check = mysqli_query($con, $query_check);

                if ($result_check === false) {
                    // Manejo básico de error en consulta
                    die("Error en consulta: " . mysqli_error($con));
                }

                if (mysqli_num_rows($result_check) > 0) {
                    // Si existe, actualizar la calificación
                    $query_update = "UPDATE tblresult 
                                     SET Marks = '$mark' 
                                     WHERE StudentId = '$student_id' AND SubjectId = '$subject_id'";
                    $res_update = mysqli_query($con, $query_update);
                    if ($res_update === false) {
                        die("Error al actualizar calificación: " . mysqli_error($con));
                    }
                } else {
                    // Si no existe, insertar nueva calificación
                    $query_insert = "INSERT INTO tblresult (StudentId, SubjectId, Marks) 
                                     VALUES ('$student_id', '$subject_id', '$mark')";
                    $res_insert = mysqli_query($con, $query_insert);
                    if ($res_insert === false) {
                        die("Error al insertar calificación: " . mysqli_error($con));
                    }
                }
            }
        }
    }
    // Mensaje de éxito al finalizar el procesamiento
    echo "Calificaciones guardadas correctamente.";
} else {
    echo "No se recibieron calificaciones para procesar.";
}
?>
