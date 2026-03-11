-- ========================================
-- SCRIPT: Crear Relación Tutor-Alumno (Padres de Familia)
-- ========================================

-- ========================================
-- 1. AGREGAR COLUMNA A tblstudents
-- ========================================

-- Agregar referencia rápida al tutor principal
ALTER TABLE tblstudents 
ADD COLUMN primary_tutor_id INT(11) DEFAULT NULL 
AFTER Status;

-- ========================================
-- 2. CREAR TABLA INTERMEDIA student_tutor
-- ========================================

-- Tabla que vincular estudiantes con tutores (1:N y N:N)
-- Un estudiante puede tener múltiples tutores (papá, mamá, abuelo)
-- Un tutor puede ser responsable de múltiples estudiantes

CREATE TABLE IF NOT EXISTS student_tutor (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    StudentId INT(11) NOT NULL,
    TutorId INT(11) NOT NULL,
    RelationshipType VARCHAR(50) DEFAULT 'padre',  -- 'padre', 'madre', 'abuelo', 'tutor_legal', etc.
    PrimaryContact TINYINT(1) DEFAULT 0,  -- 1 si es contacto principal
    CanViewGrades TINYINT(1) DEFAULT 1,  -- 1 si puede ver calificaciones
    CanDownloadReport TINYINT(1) DEFAULT 1,  -- 1 si puede descargar boletas
    CreationDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdateDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (StudentId) REFERENCES tblstudents(StudentId) ON DELETE CASCADE,
    FOREIGN KEY (TutorId) REFERENCES admin(id) ON DELETE CASCADE,
    
    -- Índices para búsquedas rápidas
    KEY idx_StudentId (StudentId),
    KEY idx_TutorId (TutorId),
    KEY idx_PrimaryContact (PrimaryContact)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ========================================
-- 3. AGREGAR DATOS DE PRUEBA
-- ========================================

-- Vincular el tutor@test.com (id=8) con estudiante 1 (Farah Balderas)
-- NOTA: El tutor@test.com fue creado en script anterior
INSERT INTO student_tutor (StudentId, TutorId, RelationshipType, PrimaryContact, CanViewGrades, CanDownloadReport)
VALUES 
  (1, 8, 'padre', 1, 1, 1);  -- Tutor principal de Farah

-- Actualizar primary_tutor_id en tblstudents
UPDATE tblstudents SET primary_tutor_id = 8 WHERE StudentId = 1;

-- ========================================
-- 4. CREAR VISTA PARA CONSULTAS COMUNES
-- ========================================

-- Vista: Listar estudiantes con su tutor principal
DROP VIEW IF EXISTS vw_student_with_tutor;
CREATE VIEW vw_student_with_tutor AS
SELECT 
    s.StudentId,
    s.StudentName,
    s.RollId,
    s.ClassId,
    c.ClassName,
    c.Section,
    s.StudentEmail,
    a.id as TutorId,
    a.UserName as TutorEmail,
    st.RelationshipType,
    st.PrimaryContact
FROM tblstudents s
LEFT JOIN tblclasses c ON s.ClassId = c.id
LEFT JOIN admin a ON s.primary_tutor_id = a.id
LEFT JOIN student_tutor st ON s.StudentId = st.StudentId AND st.PrimaryContact = 1;

-- Vista: Listar todos los estudiantes de un tutor
DROP VIEW IF EXISTS vw_tutor_students;
CREATE VIEW vw_tutor_students AS
SELECT 
    st.StudentId,
    s.StudentName,
    s.RollId,
    c.ClassName,
    c.Section,
    a.UserName as TutorEmail,
    st.RelationshipType,
    st.CanViewGrades,
    st.CanDownloadReport
FROM student_tutor st
JOIN tblstudents s ON st.StudentId = s.StudentId
JOIN tblclasses c ON s.ClassId = c.id
JOIN admin a ON st.TutorId = a.id
WHERE st.PrimaryContact = 1;  -- Solo contactos principales para simplificar

-- ========================================
-- 5. AGREGAR ForeignKey CONSTRAINT (seguridad)
-- ========================================

-- Comentado: Si quieres agregar FK directa a primary_tutor_id
-- ALTER TABLE tblstudents 
-- ADD CONSTRAINT fk_primary_tutor 
-- FOREIGN KEY (primary_tutor_id) REFERENCES admin(id);

-- ========================================
-- VERIFICACIÓN
-- ========================================

/*
-- Ver estructura nueva
DESCRIBE student_tutor;
DESCRIBE tblstudents;

-- Ver vistas creadas
SELECT * FROM vw_student_with_tutor;
SELECT * FROM vw_tutor_students WHERE TutorEmail = 'tutor@test.com';

-- Count
SELECT COUNT(*) as total_relations FROM student_tutor;
*/
