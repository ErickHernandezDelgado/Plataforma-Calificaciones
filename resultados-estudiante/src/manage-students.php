<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include(__DIR__ . '/includes/config.php');

if (!isset($_SESSION['alogin'])) {
    header("Location: index.php");
    exit;
}

$page_title = "Gestionar Estudiantes";
$students = [];
$search = $_GET['search'] ?? '';

try {
    $sql = "SELECT s.StudentId, s.StudentName, s.StudentEmail, s.CURP, c.ClassName, c.Section, s.Status, 
                   GROUP_CONCAT(CONCAT(st.RelationshipType, ': ', a.UserName) SEPARATOR ', ') as tutors
            FROM tblstudents s
            LEFT JOIN tblclasses c ON s.ClassId = c.id
            LEFT JOIN student_tutor st ON s.StudentId = st.StudentId
            LEFT JOIN admin a ON st.TutorId = a.id
            WHERE 1=1";

    if ($search) {
        $sql .= " AND (s.StudentName LIKE :search OR s.StudentEmail LIKE :search OR s.CURP LIKE :search)";
    }

    $sql .= " GROUP BY s.StudentId ORDER BY s.StudentName";

    $query = $dbh->prepare($sql);
    
    if ($search) {
        $search_term = "%$search%";
        $query->bindParam(':search', $search_term, PDO::PARAM_STR);
    }
    
    $query->execute();
    $students = $query->fetchAll(PDO::FETCH_OBJ);
} catch (Exception $e) {
    error_log("Error fetching students: " . $e->getMessage());
}

?>
<?php include('includes/header.php'); ?>

<div>
    <h1 style="color: #333; margin-bottom: 1.5rem;">
        <i class="fas fa-users"></i> Gestionar Estudiantes
    </h1>

    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-body">
            <form method="get" style="display: flex; gap: 1rem; align-items: flex-end;">
                <div style="flex: 1;">
                    <label for="search">Buscar Estudiante</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        class="form-control" 
                        placeholder="Nombre, email o CURP"
                        value="<?php echo htmlentities($search); ?>"
                    >
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <a href="manage-students.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Limpiar
                </a>
                <a href="add-students.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Agregar
                </a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Total: <?php echo count($students); ?> estudiantes
        </div>
        <div class="card-body">
            <?php if (count($students) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="table" style="font-size: 0.9rem;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>CURP</th>
                                <th>Año</th>
                                <th>Tutor(es)</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $idx => $student): ?>
                                <tr>
                                    <td><?php echo $idx + 1; ?></td>
                                    <td><strong><?php echo htmlentities($student->StudentName); ?></strong></td>
                                    <td><?php echo htmlentities($student->StudentEmail); ?></td>
                                    <td><?php echo htmlentities($student->CURP ?? '-'); ?></td>
                                    <td><?php echo htmlentities($student->ClassName . ' ' . $student->Section); ?></td>
                                    <td style="font-size: 0.85rem;"><?php echo htmlentities($student->tutors ?? '-'); ?></td>
                                    <td>
                                        <span class="badge" style="padding: 0.3rem 0.6rem; border-radius: 4px; 
                                            background: <?php echo $student->Status == 1 ? '#28a745' : '#dc3545'; ?>;
                                            color: white; font-size: 0.85rem;">
                                            <?php echo $student->Status == 1 ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit-student.php?stid=<?php echo $student->StudentId; ?>" class="btn btn-info" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem;">
                    <p style="color: #666;">
                        <i class="fas fa-inbox" style="font-size: 2rem; display: block; margin-bottom: 1rem; opacity: 0.5;"></i>
                        No hay estudiantes que mostrar
                    </p>
                    <a href="add-students.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Agregar Primer Estudiante
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>
