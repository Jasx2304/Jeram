<?php
use JERAM\Utilidades\Recursos;
?>
<?php include dirname(__DIR__) . '/plantillas/cabecera.php'; ?>

<section class="sinopsis-intro">
    <?php if ($juego): ?>
        <h1>Sinopsis de <?= htmlspecialchars($juego['titulo']) ?></h1>
        <?php if ($juego['descripcion_larga']): ?>
            <p><?= nl2br(htmlspecialchars($juego['descripcion_larga'])) ?></p>
        <?php else: ?>
            <p><?= htmlspecialchars($juego['descripcion_corta'] ?? 'Sin descripción disponible.') ?></p>
        <?php endif; ?>
        <a href="<?= Recursos::url('game') . '?game=' . htmlspecialchars($juego['slug']) ?>" class="btn-sinopsis">🎮 Volver al juego</a>
    <?php else: ?>
        <h1>Sinopsis</h1>
        <p>Seleccioná un juego desde el catálogo para ver su sinopsis.</p>
        <a href="<?= Recursos::url('juegos') ?>" class="btn-sinopsis">🎮 Ver juegos</a>
    <?php endif; ?>
</section>

<?php if ($juego): ?>
<section class="sinopsis-details">
    <article>
        <h2>Objetivo del juego</h2>
        <p><?= htmlspecialchars($juego['objetivo'] ?? 'Completá el juego y alcanzá la mayor puntuación posible.') ?></p>
    </article>

    <?php
    $controles = json_decode($juego['controles'] ?? '[]', true);
    if (!empty($controles)):
    ?>
    <article>
        <h2>Controles</h2>
        <ul>
            <?php foreach ($controles as $control): ?>
                <li><strong><?= htmlspecialchars($control['tecla']) ?></strong> — <?= htmlspecialchars($control['accion']) ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php include dirname(__DIR__) . '/plantillas/pie.php'; ?>
