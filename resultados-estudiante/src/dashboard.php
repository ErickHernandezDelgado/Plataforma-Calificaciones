<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include(__DIR__ . '/includes/config.php');

// Verificar seguridad
if (!isset($_SESSION['alogin']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$page_title = "Dashboard - Admin";

// Obtener estadísticas
$totalStudents = 0;
$totalTeachers = 0;
$totalSubjects = 0;
$totalClasses = 0;
$totalTutors = 0;
$totalResults = 0;

try {
    $queries = [
        'students' => "SELECT COUNT(*) as cnt FROM tblstudents WHERE Status = 1",
        'teachers' => "SELECT COUNT(*) as cnt FROM tblteachers WHERE Status = 1",
        'subjects' => "SELECT COUNT(*) as cnt FROM tblsubjects",
        'classes' => "SELECT COUNT(*) as cnt FROM tblclasses",
        'tutors' => "SELECT COUNT(DISTINCT TutorId) as cnt FROM student_tutor",
        'results' => "SELECT COUNT(DISTINCT StudentId) as cnt FROM tblresult"
    ];

    foreach ($queries as $key => $sql) {
        $q = $dbh->prepare($sql);
        $q->execute();
        $r = $q->fetch(PDO::FETCH_OBJ);
        ${'total' . ucfirst($key)} = $r->cnt ?? 0;
    }
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
}

?>
<?php include('includes/header.php'); ?>

<div style="padding: 0;">
    <!-- Encabezado de la página -->
    <div style="margin-bottom: 2rem;">
        <h1 style="color: #333; margin: 0 0 0.5rem 0;">
            <i class="fas fa-chart-line"></i> Dashboard
        </h1>
        <p style="color: #666; margin: 0;">Panel de administración general</p>
    </div>

    <!-- Grid de Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card" onclick="location.href='manage-students.php'">
            <div class="stat-number">👨‍🎓 <?php echo $totalStudents; ?></div>
            <p class="stat-label">Estudiantes Activos</p>
        </div>

        <div class="stat-card success" onclick="location.href='manage-teacher.php'">
            <div class="stat-number">👨‍🏫 <?php echo $totalTeachers; ?></div>
            <p class="stat-label">Docentes Activos</p>
        </div>

        <div class="stat-card warning" onclick="location.href='manage-subjects.php'">
            <div class="stat-number">📚 <?php echo $totalSubjects; ?></div>
            <p class="stat-label">Materias Registradas</p>
        </div>

        <div class="stat-card info" onclick="location.href='manage-classes.php'">
            <div class="stat-number">🎓 <?php echo $totalClasses; ?></div>
            <p class="stat-label">Años Escolares</p>
        </div>

        <div class="stat-card danger" onclick="location.href='manage-students.php'">
            <div class="stat-number">👪 <?php echo $totalTutors; ?></div>
            <p class="stat-label">Tutores Registrados</p>
        </div>

        <div class="stat-card" onclick="location.href='manage-results.php'">
            <div class="stat-number">📊 <?php echo $totalResults; ?></div>
            <p class="stat-label">Estudiantes con Calificaciones</p>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-bolt"></i> Acciones Rápidas
        </div>
        <div class="card-body" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
            <a href="add-students.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Agregar Estudiante
            </a>
            <a href="add-teacher.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Agregar Docente
            </a>
            <a href="create-subject.php" class="btn btn-warning">
                <i class="fas fa-plus"></i> Crear Materia
            </a>
            <a href="add-result.php" class="btn btn-info">
                <i class="fas fa-plus"></i> Agregar Calificación
            </a>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>
