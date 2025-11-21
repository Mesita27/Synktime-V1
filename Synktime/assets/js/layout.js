class Layout {
    constructor() {
        this.sidebar = document.getElementById('sidebar');
        this.mainWrapper = document.querySelector('.main-wrapper');
        this.toggleBtn = document.getElementById('toggleSidebar');
        
        console.log('Layout Constructor - Elementos encontrados:', {
            sidebar: !!this.sidebar,
            mainWrapper: !!this.mainWrapper,
            toggleBtn: !!this.toggleBtn
        });
        
        if (this.sidebar && this.mainWrapper && this.toggleBtn) {
            this.initializeLayout();
        } else {
            console.error('Layout: No se pueden inicializar - elementos faltantes');
        }
    }

    initializeLayout() {
        console.log('Inicializando Layout...');
        
        // Toggle sidebar
        this.toggleBtn.addEventListener('click', () => {
            console.log('Sidebar toggle clicked, window width:', window.innerWidth);
            
            if (window.innerWidth <= 1024) {
                this.sidebar.classList.toggle('mobile-active');
                console.log('Mobile toggle - active:', this.sidebar.classList.contains('mobile-active'));
            } else {
                this.sidebar.classList.toggle('collapsed');
                this.mainWrapper.classList.toggle('sidebar-collapsed');
                const isCollapsed = this.sidebar.classList.contains('collapsed');
                localStorage.setItem('sidebarState', isCollapsed ? 'collapsed' : 'expanded');
                console.log('Desktop toggle - collapsed:', isCollapsed);
            }
        });

        // Click fuera del sidebar en móvil lo cierra
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 1024) {
                if (!this.sidebar.contains(e.target) && 
                    !this.toggleBtn.contains(e.target) && 
                    this.sidebar.classList.contains('mobile-active')) {
                    this.sidebar.classList.remove('mobile-active');
                }
            }
        });

        // Restaurar estado del sidebar
        if (window.innerWidth > 1024) {
            const sidebarState = localStorage.getItem('sidebarState');
            if (sidebarState === 'collapsed') {
                this.sidebar.classList.add('collapsed');
                this.mainWrapper.classList.add('sidebar-collapsed');
            }
        }

        // Actualizar datetime
        this.updateDateTime();
        setInterval(() => this.updateDateTime(), 1000);

        // Manejar resize de ventana
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024) {
                this.sidebar.classList.remove('mobile-active');
                const sidebarState = localStorage.getItem('sidebarState');
                if (sidebarState === 'collapsed') {
                    this.sidebar.classList.add('collapsed');
                    this.mainWrapper.classList.add('sidebar-collapsed');
                }
            } else {
                this.sidebar.classList.remove('collapsed');
                this.mainWrapper.classList.remove('sidebar-collapsed');
            }
        });
    }

    updateDateTime() {
    const now = new Date();
    // Opciones para mostrar fecha y hora en español de Colombia
    const options = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
        timeZone: 'America/Bogota'
    };
    // Formato: DD/MM/YYYY HH:MM:SS
    const dateTimeStr = now.toLocaleString('es-CO', options).replace(',', '');
    document.getElementById('currentDateTime').textContent = dateTimeStr;
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new Layout();
    // Dropdown user menu
    const userDropdown = document.querySelector('.user-dropdown');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userMenu = document.getElementById('userMenu');

    if (userDropdown && userMenuBtn && userMenu) {
        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('open');
        });

        // Cerrar el menú al hacer click fuera
        document.addEventListener('click', (e) => {
            if (userDropdown.classList.contains('open')) {
                userDropdown.classList.remove('open');
            }
        });

        // Opcional: No cerrar al hacer click dentro del menú
        userMenu.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }
});