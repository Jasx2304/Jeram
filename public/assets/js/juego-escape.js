// =========================================================
// juego-escape.js → "Escape Neon"
// Runner neon: correr/saltar/esquivar barriles, tuberías y láseres.
// Usa sprites de assets/imagenes/escape-neon/ y al terminar la partida
// envía el puntaje con submitScore() (puntuaciones.js).
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

canvas.classList.add('escape-canvas');

const SPRITE_PATHS = {
    run: [
        'assets/imagenes/sprites/sprites_escape_neon/correr_1.png',
        'assets/imagenes/sprites/sprites_escape_neon/correr_2.png',
        'assets/imagenes/sprites/sprites_escape_neon/correr_3.png',
        'assets/imagenes/sprites/sprites_escape_neon/correr_4.png'
    ],
    duck: [
        'assets/imagenes/sprites/sprites_escape_neon/agachado_neon.png',
        'assets/imagenes/sprites/sprites_escape_neon/agachado_neon_2.png'
    ],
    barrel: 'assets/imagenes/sprites/sprites_escape_neon/obstaculo_rosado.png',
    pipe: 'assets/imagenes/sprites/sprites_escape_neon/obstaculo_azul.png',
    laser: 'assets/imagenes/sprites/sprites_escape_neon/obstaculo_amarillo_agachable.png',
    background: 'assets/imagenes/sprites/sprites_escape_neon/fondo_escape_neon.png',
};

const spriteCache = {};
let spritesLoaded = false;

function loadSprites() {
    return new Promise((resolve) => {
        let loaded = 0;
        const paths = [];
        Object.entries(SPRITE_PATHS).forEach(([key, path]) => {
            if (Array.isArray(path)) {
                path.forEach((p, i) => paths.push({ key, index: i, path: p }));
            } else {
                paths.push({ key, path });
            }
        });
        const total = paths.length;
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

const LANES = 3;

class Player {
    constructor(canvasW, canvasH) {
        this.lane = 1;
        this.ducking = false;
        this.width = 40;
        this.height = 55;
        this.duckHeight = 42;
        this.canvasW = canvasW;
        this.canvasH = canvasH;
        this.laneW = canvasW / LANES;
        this.baseY = canvasH - this.height - 30;
    }

    get x() {
        return this.lane * this.laneW + (this.laneW - this.width) / 2;
    }

    get y() {
        return this.ducking ? this.baseY + (this.height - this.duckHeight) : this.baseY;
    }

    get drawH() {
        return this.ducking ? this.duckHeight : this.height;
    }

    moveLeft() {
        if (this.lane > 0) this.lane--;
    }

    moveRight() {
        if (this.lane < LANES - 1) this.lane++;
    }

    duck(d) {
        this.ducking = d;
    }

    draw() {
        const sprites = this.ducking ? spriteCache['duck'] : spriteCache['run'];
        if (sprites && sprites.length > 0) {
            const idx = Math.floor(this._animFrame || 0) % sprites.length;
            const s = sprites[idx];
            if (s) {
                ctx.drawImage(s, this.x, this.y, this.width, this.drawH);
                this._animFrame = (this._animFrame || 0) + 0.15;
                return;
            }
        }
        ctx.fillStyle = this.ducking ? '#9d4edd' : '#ff00fb';
        ctx.fillRect(this.x, this.y, this.width, this.drawH);
        if (!this.ducking) {
            ctx.fillStyle = '#fff';
            ctx.fillRect(this.x + 6, this.y + 8, 6, 8);
            ctx.fillRect(this.x + this.width - 12, this.y + 8, 6, 8);
            ctx.fillStyle = '#000';
            ctx.fillRect(this.x + 7, this.y + 9, 4, 6);
            ctx.fillRect(this.x + this.width - 11, this.y + 9, 4, 6);
        }
    }

    getHitBox() {
        return { x: this.x + 4, y: this.y + 4, width: this.width - 8, height: this.drawH - 8 };
    }
}

class FallingObstacle {
    constructor(lane, type, speed, canvasW, canvasH) {
        this.lane = lane;
        this.type = type;
        this.speed = speed;
        this.canvasW = canvasW;
        this.canvasH = canvasH;
        this.laneW = canvasW / LANES;
        this.width = 50;
        this.height = 50;
        this.x = this.lane * this.laneW + (this.laneW - this.width) / 2;
        this.y = -this.height;
        this.passed = false;
        this.requiresDuck = (type === 'laser');

        switch(type) {
            case 'barrel': this.height = 55; this.width = 50; break;
            case 'pipe': this.height = 40; this.width = 55; break;
            case 'laser': this.height = 32; this.width = this.laneW * 0.7; this.x = this.lane * this.laneW + (this.laneW - this.width) / 2; break;
        }
    }

    update() {
        this.y += this.speed;
    }

    draw() {
        const s = spriteCache[this.type];
        if (s) {
            ctx.drawImage(s, this.x, this.y, this.width, this.height);
        } else {
            const colors = { barrel: '#ff006e', pipe: '#00d4ff', laser: '#ffd700' };
            const color = colors[this.type] || '#ff00fb';
            ctx.fillStyle = color;
            ctx.shadowColor = color;
            ctx.shadowBlur = 15;
            ctx.fillRect(this.x, this.y, this.width, this.height);
            ctx.shadowBlur = 0;
        }
    }

    isOffScreen() {
        return this.y > this.canvasH;
    }

    getHitBox() {
        return { x: this.x + 2, y: this.y + 2, width: this.width - 4, height: this.height - 4 };
    }
}

function rectCollide(a, b) {
    return a.x < b.x + b.width && a.x + a.width > b.x &&
           a.y < b.y + b.height && a.y + a.height > b.y;
}

class Game {
    constructor() {
        this.player = new Player(canvas.width, canvas.height);
        this.obstacles = [];
        this.score = 0;
        this.gameRunning = false;
        this.gameOver = false;
        this.fallSpeed = 3;
        this.baseSpeed = 3;
        this.maxSpeed = 12;
        this.spawnTimer = 0;
        this.spawnInterval = 1000;
        this.scoreSubmitted = false;
        this.laneW = canvas.width / LANES;
        this.animFrame = 0;
        this.setupControls();
    }

    setupControls() {
        document.addEventListener('keydown', (e) => {
            if (e.code === 'ArrowLeft') { e.preventDefault(); if (this.gameRunning) this.player.moveLeft(); }
            if (e.code === 'ArrowRight') { e.preventDefault(); if (this.gameRunning) this.player.moveRight(); }
            if (e.code === 'ArrowDown') { e.preventDefault(); if (this.gameRunning) this.player.duck(true); }
            if (e.code === 'Enter') {
                e.preventDefault();
                if (!this.gameRunning && !this.gameOver) this.start();
                else if (this.gameOver) this.start();
            }
        });
        document.addEventListener('keyup', (e) => {
            if (e.code === 'ArrowDown') { if (this.gameRunning) this.player.duck(false); }
        });

        // --- Controles con puntero (mouse + táctil + lápiz) ---
        // Clic/tap en un carril adyacente → moverse a ese carril.
        // Carril propio o no adyacente → no hace nada.
        canvas.addEventListener('pointerdown', (e) => {
            e.preventDefault();
            if (!this.gameRunning) return;

            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const anchoCarril = rect.width / LANES;
            const carrilTocado = Math.max(0, Math.min(LANES - 1, Math.floor(x / anchoCarril)));

            const diferencia = carrilTocado - this.player.lane;
            if (diferencia === 1) {
                this.player.moveRight();
            } else if (diferencia === -1) {
                this.player.moveLeft();
            }
            // carril propio o no adyacente → ignorado
        }, { passive: false });

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
        this.score = 0;
        this.fallSpeed = this.baseSpeed;
        this.player = new Player(canvas.width, canvas.height);
        this.obstacles = [];
        this.scoreSubmitted = false;
        this.laneW = canvas.width / LANES;
        gameOverlay.classList.add('hidden');
        this.update();
    }

    spawnObstacle() {
        const now = Date.now();
        const interval = Math.max(400, this.spawnInterval - this.score * 1.5);
        if (now - this.spawnTimer > interval) {
            const lane = Math.floor(Math.random() * LANES);
            const types = ['barrel', 'pipe', 'laser'];
            const type = types[Math.floor(Math.random() * types.length)];

            const blocked = this.obstacles.some(o => o.lane === lane && o.y < 60);
            if (!blocked) {
                this.obstacles.push(new FallingObstacle(lane, type, this.fallSpeed, canvas.width, canvas.height));
            }
            this.spawnTimer = now;
        }
    }

    checkCollisions() {
        const pb = this.player.getHitBox();
        for (const obs of this.obstacles) {
            if (obs.lane !== this.player.lane) continue;
            const ob = obs.getHitBox();
            if (rectCollide(pb, ob)) {
                if (obs.requiresDuck && this.player.ducking) continue;
                this.endGame();
                return;
            }
        }
    }

    endGame() {
        this.gameOver = true;
        this.gameRunning = false;
        this.showGameOver();
        this.submitScore();
    }

    async submitScore() {
        if (this.scoreSubmitted || this.score === 0) return;
        this.scoreSubmitted = true;
        if (IS_AUTHENTICATED && typeof GAME_SLUG !== 'undefined') {
            try {
                await API.post('/api/score/submit', { game_slug: GAME_SLUG, score: this.score, level_reached: Math.floor(this.score / 100) + 1 });
                if (typeof loadLeaderboard === 'function') setTimeout(() => loadLeaderboard(GAME_SLUG), 500);
            } catch (err) { console.warn('No se pudo enviar la puntuación:', err); }
        }
    }

    showGameOver() {
        gameOverlay.classList.remove('hidden');
        scoreDisplay.textContent = this.score;
        const t = document.querySelector('#gameOverlay h2');
        if (t) t.textContent = '¡Juego terminado! Presiona ENTER para reiniciar';
    }

    drawLanes() {
        for (let i = 0; i <= LANES; i++) {
            const lx = i * this.laneW;
            ctx.strokeStyle = 'rgba(0, 212, 255, 0.12)';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(lx, 0);
            ctx.lineTo(lx, canvas.height);
            ctx.stroke();
        }

        const baseY = canvas.height - 25;
        ctx.strokeStyle = 'rgba(0, 212, 255, 0.25)';
        ctx.lineWidth = 1;
        ctx.setLineDash([10, 8]);
        ctx.beginPath();
        ctx.moveTo(0, baseY);
        ctx.lineTo(canvas.width, baseY);
        ctx.stroke();
        ctx.setLineDash([]);
    }

    update() {
        if (!this.gameRunning) return;

        // Si la pantalla cambió de tamaño (pantalla completa), recalcular carriles
        this.laneW = canvas.width / LANES;
        this.player.canvasW = canvas.width;
        this.player.canvasH = canvas.height;
        this.player.laneW = this.laneW;
        this.player.baseY = canvas.height - this.player.height - 30;
        for (const obs of this.obstacles) {
            obs.canvasW = canvas.width;
            obs.canvasH = canvas.height;
            obs.laneW = this.laneW;
            obs.x = obs.lane * this.laneW + (this.laneW - obs.width) / 2;
        }

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        const bg = spriteCache['background'];
        if (bg) {
            ctx.drawImage(bg, 0, 0, canvas.width, canvas.height);
        } else {
            ctx.fillStyle = 'rgba(10, 14, 39, 0.3)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }

        this.animFrame++;
        this.fallSpeed = Math.min(this.baseSpeed + Math.floor(this.score / 40), this.maxSpeed);

        this.spawnObstacle();

        for (let i = this.obstacles.length - 1; i >= 0; i--) {
            this.obstacles[i].update();
            this.obstacles[i].draw();
            if (!this.obstacles[i].passed && this.obstacles[i].y > this.player.y + 20) {
                this.obstacles[i].passed = true;
                this.score += 10;
            }
            if (this.obstacles[i].isOffScreen()) this.obstacles.splice(i, 1);
        }

        this.drawLanes();
        this.player.draw();
        this.checkCollisions();

        currentScore.textContent = this.score;
        speedDisplay.textContent = (this.fallSpeed / this.baseSpeed).toFixed(1) + 'x';
        if (levelDisplay) levelDisplay.textContent = Math.floor(this.score / 100) + 1;

        requestAnimationFrame(() => this.update());
    }
}

const game = new Game();
window.addEventListener('load', async () => {
    try {
        await loadSprites();
        resizeCanvas();
        game.laneW = canvas.width / LANES;
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
