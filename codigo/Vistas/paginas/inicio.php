<?php
// Portada de bienvenida del sitio.
use JERAM\Utilidades\Recursos;
?>
<?php include dirname(__DIR__) . '/plantillas/cabecera.php'; ?>

<section class="Cuerpo">
    <div class="texto_inicio">
        <h1>BIENVENIDO A JERAM</h1>
        <h2>en donde te podrás divertir y jugar</h2>
        <p>Si lo que te gusta son las aventuras y los juegos donde te retan a tomar decisiones difíciles, este es tu lugar</p>
        <br>
        <div class="juego-link" id="juego-link">
            <a href="<?= Recursos::url('juegos') ?>" class="prueba-juego">COMENZAR EXPERIENCIA</a>
        </div>
    </div>
    <img src="<?= Recursos::img('jeram-pixilart.png') ?>" alt="Logo de JERAM" class="logo_inicio">
</section>

<?php include dirname(__DIR__) . '/plantillas/pie.php'; ?>