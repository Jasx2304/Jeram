<?php
// Moderación de comentarios: filtros por estado/juego/búsqueda y
// botones para mostrar, ocultar o reportar cada comentario.
// Recibe $comentarios y $juegosDisponibles desde ControladorAdmin::comentarios().
use JERAM\Utilidades\Recursos;
$subPaginaActiva = 'comments';
$mostrarEnvoltura = false;
?>
<?php include dirname(__DIR__, 2) . '/plantillas/cabecera.php'; ?>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <h2><i class='bx bx-shield'></i> Admin</h2>
        <nav class="admin-nav">
            <a href="<?= Recursos::url('admin/dashboard') ?>"><i class='bx bx-home'></i> Dashboard</a>
            <a href="<?= Recursos::url('admin/games') ?>"><i class='bx bx-game'></i> Juegos</a>
            <a href="<?= Recursos::url('admin/comments') ?>" class="active"><i class='bx bx-comment'></i> Comentarios</a>
            <a href="<?= Recursos::url('admin/users') ?>"><i class='bx bx-user'></i> Usuarios</a>
        </nav>
    </aside>

    <main class="admin-content">
        <div class="admin-header">
            <h1><i class='bx bx-comment'></i> Moderación de Comentarios</h1>
        </div>

        <form class="admin-filters" method="get" action="<?= Recursos::url('admin/comments') ?>">
            <select name="estado">
                <option value="all" <?= $estado === 'all' ? 'selected' : '' ?>>Todos</option>
                <option value="visible" <?= $estado === 'visible' ? 'selected' : '' ?>>Visibles</option>
                <option value="oculto" <?= $estado === 'oculto' ? 'selected' : '' ?>>Ocultos</option>
                <option value="reportado" <?= $estado === 'reportado' ? 'selected' : '' ?>>Reportados</option>
            </select>
            <select name="game_slug">
                <option value="">Todos los juegos</option>
                <?php foreach ($juegosDisponibles as $g): ?>
                    <option value="<?= htmlspecialchars($g['slug']) ?>" <?= $slugJuego === $g['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($g['titulo']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="search" placeholder="Buscar en comentarios..." value="<?= htmlspecialchars($busqueda) ?>">
            <button type="submit" class="btn">Filtrar</button>
        </form>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Juego</th>
                    <th>Comentario</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comentarios as $comentario): ?>
                    <tr class="comment-row-<?= $comentario['estado'] ?>">
                        <td>#<?= $comentario['id'] ?></td>
                        <td><?= htmlspecialchars($comentario['nombre']) ?><br><small><?= htmlspecialchars($comentario['email']) ?></small></td>
                        <td><code><?= htmlspecialchars($comentario['game_slug']) ?></code></td>
                        <td class="comment-text-cell"><?= htmlspecialchars(mb_substr($comentario['contenido'], 0, 200)) ?></td>
                        <td>
                            <span class="badge badge-<?= match($comentario['estado']) {
                                'visible' => 'success',
                                'oculto' => 'secondary',
                                'reportado' => 'warning',
                                default => 'secondary'
                            } ?>"><?= $comentario['estado'] ?></span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($comentario['created_at'])) ?></td>
                        <td class="actions">
                            <?php if ($comentario['estado'] !== 'visible'): ?>
                                <form method="post" action="<?= Recursos::url('admin/comments/moderate') ?>" style="display:inline">
                                    <input type="hidden" name="id" value="<?= $comentario['id'] ?>">
                                    <input type="hidden" name="action" value="mostrar">
                                    <button type="submit" class="btn-sm btn-success">Mostrar</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($comentario['estado'] !== 'oculto'): ?>
                                <form method="post" action="<?= Recursos::url('admin/comments/moderate') ?>" style="display:inline">
                                    <input type="hidden" name="id" value="<?= $comentario['id'] ?>">
                                    <input type="hidden" name="action" value="ocultar">
                                    <button type="submit" class="btn-sm btn-danger">Ocultar</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($comentario['estado'] !== 'reportado'): ?>
                                <form method="post" action="<?= Recursos::url('admin/comments/moderate') ?>" style="display:inline">
                                    <input type="hidden" name="id" value="<?= $comentario['id'] ?>">
                                    <input type="hidden" name="action" value="reportar">
                                    <button type="submit" class="btn-sm btn-warning">Reportar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($comentarios)): ?>
                    <tr><td colspan="7" class="empty-state">No hay comentarios</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>

<?php include dirname(__DIR__, 2) . '/plantillas/pie.php'; ?>