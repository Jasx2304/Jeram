<?php $tituloPagina = 'Error del servidor'; $paginaActiva = ''; ?>
<?php include dirname(__DIR__, 2) . '/Vistas/plantillas/cabecera.php'; ?>

<section class="error-page">
    <h1>500</h1>
    <h2>Error interno del servidor</h2>
    <p>Ha ocurrido un error inesperado. Por favor, intenta de nuevo más tarde.</p>
    <a href="<?= \JERAM\Utilidades\Recursos::url('') ?>" class="btn">Volver al inicio</a>
</section>

<?php include dirname(__DIR__, 2) . '/Vistas/plantillas/pie.php'; ?>