// =========================================================
// juego-encargo.js → "El Encargo"
// Dos vidas:
//   1) Vida 1: movimiento controlado con W/S o flechas arriba/abajo
//   2) Vida 2: modo flappy bird con ESPACIO (caída libre)
// Al chocar se pierde una vida; si queda una, aviso de 3s y cambia al modo flappy.
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

canvas.classList.add('encargo-canvas');

const SPRITE_PATHS = {
    player: [
        'assets/imagenes/sprites/sprites_el_encargo/dron_1.png',
        'assets/imagenes/sprites/sprites_el_encargo/dron_2.png'
    ],
    playerDamaged: 'assets/imagenes/sprites/sprites_el_encargo/dron_danado_modoflappybird.png',
    obstacle: 'assets/imagenes/sprites/sprites_el_encargo/obstaculo.png',
    pipeTop: 'assets/imagenes/sprites/sprites_el_encargo/obstaculo.png',
    pipeBottom: 'assets/imagenes/sprites/sprites_el_encargo/obstaculo.png',
    background: 'assets/imagenes/sprites/sprites_el_encargo/fondo_el_encargo.jpeg',
};

const spriteCache = {};
let spritesLoaded = false;

// Estados del juego
const STATE_FIRST = 'first';        // Vida 1: controlado con W/S o flechas
const STATE_TRANSITION = 'transition'; // Aviso de 3 segundos
const STATE_FLAPPY = 'flappy';      // Vida 2: flappy bird con espacio
const STATE_GAMEOVER = 'gameover';

let currentState = STATE_FIRST;
let vidas = 2;
let transitionStart = 0;

function loadSprites() {
    return new Promise((resolve) => {
        let loaded = 0;
        let total = 0;
        const paths = [];

        Object.entries(SPRITE_PATHS).forEach(([key, path]) => {
            if (Array.isArray(path)) {
                path.forEach((p, i) => paths.push({ key, index: i, path: p }));
            } else {
                paths.push({ key, path });
            }
        });
        total = paths.length;
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

        paths.forEach(({ key, index, path }) => {
            const img = new Image();
            img.src = path;
            img.onload = () => {
                if (index !== undefined) {
                    if (!spriteCache[key]) spriteCache[key] = [];
                    spriteCache[key][index] = img;
                } else {
                    spriteCache[key] = img;
                }
                check();
            };
            img.onerror = () => { check(); };
        });
    });
}

class Player {
    constructor() {
        this.x = canvas.width * 0.15;
        this.y = canvas.height / 2;
        this.width = 80;
        this.height = 60;
        this.velocity = 0;
        this.gravity = 0.18;
        this.lift = -6;
        this.moveSpeed = 7;
        this.animationFrame = 0;
        this.animationSpeed = 0.2;
    }

    update(mode) {
        if (mode === STATE_FIRST) {
            return;
        }
        this.velocity += this.gravity;
        this.y += this.velocity;
    }

    flap() {
        this.velocity = this.lift;
    }

    moveUp() {
        this.y -= this.moveSpeed;
        if (this.y < 0) this.y = 0;
    }

    moveDown() {
        this.y += this.moveSpeed;
        if (this.y + this.height > canvas.height) this.y = canvas.height - this.height;
    }

    draw() {
        if (currentState === STATE_FLAPPY && spriteCache['playerDamaged']) {
            ctx.drawImage(spriteCache['playerDamaged'], this.x, this.y, this.width, this.height);
            return;
        }
        const playerSprites = spriteCache['player'];
        if (playerSprites && playerSprites.length > 0) {
            const index = Math.floor(this.animationFrame) % playerSprites.length;
            const s = playerSprites[index];
            if (s) {
                ctx.drawImage(s, this.x, this.y, this.width, this.height);
                this.animationFrame += this.animationSpeed;
            } else {
                this.drawFallback();
            }
        } else {
            this.drawFallback();
        }
    }

    drawFallback() {
        ctx.fillStyle = '#ff00fb';
        ctx.beginPath();
        ctx.ellipse(this.x + this.width/2, this.y + this.height/2, this.width/2, this.height/2, 0, 0, Math.PI*2);
        ctx.fill();
        ctx.fillStyle = '#fff';
        ctx.beginPath();
        ctx.arc(this.x + this.width/2 + 6, this.y + this.height/2 - 4, 4, 0, Math.PI*2);
        ctx.fill();
        ctx.fillStyle = '#000';
        ctx.beginPath();
        ctx.arc(this.x + this.width/2 + 7, this.y + this.height/2 - 4, 2, 0, Math.PI*2);
        ctx.fill();
    }

    getHitBox() {
        return { x: this.x + 4, y: this.y + 4, width: this.width - 8, height: this.height - 8 };
    }
}

class Pipe {
    constructor() {
        this.width = 80;
        this.gap = canvas.height * 0.35;
        const minTop = 60;
        const maxTop = canvas.height - this.gap - 60;
        this.topHeight = minTop + Math.random() * (maxTop - minTop);
        this.x = canvas.width;
        this.passed = false;
    }

    update(speed) {
        this.x -= speed;
    }

    draw() {
        const sTop = spriteCache['pipeTop'];
        const sBot = spriteCache['pipeBottom'];
        if (sTop) {
            ctx.drawImage(sTop, this.x, 0, this.width, this.topHeight);
        } else {
            const grad = ctx.createLinearGradient(this.x, 0, this.x + this.width, 0);
            grad.addColorStop(0, '#9d4edd');
            grad.addColorStop(0.5, '#00d4ff');
            grad.addColorStop(1, '#9d4edd');
            ctx.fillStyle = grad;
            ctx.fillRect(this.x, 0, this.width, this.topHeight);
            ctx.fillStyle = '#00d4ff';
            ctx.fillRect(this.x - 4, this.topHeight - 20, this.width + 8, 20);
        }
        const bottomY = this.topHeight + this.gap;
        if (sBot) {
            ctx.drawImage(sBot, this.x, bottomY, this.width, canvas.height - bottomY);
        } else {
            const grad = ctx.createLinearGradient(this.x, bottomY, this.x + this.width, bottomY);
            grad.addColorStop(0, '#9d4edd');
            grad.addColorStop(0.5, '#00d4ff');
            grad.addColorStop(1, '#9d4edd');
            ctx.fillStyle = grad;
            ctx.fillRect(this.x, bottomY, this.width, canvas.height - bottomY);
            ctx.fillStyle = '#00d4ff';
            ctx.fillRect(this.x - 4, bottomY, this.width + 8, 20);
        }
    }

    isOffScreen() {
        return this.x + this.width < 0;
    }

    getTopHitBox() {
        return { x: this.x, y: 0, width: this.width, height: this.topHeight };
    }

    getBottomHitBox() {
        const bottomY = this.topHeight + this.gap;
        return { x: this.x, y: bottomY, width: this.width, height: canvas.height - bottomY };
    }
}

function rectCollide(a, b) {
    return a.x < b.x + b.width && a.x + a.width > b.x &&
           a.y < b.y + b.height && a.y + a.height > b.y;
}

class Game {
    constructor() {
        this.player = new Player();
        this.pipes = [];
        this.score = 0;
        this.gameRunning = false;
        this.gameOver = false;
        this.speed = 4;
        this.baseSpeed = 4;
        this.spawnInterval = 1800;
        this.lastSpawn = 0;
        this.scoreSubmitted = false;
        this._loopActive = false;
        this.setupControls();
    }

    setupControls() {
        document.addEventListener('keydown', (e) => {
            if (e.code === 'Space' || e.code === 'ArrowUp') {
                e.preventDefault();
                if (currentState === STATE_FLAPPY) {
                    if (this.gameRunning) this.player.flap();
                    if (!this.gameRunning && !this.gameOver) this.start();
                    else if (this.gameOver) this.start();
                } else if (currentState === STATE_FIRST && e.code === 'ArrowUp') {
                    this.player.moveUp();
                }
            }
            if (e.code === 'ArrowDown') {
                e.preventDefault();
                if (currentState === STATE_FIRST && this.gameRunning) this.player.moveDown();
            }
            if (e.code === 'KeyW') {
                e.preventDefault();
                if (currentState === STATE_FIRST && this.gameRunning) this.player.moveUp();
            }
            if (e.code === 'KeyS') {
                e.preventDefault();
                if (currentState === STATE_FIRST && this.gameRunning) this.player.moveDown();
            }
            if (e.code === 'Enter') {
                e.preventDefault();
                if (!this.gameRunning && !this.gameOver) this.start();
                else if (this.gameOver) this.start();
            }
        });
        // --- Controles con puntero (mouse + táctil + lápiz) ---
        // Modo dron (vida 1): presionar mitad superior sube, inferior baja (mantener = sigue)
        // Modo flappy (vida 2): cada pulsación da un aleteo
        let moverDireccion = 0; // 1 = arriba, -1 = abajo, 0 = nada
        let intervaloMovimiento = null;

        const detenerMovimiento = () => {
            moverDireccion = 0;
            if (intervaloMovimiento !== null) {
                clearInterval(intervaloMovimiento);
                intervaloMovimiento = null;
            }
        };

        const iniciarMovimiento = (direccion) => {
            detenerMovimiento();
            if (currentState !== STATE_FIRST || !this.gameRunning) return;
            moverDireccion = direccion;
            if (direccion > 0) this.player.moveUp();
            else this.player.moveDown();
            intervaloMovimiento = setInterval(() => {
                if (moverDireccion > 0) this.player.moveUp();
                else if (moverDireccion < 0) this.player.moveDown();
            }, 60);
        };

        canvas.addEventListener('pointerdown', (e) => {
            e.preventDefault();
            try { canvas.setPointerCapture(e.pointerId); } catch (_) { /* noop */ }

            // Modo flappy: cada pulsación aletea
            if (currentState === STATE_FLAPPY && this.gameRunning) {
                this.player.flap();
                return;
            }

            // Modo dron: zona superior = subir, zona inferior = bajar
            if (currentState === STATE_FIRST && this.gameRunning) {
                const rect = canvas.getBoundingClientRect();
                const yRelativa = e.clientY - rect.top;
                iniciarMovimiento(yRelativa < rect.height / 2 ? 1 : -1);
            }
        }, { passive: false });

        const soltarPuntero = (e) => {
            if (e) e.preventDefault();
            detenerMovimiento();
        };
        canvas.addEventListener('pointerup', soltarPuntero, { passive: false });
        canvas.addEventListener('pointercancel', soltarPuntero);

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
        vidas = 2;
        currentState = STATE_FIRST;
        this.gameRunning = true;
        this.gameOver = false;
        this.score = 0;
        this.speed = this.baseSpeed;
        this.player = new Player();
        this.pipes = [];
        this.lastSpawn = Date.now();
        this.scoreSubmitted = false;
        gameOverlay.classList.add('hidden');
        if (loadingStatus) loadingStatus.textContent = 'Vida 1/2 - Mueve el dron con W/S o flechas';
        if (!this._loopActive) {
            this._loopActive = true;
            this.update();
        }
    }

    spawnPipe() {
        const now = Date.now();
        const speedRatio = this.baseSpeed / this.speed;
        const dynamicInterval = this.spawnInterval * speedRatio;
        const minInterval = Math.max(550, dynamicInterval);
        if (now - this.lastSpawn > minInterval) {
            this.pipes.push(new Pipe());
            this.lastSpawn = now;
        }
    }

    checkCollisions() {
        const pb = this.player.getHitBox();
        if (this.player.y + this.player.height > canvas.height || this.player.y < 0) {
            this.handleHit();
            return;
        }
        for (const pipe of this.pipes) {
            if (rectCollide(pb, pipe.getTopHitBox()) || rectCollide(pb, pipe.getBottomHitBox())) {
                this.handleHit();
                return;
            }
        }
    }

    handleHit() {
        if (vidas > 1) {
            vidas--;
            currentState = STATE_TRANSITION;
            transitionStart = Date.now();
            gameOverlay.classList.remove('hidden');
            const t = document.querySelector('#gameOverlay h2');
            if (t) t.textContent = `¡Impacto! Vida ${vidas}/2 - Cambio a modo flappy en 3s`;
            if (loadingStatus) loadingStatus.textContent = `¡Impacto! Te queda vida ${vidas}/2. Cambio a modo flappy...`;
        } else {
            this.endGame();
        }
    }

    endGame() {
        this.gameOver = true;
        this.gameRunning = false;
        this.showGameOver();
        this.submitScore();
    }

    showGameOver() {
        gameOverlay.classList.remove('hidden');
        scoreDisplay.textContent = this.score;
        const t = document.querySelector('#gameOverlay h2');
        if (t) t.textContent = '¡Juego terminado! Presiona ENTER para reiniciar';
    }

    update() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        const bg = spriteCache['background'];
        if (bg) {
            ctx.drawImage(bg, 0, 0, canvas.width, canvas.height);
        }

        if (this.gameOver) {
            this._loopActive = false;
            return;
        }

        if (currentState === STATE_TRANSITION) {
            const elapsed = (Date.now() - transitionStart) / 1000;
            const remaining = Math.max(0, Math.ceil(3 - elapsed));
            if (loadingStatus) loadingStatus.textContent = `Cambio a modo flappy en ${remaining}s...`;
            const t = document.querySelector('#gameOverlay h2');
            if (t) t.textContent = `¡Impacto! Vida ${vidas}/2 - Modo flappy en ${remaining}s`;
            if (elapsed >= 3) {
                currentState = STATE_FLAPPY;
                this.gameOver = false;
                this.player = new Player();
                this.pipes = [];
                this.lastSpawn = Date.now();
                gameOverlay.classList.add('hidden');
                if (loadingStatus) loadingStatus.textContent = 'Modo Flappy Bird - ESPACIO para saltar';
            }
            requestAnimationFrame(() => this.update());
            return;
        }

        if (!this.gameRunning) {
            this._loopActive = false;
            return;
        }

        this.speed = this.baseSpeed + Math.floor(this.score / 50);
        this.player.update(currentState);
        this.spawnPipe();
        this.player.draw();

        for (let i = this.pipes.length - 1; i >= 0; i--) {
            this.pipes[i].update(this.speed);
            this.pipes[i].draw();
            if (!this.pipes[i].passed && this.pipes[i].x + this.pipes[i].width < this.player.x) {
                this.pipes[i].passed = true;
                this.score += 10;
            }
            if (this.pipes[i].isOffScreen()) this.pipes.splice(i, 1);
        }

        this.checkCollisions();
        currentScore.textContent = this.score;
        speedDisplay.textContent = (this.speed / this.baseSpeed).toFixed(1) + 'x';
        if (levelDisplay) levelDisplay.textContent = Math.floor(this.score / 100) + 1;
        requestAnimationFrame(() => this.update());
    }

    async submitScore() {
        if (this.scoreSubmitted || this.score === 0) return;
        this.scoreSubmitted = true;
        if (IS_AUTHENTICATED && typeof GAME_SLUG !== 'undefined') {
            try {
                await API.post('/api/score/submit', { game_slug: GAME_SLUG, score: this.score, level_reached: 1 });
                if (typeof loadLeaderboard === 'function') setTimeout(() => loadLeaderboard(GAME_SLUG), 500);
            } catch (err) { console.warn('No se pudo enviar la puntuación:', err); }
        }
    }
}

const game = new Game();
window.addEventListener('load', async () => {
    try {
        await loadSprites();
        resizeCanvas();
        if (loadingStatus) loadingStatus.textContent = 'Vida 1/2 - Mueve el dron con W/S o flechas';
        const t = document.querySelector('#gameOverlay h2');
        if (t) t.textContent = 'Vida 1/2 - Usa W/S o flechas para mover el dron';
    } catch (e) {
        if (loadingStatus) {
            loadingStatus.textContent = 'Usando gráficos básicos';
            loadingStatus.style.color = '#ff006e';
        }
    }
});
