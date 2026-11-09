<?php $tituloPagina = 'Página no encontrada'; $paginaActiva = ''; ?>
<?php include dirname(__DIR__, 2) . '/Vistas/plantillas/cabecera.php'; ?>

<section class="error-page">
    <h1>404</h1>
    <h2>Página no encontrada</h2>
    <p>Lo sentimos, la página que buscas no existe.</p>
    <a href="<?= \JERAM\Utilidades\Recursos::url('') ?>" class="btn">Volver al inicio</a>
</section>

<?php include dirname(__DIR__, 2) . '/Vistas/plantillas/pie.php'; ?>