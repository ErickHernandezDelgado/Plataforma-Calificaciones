/**
 * main.js - JavaScript simple y limpio
 * Sin jQuery, sin loaders, sin preventDefault()
 * Navegación simple y directa
 */

// Inicializar cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('✓ Página cargada correctamente');
    
    // Toggle sidebar en dispositivos móviles
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Cerrar sidebar al hacer click en un enlace
    const sidebarLinks = document.querySelectorAll('.sidebar-nav-link');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (sidebar) {
                sidebar.classList.remove('active');
            }
        });
    });
    
    // Toggle submenús SIN preventDefault() - dejar que funcione naturalmente
    const submenuToggles = document.querySelectorAll('.sidebar-nav-item > a');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            const parent = this.parentElement;
            if (parent.classList.contains('sidebar-nav-item')) {
                parent.classList.toggle('open');
            }
        });
    });
    
    // Auto-detectar página actual y marcar como activa
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    const navLinks = document.querySelectorAll('.sidebar-nav-link');
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href === currentPage) {
            link.classList.add('active');
            link.closest('.sidebar-nav-item')?.classList.add('open');
        }
    });
});

// ✨ No hay loaders, spinners ni bloqueos de navegación
// Los enlaces funcionan naturalmente como HTML normal
