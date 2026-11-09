// =========================================================
// pantalla-completa.js → botón de pantalla completa
//
// - Intenta la API real de pantalla completa (#gameWindow).
// - Si el navegador no la soporta (iPhone) o la bloquea,
//   usa un modo "falso fullscreen" con CSS: el juego cubre
//   toda la pantalla igualmente (sin bloqueo de orientación).
// - Al entrar/salir redimensiona el canvas (los juegos
//   escuchan el evento resize de la ventana).
// =========================================================
(function () {
    const btn = document.getElementById('btnPantallaCompleta');
    const gameWindow = document.getElementById('gameWindow');
    if (!btn || !gameWindow) return;

    const requestFs = gameWindow.requestFullscreen || gameWindow.webkitRequestFullscreen;
    const exitFs = document.exitFullscreen || document.webkitExitFullscreen;
    const fsElement = () => document.fullscreenElement || document.webkitFullscreenElement;
    const fsFakeActivo = () => gameWindow.classList.contains('fs-fake');

    function resizeJuegos() {
        window.dispatchEvent(new Event('resize'));
    }

    function textoBoton(activado) {
        btn.textContent = activado ? '⛶ Salir de pantalla completa' : '⛶ Pantalla completa';
    }

    // --- Modo respaldo (iPhone / API bloqueada): fullscreen con CSS ---
    function alternarFsFake() {
        const activado = gameWindow.classList.toggle('fs-fake');
        document.body.classList.toggle('bloqueo-scroll', activado);
        textoBoton(activado);
        resizeJuegos();
    }

    ['fullscreenchange', 'webkitfullscreenchange'].forEach((evt) => {
        document.addEventListener(evt, () => {
            textoBoton(!!fsElement() || fsFakeActivo());
            resizeJuegos();
        });
    });

    async function entrarPantallaCompleta() {
        try {
            await requestFs.call(gameWindow);
        } catch (err) {
            console.warn('Pantalla completa bloqueada, usando modo alternativo:', err);
            alternarFsFake();
            return;
        }
        // Bloquear en horizontal (funciona en Android/Chrome; en otros puede fallar)
        try {
            if (screen.orientation && screen.orientation.lock) {
                await screen.orientation.lock('landscape');
            }
        } catch (err) {
            // Navegador sin soporte: el usuario puede girar el teléfono a mano
        }
        textoBoton(true);
        resizeJuegos();
    }

    async function salirPantallaCompleta() {
        try {
            await exitFs.call(document);
        } catch (err) {
            console.warn('No se pudo salir de pantalla completa:', err);
        }
        try {
            if (screen.orientation && screen.orientation.unlock) {
                screen.orientation.unlock();
            }
        } catch (err) { /* ignorar */ }
        textoBoton(false);
        resizeJuegos();
    }

    function alternarPantallaCompleta() {
        if (fsElement()) {
            salirPantallaCompleta();
        } else if (fsFakeActivo()) {
            alternarFsFake();
        } else if (requestFs) {
            entrarPantallaCompleta();
        } else {
            alternarFsFake();
        }
    }

    btn.addEventListener('click', alternarPantallaCompleta);

    // Botón ✕ dentro del juego (visible en pantalla completa)
    const btnSalir = document.getElementById('btnSalirFullscreen');
    if (btnSalir) {
        btnSalir.addEventListener('pointerdown', (e) => e.stopPropagation());
        btnSalir.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (fsElement()) {
                salirPantallaCompleta();
            } else if (fsFakeActivo()) {
                alternarFsFake();
            }
        });
    }
})();
