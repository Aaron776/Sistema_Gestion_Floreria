 <div style="margin-top:2.5rem; font-size:0.7rem; color:#a28d7a; text-align:center; border-top:1px solid #ede8e0; padding-top:1.2rem;">
            <i class="fas fa-seedling" style="margin-right:4px;"></i> Florería Pétalos · Dashboard v1.0 · Diseño moderno y responsive
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Toggle sidebar en móvil
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');

        function openSidebar() {
            if (!sidebar || !overlay) return;
            sidebar.classList.add('open');
            overlay.classList.add('open');
        }

        function closeSidebar() {
            if (!sidebar || !overlay) return;
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        }

        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (sidebar.classList.contains('open')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });
        }

        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }

        // Cerrar sidebar al hacer click en un enlace (móvil)
        if (sidebar) {
            document.querySelectorAll('.sidebar-menu a').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 1024 && !link.classList.contains('submenu-toggle')) {
                        closeSidebar();
                    }
                });
            });
        }

        // Toggle submenús desplegables
        function toggleSubmenu(li) {
            li.classList.toggle('open');
        }

        // Cerrar al redimensionar a escritorio
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024) {
                closeSidebar();
            }
        });
    </script>
</body>

</html>