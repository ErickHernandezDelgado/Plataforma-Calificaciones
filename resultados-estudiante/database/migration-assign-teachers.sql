-- =============================================================================
-- Migration: Auto-assign teachers to subjects when no assignment exists
-- =============================================================================
-- Este script ayuda a identificar y potencialmente auto-asignar maestros
-- a materias que no tienen asignación.
-- 
-- STEP 1: Ver qué combinaciones de Materia-Clase NO tienen maestro asignado
-- =============================================================================

-- Materias que existen pero no tienen maestro asignado a ninguna clase
SELECT DISTINCT s.id, s.SubjectName, COUNT(ts.Id) as asignaciones
FROM tblsubjects s
LEFT JOIN tblteacher_subject ts ON s.id = ts.SubjectId
GROUP BY s.id, s.SubjectName
HAVING asignaciones = 0
ORDER BY SubjectName;

-- Combinaciones de Materia + Clase que no tienen maestro
SELECT 
    c.id as ClassId,
    c.ClassName,
    c.Section,
    s.id as SubjectId,
    s.SubjectName,
    COUNT(ts.Id) as tiene_maestro
FROM tblclasses c
CROSS JOIN tblsubjects s
LEFT JOIN tblteacher_subject ts ON c.id = ts.ClassId AND s.id = ts.SubjectId
WHERE ts.Id IS NULL
GROUP BY c.id, c.ClassName, c.Section, s.id, s.SubjectName
ORDER BY c.ClassName, c.Section, s.SubjectName
LIMIT 20;

-- =============================================================================
-- STEP 2: Ver si hay maestros disponibles para asignar
-- =============================================================================

-- Maestros que no tienen muchas asignaciones (buenos candidatos para nuevas)
SELECT 
    t.Id,
    t.FirstName,
    t.LastName,
    COUNT(ts.Id) as total_asignaciones
FROM tblteachers t
LEFT JOIN tblteacher_subject ts ON t.Id = ts.TeacherId
GROUP BY t.Id, t.FirstName, t.LastName
ORDER BY total_asignaciones ASC;

-- =============================================================================
-- STEP 3: Auto-asignación inteligente (EJEMPLO - AJUSTAR SEGÚN NECESIDADES)
-- =============================================================================
-- Este ejemplo asigna la primera materia de cada clase al primer maestro disponible
-- IMPORTANTE: Descomenta y ajusta según tu lógica de negocio

/*
-- Opción A: Asignar Español a todos los grupos que NO lo tienen (a maestro con ID 2)
INSERT INTO tblteacher_subject (TeacherId, SubjectId, ClassId)
SELECT 2, s.id, c.id
FROM tblclasses c
CROSS JOIN tblsubjects s
WHERE s.SubjectName = 'Español'
  AND NOT EXISTS (
    SELECT 1 FROM tblteacher_subject ts 
    WHERE ts.ClassId = c.id AND ts.SubjectId = s.id
  );

-- Opción B: Asignar Matemáticas a Primaria
INSERT INTO tblteacher_subject (TeacherId, SubjectId, ClassId)
SELECT 1, s.id, c.id
FROM tblclasses c
CROSS JOIN tblsubjects s
WHERE s.SubjectName = 'Matemáticas'
  AND c.ClassName LIKE '%Primero%' OR c.ClassName LIKE '%Segundo%'
  AND NOT EXISTS (
    SELECT 1 FROM tblteacher_subject ts 
    WHERE ts.ClassId = c.id AND ts.SubjectId = s.id
  );
*/

-- =============================================================================
-- STEP 4: Validar el resultado
-- =============================================================================

-- Ver el estado actual de asignaciones
SELECT 
    t.FirstName,
    t.LastName,
    s.SubjectName,
    c.ClassName,
    c.Section,
    ts.AssignDate
FROM tblteacher_subject ts
JOIN tblteachers t ON ts.TeacherId = t.Id
JOIN tblsubjects s ON ts.SubjectId = s.id
JOIN tblclasses c ON ts.ClassId = c.id
ORDER BY t.FirstName, t.LastName, c.ClassName, c.Section, s.SubjectName;
