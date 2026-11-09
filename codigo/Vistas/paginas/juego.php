<?php
// Página de juego: embebe el <canvas> y carga el JS del juego
// que corresponda a su slug + los scripts de ranking/comentarios.
// Recibe $juego y $controles desde ControladorJuegos::mostrar().
use JERAM\Utilidades\Recursos;
use JERAM\Autenticacion\Sesion;
$usuarioActual = Sesion::usuarioActual();

// GAME_SLUG / IS_AUTHENTICATED los leen los JS del juego y las APIs
$scriptsExtra = '<script>
const GAME_SLUG = ' . json_encode($juego['slug']) . ';
const IS_AUTHENTICATED = ' . ($usuarioActual ? 'true' : 'false') . ';
</script>';
// Cada juego tiene su propio archivo JS de la carpeta assets/js
$jsDelJuego = match($juego['slug']) {
    'scary-monster' => 'juego-monstruo.js',
    'el-encargo' => 'juego-encargo.js',
    'linea-de-produccion' => 'juego-produccion.js',
    'escape-neon' => 'juego-escape.js',
    default => 'juego-monstruo.js',
};
$scriptsExtra .= '<script src="' . Recursos::versionada(Recursos::js($jsDelJuego)) . '"></script>';
$scriptsExtra .= '<script src="' . Recursos::versionada(Recursos::js('puntuaciones.js')) . '"></script>';
$scriptsExtra .= '<script src="' . Recursos::versionada(Recursos::js('comentarios.js')) . '"></script>';
$scriptsExtra .= '<script src="' . Recursos::versionada(Recursos::js('pantalla-completa.js')) . '"></script>';

// Instrucciones de controles táctiles para celular (se muestran solo en pantallas táctiles)
$controlesMovil = match ($juego['slug']) {
    'scary-monster' => 'Toca la pantalla para saltar · Arrastra el dedo hacia abajo para agacharte',
    'el-encargo' => 'Toca la mitad superior: el dron sube · Toca la mitad inferior: el dron baja · En modo flappy, toca para aletear',
    'escape-neon' => 'Toca el carril de al lado para moverte hacia él · Solo puedes moverte un carril cada vez',
    'linea-de-produccion' => 'Toca los botones de color para repetir la secuencia',
    default => '',
};
?>
<?php include dirname(__DIR__) . '/plantillas/cabecera.php'; ?>

<section class="game-card">
    <h1 class="game-title">🎮 <?= htmlspecialchars($juego['titulo']) ?></h1>

    <div class="game-layout">
        <div class="game-main-column">
            <div class="game-toolbar">
                <button type="button" id="btnPantallaCompleta" class="btn-fullscreen">⛶ Pantalla completa</button>
            </div>
            <div class="game-window" id="gameWindow">
                <canvas id="gameCanvas"></canvas>
                <button type="button" id="btnSalirFullscreen" class="btn-salir-fs" aria-label="Salir de pantalla completa" title="Salir de pantalla completa">✕</button>
                <div class="game-overlay" id="gameOverlay">
                    <div class="overlay-content">
                        <h2>Presiona ENTER para jugar</h2>
                        <p class="score-display">Puntuación: <span id="scoreDisplay">0</span></p>
                        <p class="loading-status" id="loadingStatus">Cargando sprites...</p>
                    </div>
                </div>
            </div>

            <div class="game-info">
                <div class="game-stats">
                    <div class="stat-item">
                        <span class="stat-label">Puntuación:</span>
                        <span class="stat-value" id="currentScore">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Velocidad:</span>
                        <span class="stat-value" id="speedDisplay">1x</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Nivel:</span>
                        <span class="stat-value" id="levelDisplay">1</span>
                    </div>
                </div>

                <div class="game-controls">
                    <h3>Controles</h3>
                    <ul>
                        <?php foreach ($controles as $control): ?>
                            <li><strong><?= htmlspecialchars($control['tecla']) ?></strong> — <?= htmlspecialchars($control['accion']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($controlesMovil): ?>
                        <p class="controles-tactiles">📱 <?= htmlspecialchars($controlesMovil) ?></p>
                    <?php endif; ?>
                </div>

                <div class="game-objective">
                    <h3>Objetivo</h3>
                    <p><?= htmlspecialchars($juego['objetivo']) ?></p>
                </div>
                <div class="game-sinopsis-link">
                    <a href="<?= Recursos::url('sinopsis') . '?game=' . htmlspecialchars($juego['slug']) ?>" class="btn-sinopsis">📖 Ver Sinopsis</a>
                </div>
            </div>
        </div>

        <div class="game-sidebar">
            <div class="leaderboard-panel">
                <h3>🏆 Ranking</h3>
                <div id="leaderboardContent">
                    <p class="loading-text">Cargando ranking...</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="game-comentarios">
    <h2>💬 Comentarios</h2>

    <?php if ($usuarioActual): ?>
        <div class="comment-form">
            <label for="comment-text">Escribe tu comentario</label>
            <textarea id="comment-text" rows="4" placeholder="Comparte tu experiencia..." maxlength="2000"></textarea>
            <button type="button" id="submitComment">Enviar comentario</button>
            <span class="comment-char-count" id="charCount">0/2000</span>
        </div>
    <?php else: ?>
        <div class="comment-login-prompt">
            <p><a href="<?= Recursos::url('login') ?>">Inicia sesión</a> para dejar un comentario.</p>
        </div>
    <?php endif; ?>

    <div class="comments-list" id="commentsList">
        <div class="loading-text">Cargando comentarios...</div>
    </div>
</section>

<?php include dirname(__DIR__) . '/plantillas/pie.php'; ?>