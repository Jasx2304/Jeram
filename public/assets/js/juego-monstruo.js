// =========================================================
// juego-monstruo.js → "Corre Scary Monster"
// Runner infinito en canvas 2D: el jugador corre, salta y se
// agacha para esquivar obstáculos. A mayor distancia, más velocidad.
// Al perder se envía la puntuación con submitScore() (puntuaciones.js).
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

const SPRITE_PATHS = {
    run: ['assets/imagenes/sprites/sprites_scary_monster/Parado.png', 'assets/imagenes/sprites/sprites_scary_monster/camina_1.png', 'assets/imagenes/sprites/sprites_scary_monster/camina_2.png'],
    duck: ['assets/imagenes/sprites/sprites_scary_monster/agachado_1.png', 'assets/imagenes/sprites/sprites_scary_monster/agachado_2.png'],
    death: 'assets/imagenes/sprites/sprites_scary_monster/muerto.png',
    obstacle1: 'assets/imagenes/sprites/sprites_scary_monster/obstaculo_cactus_1.png',
    obstacle2: 'assets/imagenes/sprites/sprites_scary_monster/obstaculo_cactus_2.png',
    background: 'assets/imagenes/sprites/sprites_scary_monster/fondo_scary_monster.png'
};

const spriteCache = {};
let spritesLoaded = false;

async function loadSprites() {
    return new Promise((resolve) => {
        let loaded = 0;
        let total = 0;
        total += SPRITE_PATHS.run.length;
        total += SPRITE_PATHS.duck.length;
        total += 1; // death
        total += 2; // obstacles
        total += 1; // background

        const checkComplete = () => {
            loaded++;
            if (loadingStatus) {
                loadingStatus.textContent = `Cargando sprites... ${loaded}/${total}`;
            }
            if (loaded === total) {
                spritesLoaded = true;
                if (loadingStatus) {
                    loadingStatus.textContent = 'Sprites cargados ✅';
                    loadingStatus.style.color = '#00ff88';
                }
                resolve();
            }
        };

        SPRITE_PATHS.run.forEach((path, index) => {
            const img = new Image();
            img.src = path;
            img.onload = () => {
                spriteCache[`run${index}`] = img;
                checkComplete();
            };
            img.onerror = () => {
                checkComplete();
            };
        });

        SPRITE_PATHS.duck.forEach((path, index) => {
            const img = new Image();
            img.src = path;
            img.onload = () => {
                spriteCache[`duck${index}`] = img;
                checkComplete();
            };
            img.onerror = () => {
                checkComplete();
            };
        });

        const deathImg = new Image();
        deathImg.src = SPRITE_PATHS.death;
        deathImg.onload = () => {
            spriteCache['death'] = deathImg;
            checkComplete();
        };
        deathImg.onerror = () => {
            checkComplete();
        };

        ['obstacle1', 'obstacle2', 'background'].forEach(key => {
            const img = new Image();
            img.src = SPRITE_PATHS[key];
            img.onload = () => {
                spriteCache[key] = img;
                checkComplete();
            };
            img.onerror = () => {
                checkComplete();
            };
        });
    });
}

class Player {
    constructor() {
        this.width = 80;
        this.height = 90;
        this.normalHeight = 90;
        this.crouchHeight = 60;
        this.crouchOffset = 30;
        this.duckSpeed = 20;
        this.fastFallMultiplier = 2.2;
        this.duckOffset = 0;
        this.x = canvas.width * 0.1;
        this.y = canvas.height - this.height - 10;
        this.velocityY = 0;
        this.velocityX = 0;
        this.jumping = false;
        this.ducking = false;
        this.dead = false;
        this.jumpPower = 15;
        this.gravity = 0.6;
        this.groundY = this.y;
        this.animationFrame = 0;
        this.animationSpeed = 0.15;
        this.duckAnimationSpeed = 0.1;
    }

    update() {
        // Si la pantalla cambió de tamaño (pantalla completa), el suelo se recalcula
        this.groundY = canvas.height - this.height - 10;
        if (!this.jumping) {
            this.y = this.groundY;
        }

        if (this.jumping) {
            if (this.ducking) {
                this.velocityY += this.gravity * this.fastFallMultiplier;
            } else {
                this.velocityY += this.gravity;
            }
            this.y += this.velocityY;
            if (this.y >= this.groundY) {
                this.y = this.groundY;
                this.velocityY = 0;
                this.jumping = false;
            }
        }

        if (!this.jumping) {
            if (this.ducking) {
                this.duckOffset = Math.min(this.crouchOffset, this.duckOffset + this.duckSpeed);
            } else {
                this.duckOffset = Math.max(0, this.duckOffset - this.duckSpeed);
            }
        }

        if (this.x < 0) this.x = 0;
        if (this.x + this.width > canvas.width) this.x = canvas.width - this.width;

        if (!this.jumping && !this.dead) {
            if (this.ducking) {
                this.animationFrame += this.duckAnimationSpeed;
            } else {
                this.animationFrame += this.animationSpeed;
            }
        }
    }

    jump() {
        if (!this.jumping && !this.ducking) {
            this.velocityY = -this.jumpPower;
            this.jumping = true;
        }
    }

    duck(isDucking) {
        this.ducking = isDucking;
        if (!isDucking) {
            this.animationFrame = 0;
        }
    }

    die() {
        this.dead = true;
    }

    draw() {
        let spriteToUse = null;

        if (this.dead && spriteCache['death']) {
            ctx.drawImage(spriteCache['death'], this.x, this.y, this.width, this.height);
            return;
        }

        const duckTop = this.y + this.duckOffset;
        const isCrouching = this.duckOffset > 0;

        if (isCrouching) {
            const duckIndex = Math.floor(this.animationFrame) % SPRITE_PATHS.duck.length;
            spriteToUse = spriteCache[`duck${duckIndex}`];
            if (spriteToUse) {
                ctx.drawImage(spriteToUse, this.x, duckTop, this.width, this.crouchHeight);
            }
        } else {
            const runIndex = Math.floor(this.animationFrame) % SPRITE_PATHS.run.length;
            spriteToUse = spriteCache[`run${runIndex}`];
            if (spriteToUse) {
                ctx.drawImage(spriteToUse, this.x, this.y, this.width, this.height);
            }
        }

        if (!spriteToUse) {
            this.drawFallback();
        }
    }

    drawFallback() {
        const duckTop = this.y + this.duckOffset;
        const isCrouching = this.duckOffset > 0;

        if (isCrouching) {
            ctx.fillStyle = '#ff00fb';
            ctx.fillRect(this.x, duckTop, this.width, this.crouchHeight);
            ctx.strokeStyle = '#00d4ff';
            ctx.lineWidth = 2;
            ctx.strokeRect(this.x, duckTop, this.width, this.crouchHeight);
        } else {
            ctx.fillStyle = '#ff00fb';
            ctx.fillRect(this.x, this.y, this.width, this.height);
            ctx.strokeStyle = '#00d4ff';
            ctx.lineWidth = 2;
            ctx.strokeRect(this.x, this.y, this.width, this.height);
        }

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(this.x + 8, this.y + 10, 6, 8);
        ctx.fillRect(this.x + 26, this.y + 10, 6, 8);
        ctx.fillStyle = '#000000';
        ctx.fillRect(this.x + 9, this.y + 11, 4, 6);
        ctx.fillRect(this.x + 27, this.y + 11, 4, 6);
    }

    getHitBox() {
        // Hitbox más justa: se recorta el espacio vacío del sprite
        // (el jugador parece más pequeño que su rectángulo).
        const insetX = this.width * 0.10;
        const insetTop = this.height * 0.08;
        const insetBottom = this.height * 0.05;
        if (this.duckOffset > 0) {
            return {
                x: this.x + insetX,
                y: this.y + this.duckOffset + insetTop,
                width: this.width - insetX * 2,
                height: this.crouchHeight - insetTop - insetBottom
            };
        }
        return {
            x: this.x + insetX,
            y: this.y + insetTop,
            width: this.width - insetX * 2,
            height: this.height - insetTop - insetBottom
        };
    }
}

class Obstacle {
    constructor(speed, type = 'default') {
        this.width = 40 + Math.random() * 20;
        this.height = 50 + Math.random() * 25;
        this.x = canvas.width;
        this.y = canvas.height - this.height - 10;
        this.speed = speed;
        this.type = type;
        this.spriteKey = Math.random() < 0.5 ? 'obstacle1' : 'obstacle2';
    }

    update() {
        this.x -= this.speed;
        this.y = canvas.height - this.height - 10;
    }

    draw() {
        const s = spriteCache[this.spriteKey];
        if (s) {
            ctx.drawImage(s, this.x, this.y, this.width, this.height);
        } else if (this.type === 'spike') {
            ctx.fillStyle = '#ff006e';
            ctx.beginPath();
            ctx.moveTo(this.x + this.width / 2, this.y);
            ctx.lineTo(this.x + this.width, this.y + this.height);
            ctx.lineTo(this.x, this.y + this.height);
            ctx.closePath();
            ctx.fill();
            ctx.strokeStyle = '#ff00fb';
            ctx.lineWidth = 2;
            ctx.stroke();
        } else {
            ctx.fillStyle = '#ff006e';
            ctx.fillRect(this.x, this.y, this.width, this.height);
            ctx.strokeStyle = '#ff00fb';
            ctx.lineWidth = 2;
            ctx.strokeRect(this.x, this.y, this.width, this.height);
        }
    }

    isOffScreen() {
        return this.x + this.width < 0;
    }

    getHitBox() {
        // Hitbox del cactus recortada al área visible real de la imagen
        // (los sprites tienen márgenes transparentes de ~3-12% por lado).
        const insetX = this.width * 0.18;
        const insetTop = this.height * 0.10;
        const insetBottom = this.height * 0.04;
        return {
            x: this.x + insetX,
            y: this.y + insetTop,
            width: this.width - insetX * 2,
            height: this.height - insetTop - insetBottom
        };
    }
}

class Game {
    constructor() {
        this.player = new Player();
        this.obstacles = [];
        this.score = 0;
        this.gameRunning = false;
        this.gameOver = false;
        this.speed = 5;
        this.baseSpeed = 5;
        this.gameStartTime = 0;
        this.level = 1;
        this.maxSpeed = 18;
        this.minGapPx = 420;      // distancia mínima en píxeles entre obstáculos
        this.gapObjetivo = 0;     // px que debe recorrer antes del próximo obstáculo
        this.distDesdeSpawn = 0;  // px recorridos desde el último obstáculo
        this.speedIncreasePerSecond = 0.3;
        this.scoreSubmitted = false;

        this.setupControls();
    }

    setupControls() {
        document.addEventListener('keydown', (e) => {
            if (e.code === 'Space' || e.code === 'ArrowUp') {
                e.preventDefault();
                if (this.gameRunning) {
                    this.player.jump();
                }
            }
            if (e.code === 'ArrowDown') {
                e.preventDefault();
                if (this.gameRunning) {
                    this.player.duck(true);
                }
            }
            if (e.code === 'Enter') {
                e.preventDefault();
                if (!this.gameRunning && !this.gameOver) {
                    this.start();
                } else if (this.gameOver) {
                    this.start();
                }
            }
        });

        document.addEventListener('keyup', (e) => {
            if (e.code === 'ArrowDown') {
                if (this.gameRunning) {
                    this.player.duck(false);
                }
            }
        });

        // --- Controles con puntero (mouse + táctil + lápiz) ---
        // Clic/tap = saltar
        // Arrastrar hacia abajo = agacharse (mientras dure el arrastre)
        let touchStartX = 0;
        let touchStartY = 0;
        let touchMoved = false;
        let duckingByTouch = false;
        let punteroActivo = false;

        canvas.addEventListener('pointerdown', (e) => {
            e.preventDefault();
            punteroActivo = true;
            try { canvas.setPointerCapture(e.pointerId); } catch (_) { /* noop */ }
            touchStartX = e.clientX;
            touchStartY = e.clientY;
            touchMoved = false;
        }, { passive: false });

        canvas.addEventListener('pointermove', (e) => {
            e.preventDefault();
            if (!punteroActivo || !this.gameRunning) return;
            const dx = e.clientX - touchStartX;
            const dy = e.clientY - touchStartY;
            if (Math.abs(dx) > 15 || Math.abs(dy) > 15) {
                touchMoved = true;
            }
            if (dy > 20) {
                // Arrastre hacia abajo → agacharse
                if (!duckingByTouch) {
                    duckingByTouch = true;
                    this.player.duck(true);
                }
            } else if (dy < 10 && duckingByTouch) {
                // Volvió hacia arriba → levantarse
                duckingByTouch = false;
                this.player.duck(false);
            }
        }, { passive: false });

        canvas.addEventListener('pointerup', (e) => {
            e.preventDefault();
            punteroActivo = false;
            if (duckingByTouch) {
                duckingByTouch = false;
                this.player.duck(false);
            }
            // Clic sin arrastrar → saltar
            if (!touchMoved && this.gameRunning) {
                this.player.jump();
            }
        }, { passive: false });

        canvas.addEventListener('pointercancel', () => {
            punteroActivo = false;
            if (duckingByTouch) {
                duckingByTouch = false;
                this.player.duck(false);
            }
        });

        gameOverlay.addEventListener('click', () => {
            if (!this.gameRunning && !this.gameOver) {
                this.start();
            } else if (this.gameOver) {
                this.start();
            }
        });
    }

    start() {
        if (!spritesLoaded) {
            if (loadingStatus) {
                loadingStatus.textContent = 'Cargando sprites... Espera un momento';
                loadingStatus.style.color = '#ff00fb';
            }
            loadSprites().then(() => {
                this.startGame();
            });
            return;
        }
        this.startGame();
    }

    startGame() {
        this.gameRunning = true;
        this.gameOver = false;
        this.score = 0;
        this.speed = this.baseSpeed;
        this.level = 1;
        this.player = new Player();
        this.obstacles = [];
        this.gapObjetivo = this.nuevoGap();
        this.distDesdeSpawn = 0;
        this.gameStartTime = Date.now();
        this.scoreSubmitted = false;
        gameOverlay.classList.add('hidden');
        this.update();
    }

    spawnObstacle() {
        // La decisión se toma por DISTANCIA recorrida (no por tiempo):
        // el hueco mínimo se respeta igual con 15 fps que con 120 fps.
        if (this.distDesdeSpawn >= this.gapObjetivo) {
            const type = Math.random() > 0.7 ? 'spike' : 'default';
            this.obstacles.push(new Obstacle(this.speed, type));
            this.distDesdeSpawn -= this.gapObjetivo;
            this.gapObjetivo = this.nuevoGap();
        }
    }

    checkCollisions() {
        const playerHitBox = this.player.getHitBox();
        for (let obstacle of this.obstacles) {
            const obstacleHitBox = obstacle.getHitBox();
            if (
                playerHitBox.x < obstacleHitBox.x + obstacleHitBox.width &&
                playerHitBox.x + playerHitBox.width > obstacleHitBox.x &&
                playerHitBox.y < obstacleHitBox.y + obstacleHitBox.height &&
                playerHitBox.y + playerHitBox.height > obstacleHitBox.y
            ) {
                this.gameOver = true;
                this.gameRunning = false;
                this.player.die();
                this.showGameOver();
                this.submitScore();
            }
        }
    }

    async submitScore() {
        if (this.scoreSubmitted || this.score === 0) return;
        this.scoreSubmitted = true;

        if (IS_AUTHENTICATED && typeof GAME_SLUG !== 'undefined') {
            try {
                await API.post('/api/score/submit', {
                    game_slug: GAME_SLUG,
                    score: this.score,
                    level_reached: this.level,
                });
                if (typeof loadLeaderboard === 'function') {
                    setTimeout(() => loadLeaderboard(GAME_SLUG), 500);
                }
            } catch (err) {
                console.warn('No se pudo enviar la puntuación (¿sesión?):', err);
            }
        }
    }

    updateDifficulty() {
        const speedByScore = (this.score / 500) * 0.5;
        const elapsedSeconds = (Date.now() - this.gameStartTime) / 1000;
        const speedByTime = this.speedIncreasePerSecond * elapsedSeconds;
        this.speed = Math.min(this.baseSpeed + speedByScore + speedByTime, this.maxSpeed);

        const newLevel = Math.floor(this.score / 500) + 1;
        if (newLevel !== this.level) {
            this.level = newLevel;
            // La dificultad sube con la velocidad; el intervalo NO se recorta
            // aquí para no romper la distancia mínima garantizada.
        }
    }

    nuevoGap() {
        // Distancia aleatoria (px) hasta el próximo obstáculo.
        // El mínimo `minGapPx` se respeta SIEMPRE, sin importar el FPS.
        const factor = Math.min(this.score * 0.15, 700);
        const min = this.minGapPx;
        const max = Math.max(min + 250, 1500 - factor);
        return Math.random() * (max - min) + min;
    }

    showGameOver() {
        gameOverlay.classList.remove('hidden');
        scoreDisplay.textContent = this.score;
        const overlayTitle = document.querySelector('#gameOverlay h2');
        if (overlayTitle) {
            overlayTitle.textContent = '¡Juego terminado! Presiona ENTER para reiniciar';
        }
    }

    update() {
        if (!this.gameRunning) return;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        const bg = spriteCache['background'];
        if (bg) {
            ctx.drawImage(bg, 0, 0, canvas.width, canvas.height);
        } else {
            ctx.fillStyle = 'rgba(10, 14, 39, 0.3)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }

        this.updateDifficulty();

        this.player.update();
        this.player.draw();

        this.distDesdeSpawn += this.speed;
        this.spawnObstacle();

        for (let i = this.obstacles.length - 1; i >= 0; i--) {
            this.obstacles[i].speed = this.speed;
            this.obstacles[i].update();
            this.obstacles[i].draw();

            if (this.obstacles[i].isOffScreen()) {
                this.obstacles.splice(i, 1);
                this.score += 10;
            }
        }

        this.checkCollisions();

        currentScore.textContent = this.score;
        speedDisplay.textContent = (this.speed / this.baseSpeed).toFixed(1) + 'x';
        if (levelDisplay) levelDisplay.textContent = this.level;

        ctx.strokeStyle = '#00d4ff';
        ctx.lineWidth = 2;
        ctx.setLineDash([10, 5]);
        ctx.beginPath();
        ctx.moveTo(0, canvas.height - 10);
        ctx.lineTo(canvas.width, canvas.height - 10);
        ctx.stroke();
        ctx.setLineDash([]);

        requestAnimationFrame(() => this.update());
    }
}

const game = new Game();

window.addEventListener('load', async () => {
    try {
        await loadSprites();
        resizeCanvas();
        if (loadingStatus) {
            loadingStatus.textContent = 'Sprites listos ✅ Presiona ENTER o haz clic';
        }
        const overlayTitle = document.querySelector('#gameOverlay h2');
        if (overlayTitle) {
            overlayTitle.textContent = 'Presiona ENTER o haz clic para jugar';
        }
    } catch (error) {
        if (loadingStatus) {
            loadingStatus.textContent = 'Error cargando sprites - usando gráficos básicos';
            loadingStatus.style.color = '#ff006e';
        }
    }
});