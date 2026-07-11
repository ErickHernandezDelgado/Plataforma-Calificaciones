<?php
/**
 * includes/p_footer.php
 * Antes dibujaba una barra verde fija "Resultados Estudiante" al pie (con HTML mal formado:
 * un <!DOCTYPE html>/<html> duplicado dentro de la página). Se eliminó a petición del usuario
 * (2026-07-07) porque estorbaba en las pantallas de admin (fin de ciclo, generar boletas,
 * agregar calificación, cierre de periodos, generar reporte).
 *
 * NO carga JS de la plantilla (eso lo hace includes/footer.php, que sí es crítico). Este
 * archivo queda como no-op para no romper los include() existentes que lo referencian.
 */
