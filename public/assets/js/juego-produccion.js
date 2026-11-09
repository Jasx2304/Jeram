// =========================================================
// juego-produccion.js → "Línea de Producción"
// Juego de memoria/ritmo con botones de colores (estilo Simon):
// la línea produce una secuencia y el jugador debe repetirla.
// Cada acierto suma puntos; al fallar se llama a submitScore().
// =========================================================
const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');
const gameOverlay = document.getElementById('gameOverlay');
const scoreDisplay = document.getElementById('scoreDisplay');
const currentScore = document.getElementById('currentScore');
const speedDisplay = document.getElementById('speedDisplay');
const levelDisplay = document.getElementById('levelDisplay');
const loadingStatus = document.getElementById('loadingStatus');

function resizeCanvas() {
    const container = document.getElementById('gameWindow');
    canvas.width = container.offsetWidth;
    canvas.height = container.offsetHeight;
}
resizeCanvas();
window.addEventListener('resize', resizeCanvas);

const COLORS = ['cyan', 'magenta', 'yellow', 'green'];
const COLOR_MAP = {
    cyan: '#00d4ff',
    magenta: '#ff00fb',
    yellow: '#ffd700',
    green: '#00ff88',
};

const SPRITE_PATHS = {
    cyan: 'assets/imagenes/linea-produccion/btn-cyan.png',
    magenta: 'assets/imagenes/linea-produccion/btn-magenta.png',
    yellow: 'assets/imagenes/linea-produccion/btn-amarillo.png',
    green: 'assets/imagenes/linea-produccion/btn-verde.png',
    off: 'assets/imagenes/linea-produccion/btn-off.png',
    background: 'assets/imagenes/sprites/sprites_linea_de_produccion/fondo_linea_de_produccion.png',
};

const spriteCache = {};
let spritesLoaded = false;

function loadSprites() {
    return new Promise((resolve) => {
        let loaded = 0;
        const total = Object.keys(SPRITE_PATHS).length;
        if (total === 0) { spritesLoaded = true; resolve(); return; }

        const check = () => {
            loaded++;
            if (loadingStatus) loadingStatus.textContent = `Cargando sprites... ${loaded}/${total}`;
            if (loaded >= total) {
                spritesLoaded = true;
                if (loadingStatus) { loadingStatus.textContent = 'Sprites cargados'; loadingStatus.style.color = '#00ff88'; }
                resolve();
            }
        };

        Object.entries(SPRITE_PATHS).forEach(([key, path]) => {
            const img = new Image();
            img.src = path;
            img.onload = () => { spriteCache[key] = img; check(); };
            img.onerror = () => { check(); };
        });
    });
}

class Game {
    constructor() {
        this.sequence = [];
        this.playerInput = [];
        this.step = 0;
        this.score = 0;
        this.gameRunning = false;
        this.gameOver = false;
        this.showingSequence = false;
        this.scoreSubmitted = false;
        this.buttons = [];
        this.setupCanvasButtons();
        this.setupControls();
    }

    setupCanvasButtons() {
        this.buttons = COLORS.map((color, i) => {
            const cols = 2;
            const bw = Math.min(canvas.width * 0.35, 130);
            const bh = bw;
            const gap = 12;
            const totalW = cols * bw + (cols - 1) * gap;
            const totalH = 2 * bh + gap;
            const ox = (canvas.width - totalW) / 2;
            const oy = (canvas.height - totalH) / 2 - 40;
            const col = i % cols;
            const row = Math.floor(i / cols);
            return { color, x: ox + col * (bw + gap), y: oy + row * (bh + gap), w: bw, h: bh, active: false };
        });
    }

    setupControls() {
        canvas.addEventListener('click', (e) => {
            if (!this.gameRunning || this.showingSequence || this.gameOver) return;
            const rect = canvas.getBoundingClientRect();
            const mx = (e.clientX - rect.left) * (canvas.width / rect.width);
            const my = (e.clientY - rect.top) * (canvas.height / rect.height);
            for (const btn of this.buttons) {
                if (mx >= btn.x && mx <= btn.x + btn.w && my >= btn.y && my <= btn.y + btn.h) {
                    this.handlePlayerInput(btn.color);
                    return;
                }
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.code === 'Enter') {
                e.preventDefault();
                if (!this.gameRunning && !this.gameOver) this.start();
                else if (this.gameOver) this.start();
            }
        });
        gameOverlay.addEventListener('click', () => {
            if (!this.gameRunning && !this.gameOver) this.start();
            else if (this.gameOver) this.start();
        });
    }

    start() {
        if (!spritesLoaded) {
            if (loadingStatus) loadingStatus.textContent = 'Cargando sprites...';
            loadSprites().then(() => this.startGame());
            return;
        }
        this.startGame();
    }

    startGame() {
        this.gameRunning = true;
        this.gameOver = false;
        this.sequence = [];
        this.playerInput = [];
        this.step = 0;
        this.score = 0;
        this.scoreSubmitted = false;
        gameOverlay.classList.add('hidden');
        this.setupCanvasButtons();
        this.nextRound();
    }

    nextRound() {
        this.step++;
        this.playerInput = [];
        this.sequence.push(COLORS[Math.floor(Math.random() * COLORS.length)]);
        this.showingSequence = true;
        if (levelDisplay) levelDisplay.textContent = this.step;
        if (speedDisplay) speedDisplay.textContent = (1 + Math.floor(this.step / 5) * 0.2).toFixed(1) + 'x';

        this.playSequence(0);
    }

    playSequence(index) {
        if (index >= this.sequence.length) {
            setTimeout(() => { this.showingSequence = false; }, 300);
            return;
        }
        const color = this.sequence[index];
        this.flashButton(color, () => {
            setTimeout(() => this.playSequence(index + 1), 200);
        });
    }

    flashButton(color, cb) {
        const btn = this.buttons.find(b => b.color === color);
        if (btn) btn.active = true;
        this.draw();
        setTimeout(() => {
            if (btn) btn.active = false;
            this.draw();
            if (cb) cb();
        }, 400);
    }

    handlePlayerInput(color) {
        const expected = this.sequence[this.playerInput.length];
        this.flashButton(color);

        if (color !== expected) {
            this.endGame();
            return;
        }

        this.playerInput.push(color);

        if (this.playerInput.length === this.sequence.length) {
            this.score = this.step * 100;
            currentScore.textContent = this.score;
            setTimeout(() => this.nextRound(), 600);
        }
    }

    endGame() {
        this.gameOver = true;
        this.gameRunning = false;
        this.showingSequence = false;
        this.score = (this.step - 1) * 100;
        if (this.score < 0) this.score = 0;
        currentScore.textContent = this.score;
        this.showGameOver();
        this.submitScore();
    }

    async submitScore() {
        if (this.scoreSubmitted || this.score === 0) return;
        this.scoreSubmitted = true;
        if (IS_AUTHENTICATED && typeof GAME_SLUG !== 'undefined') {
            try {
                await API.post('/api/score/submit', { game_slug: GAME_SLUG, score: this.score, level_reached: this.step });
                if (typeof loadLeaderboard === 'function') setTimeout(() => loadLeaderboard(GAME_SLUG), 500);
            } catch (err) { console.warn('No se pudo enviar la puntuación:', err); }
        }
    }

    showGameOver() {
        gameOverlay.classList.remove('hidden');
        scoreDisplay.textContent = this.score;
        const t = document.querySelector('#gameOverlay h2');
        if (t) t.textContent = `¡Llegaste al paso ${this.step}! Presiona ENTER para reiniciar`;
    }

    draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        const bg = spriteCache['background'];
        if (bg) {
            ctx.drawImage(bg, 0, 0, canvas.width, canvas.height);
        } else {
            ctx.fillStyle = 'rgba(10, 14, 39, 0.3)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }

        const offImg = spriteCache['off'];
        for (const btn of this.buttons) {
            const s = btn.active ? spriteCache[btn.color] : spriteCache['off'];
            if (s && btn.active) {
                ctx.drawImage(s, btn.x, btn.y, btn.w, btn.h);
            } else if (s && !btn.active) {
                ctx.drawImage(s, btn.x, btn.y, btn.w, btn.h);
            } else {
                ctx.fillStyle = btn.active ? COLOR_MAP[btn.color] : 'rgba(255,255,255,0.1)';
                ctx.shadowColor = btn.active ? COLOR_MAP[btn.color] : 'transparent';
                ctx.shadowBlur = btn.active ? 25 : 0;
                ctx.beginPath();
                ctx.roundRect(btn.x, btn.y, btn.w, btn.h, 16);
                ctx.fill();
                ctx.shadowBlur = 0;
                ctx.strokeStyle = COLOR_MAP[btn.color];
                ctx.lineWidth = 2;
                ctx.globalAlpha = btn.active ? 1 : 0.3;
                ctx.beginPath();
                ctx.roundRect(btn.x, btn.y, btn.w, btn.h, 16);
                ctx.stroke();
                ctx.globalAlpha = 1;
            }
        }

        ctx.fillStyle = '#fff';
        ctx.font = '18px monospace';
        ctx.textAlign = 'center';
        ctx.fillText('Paso: ' + this.step, canvas.width / 2, this.buttons[0] ? this.buttons[0].y - 30 : 50);
        if (this.showingSequence) {
            ctx.fillStyle = '#ffd700';
            ctx.fillText('👀 Observá la secuencia...', canvas.width / 2, canvas.height - 30);
        } else if (this.gameRunning) {
            ctx.fillStyle = '#00ff88';
            ctx.fillText('👆 Tu turno', canvas.width / 2, canvas.height - 30);
        }
    }

    update() {
        if (!this.gameRunning) return;
        this.draw();
        requestAnimationFrame(() => this.update());
    }
}

if (!CanvasRenderingContext2D.prototype.roundRect) {
    CanvasRenderingContext2D.prototype.roundRect = function(x, y, w, h, r) {
        if (r > w/2) r = w/2;
        if (r > h/2) r = h/2;
        this.moveTo(x + r, y);
        this.lineTo(x + w - r, y);
        this.quadraticCurveTo(x + w, y, x + w, y + r);
        this.lineTo(x + w, y + h - r);
        this.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        this.lineTo(x + r, y + h);
        this.quadraticCurveTo(x, y + h, x, y + h - r);
        this.lineTo(x, y + r);
        this.quadraticCurveTo(x, y, x + r, y);
        return this;
    };
}

const game = new Game();
window.addEventListener('load', async () => {
    try {
        await loadSprites();
        resizeCanvas();
        game.setupCanvasButtons();
        game.draw();
        if (loadingStatus) loadingStatus.textContent = 'Listo. Presiona ENTER para empezar';
        const t = document.querySelector('#gameOverlay h2');
        if (t) t.textContent = 'Presiona ENTER para jugar';
    } catch (e) {
        if (loadingStatus) {
            loadingStatus.textContent = 'Usando gráficos básicos';
            loadingStatus.style.color = '#ff006e';
        }
    }
});
