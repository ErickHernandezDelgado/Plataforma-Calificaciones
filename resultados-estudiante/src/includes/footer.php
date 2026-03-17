            </main>
        </div>
    </div>

    <!-- SCRIPT PRINCIPAL -->
    <script src="./assets/js/main.js"></script>

    <!-- Script para menú interactivo OPCIONAL (no bloquea navegación) -->
    <script>
        // Auto-detectar página actual y marcar como activa
        document.addEventListener('DOMContentLoaded', function() {
            const currentFile = window.location.pathname.split('/').pop() || 'index.php';
            const navLinks = document.querySelectorAll('.sidebar-nav-link');
            
            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href && (href === currentFile || href.includes(currentFile))) {
                    link.classList.add('active');
                    link.closest('.sidebar-nav-item')?.classList.add('open');
                }
            });
        });
    </script>
</body>
</html>
