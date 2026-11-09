<?php
// Dashboard del admin: tarjetas con totales, barras de actividad
// por juego y accesos rápidos. Recibe las estadísticas desde
// ControladorAdmin::panel().
use JERAM\Utilidades\Recursos;
?>
<?php $subPaginaActiva = 'dashboard'; $mostrarEnvoltura = false; ?>
<?php include dirname(__DIR__, 2) . '/plantillas/cabecera.php'; ?>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <h2><i class='bx bx-shield'></i> Admin</h2>
        <nav class="admin-nav">
            <a href="<?= Recursos::url('admin/dashboard') ?>" class="active">
                <i class='bx bx-home'></i> Dashboard
            </a>
            <a href="<?= Recursos::url('admin/games') ?>">
                <i class='bx bx-game'></i> Juegos
            </a>
            <a href="<?= Recursos::url('admin/comments') ?>">
                <i class='bx bx-comment'></i> Comentarios
            </a>
            <a href="<?= Recursos::url('admin/users') ?>">
                <i class='bx bx-user'></i> Usuarios
            </a>
        </nav>
    </aside>

    <main class="admin-content">
        <div class="admin-header">
            <h1><i class='bx bx-layout'></i> Dashboard</h1>
        </div>

        <div class="stats-grid">
            <div class="stat-card stat-neon">
                <div class="stat-icon"><i class='bx bx-user'></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?= $totalUsuarios ?></span>
                    <span class="stat-label">Usuarios</span>
                </div>
            </div>
            <div class="stat-card stat-blue">
                <div class="stat-icon"><i class='bx bx-game'></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?= $totalJuegos ?></span>
                    <span class="stat-label">Juegos activos</span>
                </div>
            </div>
            <div class="stat-card stat-purple">
                <div class="stat-icon"><i class='bx bx-trophy'></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?= $totalPuntuaciones ?></span>
                    <span class="stat-label">Puntuaciones</span>
                </div>
            </div>
            <div class="stat-card stat-cyan">
                <div class="stat-icon"><i class='bx bx-message'></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?= $totalComentarios ?></span>
                    <span class="stat-label">Comentarios</span>
                </div>
            </div>
            <?php if ($comentariosReportados > 0): ?>
                <div class="stat-card warning">
                    <div class="stat-icon"><i class='bx bx-error'></i></div>
                    <div class="stat-info">
                        <span class="stat-number"><?= $comentariosReportados ?></span>
                        <span class="stat-label">Reportados</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="admin-section-card">
            <h2><i class='bx bx-bar-chart-alt-2'></i> Estadísticas por juego</h2>

            <?php
            $maxPlays = 1;
            foreach ($estadisticasJuegos as $stat) {
                $maxPlays = max($maxPlays, (int)$stat['jugadas']);
            }
            ?>

            <?php if (!empty($estadisticasJuegos)): ?>
                <div class="game-stats-list">
                    <?php foreach ($estadisticasJuegos as $stat): ?>
                        <div class="game-stat-row">
                            <div class="game-stat-info">
                                <span class="game-stat-name"><?= htmlspecialchars($stat['game_slug']) ?></span>
                                <span class="game-stat-meta">
                                    <i class='bx bx-play-circle'></i> <?= $stat['jugadas'] ?> partidas
                                    &nbsp;·&nbsp;
                                    <i class='bx bx-medal'></i> <?= number_format($stat['max_score']) ?> máx.
                                </span>
                            </div>
                            <div class="game-stat-bar">
                                <div class="game-stat-fill" style="width: <?= round(($stat['jugadas'] / $maxPlays) * 100) ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-state">No hay datos todavía</p>
            <?php endif; ?>
        </div>

        <div class="admin-section-card admin-shortcuts">
            <h2><i class='bx bx-rocket'></i> Accesos rápidos</h2>
            <div class="shortcut-grid">
                <a href="<?= Recursos::url('admin/games') ?>" class="shortcut-card">
                    <i class='bx bx-game'></i>
                    <span>Gestionar juegos</span>
                </a>
                <a href="<?= Recursos::url('admin/comments') ?>" class="shortcut-card">
                    <i class='bx bx-comment'></i>
                    <span>Moderar comentarios</span>
                </a>
                <a href="<?= Recursos::url('admin/users') ?>" class="shortcut-card">
                    <i class='bx bx-user'></i>
                    <span>Administrar usuarios</span>
                </a>
            </div>
        </div>
    </main>
</div>

<?php include dirname(__DIR__, 2) . '/plantillas/pie.php'; ?>
