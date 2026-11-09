// =========================================================
// principal.js: arranque del sitio en el navegador.
// Se carga en TODAS las páginas (desde pie.php):
//  1. Consulta si hay sesión (Auth.init)
//  2. Si estamos en una página de juego, monta el ranking y comentarios
//  3. Cierra modales al pulsar la X o fuera de ellos
// =========================================================
document.addEventListener('DOMContentLoaded', async function() {
    try {
        await Auth.init();
    } catch (e) {
        console.warn('Error al iniciar la sesión:', e);
    }

    // Si la página definió GAME_SLUG (vista juego.php) → cargar ranking y comentarios
    if (typeof GAME_SLUG !== 'undefined') {
        initLeaderboard(GAME_SLUG);
        initComments(GAME_SLUG);
    }

    // Cerrar modales (X o clic en el fondo)
    document.querySelectorAll('.modal-close, .modal').forEach(el => {
        el.addEventListener('click', (e) => {
            if (e.target === el || e.target.classList.contains('modal-close')) {
                e.target.closest('.modal')?.classList.remove('open');
            }
        });
    });

    // =========================================================
    // Header: se oculta al bajar el scroll y vuelve al subir
    // =========================================================
    const header = document.querySelector('header');
    if (header) {
        let ultimoScroll = 0;
        window.addEventListener('scroll', () => {
            const scrollActual = window.scrollY || 0;

            // No ocultar si el menú móvil está abierto
            const menuMovilAbierto = document.querySelector('.navlist.active');
            if (menuMovilAbierto) {
                header.classList.remove('header-oculto');
                ultimoScroll = scrollActual;
                return;
            }

            if (scrollActual > 140 && scrollActual > ultimoScroll) {
                // Bajando → ocultar
                header.classList.add('header-oculto');
            } else {
                // Subiendo o casi en el top → mostrar
                header.classList.remove('header-oculto');
            }
            ultimoScroll = scrollActual;
        }, { passive: true });
    }
});