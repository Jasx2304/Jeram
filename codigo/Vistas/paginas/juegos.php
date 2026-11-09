<?php
// Catálogo de juegos: tarjetas con portada, título y descripción corta.
// Recibe $juegos desde ControladorJuegos::listar().
use JERAM\Utilidades\Recursos;
?>
<?php include dirname(__DIR__) . '/plantillas/cabecera.php'; ?>

<section class="juegos">
    <h1 class="section-title">🎮 Nuestros Juegos</h1>
    <div class="juegos-container">
        <?php foreach ($juegos as $juego): ?>
            <div class="juego">
                <a href="<?= Recursos::url('game') . '?game=' . htmlspecialchars($juego['slug']) ?>">
                    <img src="<?= Recursos::img(htmlspecialchars($juego['imagen_portada'])) ?>" alt="<?= htmlspecialchars($juego['titulo']) ?>">
                </a>
                <h2><?= htmlspecialchars($juego['titulo']) ?></h2>
                <?php if ($juego['descripcion_corta']): ?>
                    <p class="juego-descripcion"><?= htmlspecialchars($juego['descripcion_corta']) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php include dirname(__DIR__) . '/plantillas/pie.php'; ?>