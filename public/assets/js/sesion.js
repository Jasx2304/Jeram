// =========================================================
// sesion.js: estado de sesión en el navegador + utilidades de UI.
// - Auth.check() pregunta a /api/auth/me si hay sesión (lo llama principal.js)
// - El resto son mejoras de interfaz: fortaleza de contraseña,
//   mostrar/ocultar contraseña, menú de usuario y menú móvil.
// =========================================================

// Auxiliares de sesión en el frontend
const Auth = {
    _currentUser: null,

    async check() {
        try {
            const res = await API.get('/api/auth/me');
            this._currentUser = res.authenticated ? res.user : null;
            return this._currentUser;
        } catch {
            this._currentUser = null;
            return null;
        }
    },

    getUser() {
        return this._currentUser;
    },

    async logout() {
        window.location.href = (window.APP_BASE || '') + '/logout';
    },

    async init() {
        await this.check();
    },
};

// Indicador de fortaleza de la contraseña (en el formulario de registro)
document.addEventListener('DOMContentLoaded', function() {
    const passInput = document.getElementById('regPassword');
    if (passInput) {
        const fortaleza = document.getElementById('fortaleza');
        const barraFuerza = document.getElementById('barraFuerza');
        const textoFuerza = document.getElementById('textoFuerza');

        passInput.addEventListener('input', () => {
            const pass = passInput.value;
            let score = 0;
            if (pass.length >= 8) score++;
            if (/[A-Z]/.test(pass)) score++;
            if (/[0-9]/.test(pass)) score++;
            if (/[^a-zA-Z0-9]/.test(pass)) score++;

            const config = [
                { pct: '0%', color: '#e0e0e0', label: '' },
                { pct: '25%', color: '#e53935', label: ' Muy débil' },
                { pct: '50%', color: '#ffb300', label: ' Débil' },
                { pct: '75%', color: '#43a047', label: ' Fuerte' },
                { pct: '100%', color: '#00ff88', label: ' Muy fuerte' },
            ][score];

            if (pass.length > 0) {
                fortaleza?.classList.add('show');
                textoFuerza?.classList.add('show');
                if (barraFuerza) barraFuerza.style.width = config.pct;
                if (barraFuerza) barraFuerza.style.background = config.color;
                if (textoFuerza) textoFuerza.textContent = config.label;
            } else {
                fortaleza?.classList.remove('show');
                textoFuerza?.classList.remove('show');
            }
        });
    }
});

// Mostrar / ocultar contraseña (botón con ojo)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toggle-visibility').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.parentElement.querySelector('input');
            if (input) {
                input.type = input.type === 'password' ? 'text' : 'password';
                btn.textContent = input.type === 'password' ? '👁️' : '🙈';
            }
        });
    });
});

// Menú desplegable del usuario (avatar en la barra superior)
document.addEventListener('DOMContentLoaded', function() {
    const userMenu = document.getElementById('userMenu');
    const userDropdown = document.getElementById('userDropdown');
    if (userMenu && userDropdown) {
        userMenu.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('open');
        });
        document.addEventListener('click', () => userDropdown.classList.remove('open'));
    }
});

// Menú móvil: se abre/cierra con animación, se cierra con Escape o al pulsar fuera
document.addEventListener('DOMContentLoaded', function() {
    const menuIcon = document.getElementById('menu-icon');
    const navList = document.querySelector('.navlist');
    const navLinks = navList ? navList.querySelectorAll('a') : [];

    if (menuIcon && navList) {
        function openMenu() {
            navList.classList.add('active');
            menuIcon.classList.add('open');
            menuIcon.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
            // Animación escalonada de los enlaces
            navLinks.forEach((link, i) => {
                link.style.animationDelay = `${i * 50}ms`;
            });
        }

        function closeMenu() {
            navList.classList.remove('active');
            menuIcon.classList.remove('open');
            menuIcon.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }

        function toggleMenu(e) {
            e.stopPropagation();
            if (navList.classList.contains('active')) {
                closeMenu();
            } else {
                openMenu();
            }
        }

        menuIcon.addEventListener('click', toggleMenu);

        // Se cierra al pulsar un enlace
        navLinks.forEach(link => {
            link.addEventListener('click', closeMenu);
        });

        // Se cierra al pulsar fuera
        document.addEventListener('click', (e) => {
            if (navList.classList.contains('active') &&
                !navList.contains(e.target) &&
                !menuIcon.contains(e.target)) {
                closeMenu();
            }
        });

        // Se cierra con la tecla Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && navList.classList.contains('active')) {
                closeMenu();
            }
        });

        // Al cambiar el tamaño: cerrar el menú si la pantalla se agranda
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768 && navList.classList.contains('active')) {
                closeMenu();
            }
        });
    }
});