<?php $tituloPagina = 'Acceso denegado'; $paginaActiva = ''; ?>
<?php include dirname(__DIR__, 2) . '/Vistas/plantillas/cabecera.php'; ?>

<section class="error-page">
    <h1>403</h1>
    <h2>Acceso denegado</h2>
    <p>No tienes permisos para acceder a esta página.</p>
    <a href="<?= \JERAM\Utilidades\Recursos::url('juegos') ?>" class="btn">Ver juegos</a>
</section>

<?php include dirname(__DIR__, 2) . '/Vistas/plantillas/pie.php'; ?>