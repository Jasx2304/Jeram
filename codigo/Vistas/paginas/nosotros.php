<?php
// =========================================================
// Página "Nosotros": quiénes somos + equipo con fotos reales.
// Cada tarjeta funciona como acordeón: al hacer clic se expande
// en el mismo sitio (ocupando todo el ancho de la fila) y muestra
// los sueños, metas y aporte del integrante, empujando a los demás.
// Si falta la foto de un integrante, se muestra su avatar de respaldo.
// =========================================================
use JERAM\Utilidades\Recursos;
$mostrarEnvoltura = false;

// Datos del equipo (texto tal como lo entregó cada integrante)
$integrantes = [
    [
        'apodo'  => 'Jerónimo',
        'nombre' => 'Jerónimo Arango Salazar',
        'foto'   => 'Jero.jpeg',
        'sueños' => 'terminar mis estudios en ingeniería en sistemas poder aprender y practicar sobre sistemas tener una buena relación familiar y amorosa y tener calma.',
        'metas'  => 'graduarme de la EAFIT poder viajar a bastantes lugares tener una familia y desarrollar sistemas de seguridad para toda las personas',
        'aporte' => 'desarrollador principal de la página web, analista de base de datos y principal administrador de proyecto',
    ],
    [
        'apodo'  => 'Raúl',
        'nombre' => 'Samuel Raúl Molina Duque',
        'foto'   => 'Raul.jpeg',
        'sueños' => 'Estudiar Ing. Sistemas y aprender varios idiomas, poder desarrollar mi propio videojuego',
        'metas'  => 'Estudiar y graduarme de la Eafit, aprendiendo varios idiomas para poder viajar a países como Alemania',
        'aporte' => 'Desarrollador secundario, analista del proyecto y director creativo',
    ],
    [
        'apodo'  => 'Miguel',
        'nombre' => 'Miguel Ángel Arboleda Rave',
        'foto'   => 'Miguel.jpeg',
        'sueños' => 'Ser un profesor de matemáticas reconocido y un músico de rock y blues.',
        'metas'  => 'Estudiar ingeniera matemática y psicología en la EAFIT a partir de una beca.',
        'aporte' => 'Encargado de la documentación de los avances del proyecto y diseñador de elementos publicitarios',
    ],
    [
        'apodo'  => 'Isabella',
        'nombre' => 'Isabella Morales Saraza',
        'foto'   => 'Isa.jpeg',
        'sueños' => 'Tener un trabajo que me guste, cumplir mis proyectos, conocer nuevos lugares.',
        'metas'  => 'Terminar mis estudios, graduarme, estudiar Ingeniería Financiera y prepararme para tener un buen futuro.',
        'aporte' => 'Me encargo de la organización y escritura de archivos relacionados con la documentación del proyecto.',
    ],
    [
        'apodo'  => 'Valeria',
        'nombre' => 'Valeria Franco Betancur',
        'foto'   => 'Vale.jpeg',
        'sueños' => 'tener una familia estable, una carrera y tener mi propio emprendimiento',
        'metas'  => 'graduarme de 11, tener mi título de ing de sistemas en eafit y por último ser azafata a lo largo del camino',
        'aporte' => 'se enfoca en mantener la organización y el orden, También me encargo de que el proyecto mantenga una presentación clara, coherente y fácil de usar.',
    ],
    [
        'apodo'  => 'Alexi',
        'nombre' => 'Alexi David Mejia Rivera',
        'foto'   => 'Alexi.jpeg',
        'sueños' => 'estudiar biologia y visitar otros paises, lograr utilizar el arte para conseguir dinero por medio de comisiones',
        'metas'  => 'conseguir beca en la EAFIT para estudiar mi carrera y lograr la transferencia a Alemania para conocer nuevos lugares y asi mejorar mis estudios y conocimientos',
        'aporte' => 'diseñador principal de JERAM, responsable de la mayoria de los graficos y paleta de colores',
    ],
];

// Foto real si existe en assets/imagenes/integrantes/; si no, avatar de respaldo
foreach ($integrantes as &$integrante) {
    $rutaFoto = \JERAM\Configuracion\Configuracion::rutaPublic() . '/assets/imagenes/integrantes/' . $integrante['foto'];
    $integrante['urlFoto'] = file_exists($rutaFoto)
        ? Recursos::img('integrantes/' . $integrante['foto'])
        : 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($integrante['apodo'])
            . '&backgroundType=gradientLinear&backgroundColor[]=ff00fb&backgroundColor[]=00d4ff&size=240';
}
unset($integrante);
?>
<?php include dirname(__DIR__) . '/plantillas/cabecera.php'; ?>

<main class="nosotros-page">
    <section class="nosotros-hero">
        <div class="nosotros-imagen">
            <img src="<?= Recursos::img('integrantes/todos.jpeg') ?>" alt="Foto de todo el equipo JERAM">
        </div>
        <div class="nosotros-texto">
            <h1>Quiénes somos</h1>
            <p class="nosotros-lead">
                Somos Jerónimo, Raúl, Miguel, Isabella, Valeria y Alexi.
            </p>
            <p>
                Seis estudiantes — cinco de la media técnica de software y uno de la media técnica de diseño —
                que desarrollamos este proyecto no solo como una nota académica, sino también por el gusto
                de aprender, crear y expresar nuestro amor por el arte y los videojuegos.
            </p>
            <p>
                Esperamos que disfruten JERAM tanto como nosotros disfrutamos crearlo.
            </p>
        </div>
    </section>

    <section class="equipo">
        <h2 class="section-title">El equipo</h2>
        <p class="equipo-pista">Haz clic en una foto para conocer más de cada integrante</p>
        <div class="equipo-grid">
            <?php foreach ($integrantes as $i => $integrante): ?>
            <article class="equipo-card"
                     style="animation-delay: <?= $i * 100 ?>ms"
                     tabindex="0"
                     role="button"
                     aria-expanded="false"
                     aria-label="Ver información de <?= htmlspecialchars($integrante['nombre']) ?>">
                <div class="equipo-cabecera">
                    <div class="equipo-avatar">
                        <img src="<?= htmlspecialchars($integrante['urlFoto']) ?>" alt="Foto de <?= htmlspecialchars($integrante['nombre']) ?>">
                    </div>
                    <h3><?= htmlspecialchars($integrante['apodo']) ?></h3>
                    <span class="equipo-ver"><i class='bx bx-show'></i> Ver perfil</span>
                </div>

                <!-- Información que se despliega al hacer clic en la tarjeta -->
                <div class="equipo-perfil">
                    <p class="equipo-nombre-completo"><i class='bx bx-user'></i> <?= htmlspecialchars($integrante['nombre']) ?></p>

                    <div class="perfil-seccion seccion-suenos">
                        <h4><i class='bx bx-star'></i> Sueños</h4>
                        <p><?= htmlspecialchars($integrante['sueños']) ?></p>
                    </div>

                    <div class="perfil-seccion seccion-metas">
                        <h4><i class='bx bx-target-lock'></i> Metas</h4>
                        <p><?= htmlspecialchars($integrante['metas']) ?></p>
                    </div>

                    <div class="perfil-seccion seccion-aporte">
                        <h4><i class='bx bx-award'></i> Aporte</h4>
                        <p><?= htmlspecialchars($integrante['aporte']) ?></p>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<script>
// Acordeón: al pulsar una tarjeta se expande y ocupa todo el ancho
// de la fila, empujando a los demás integrantes hacia abajo.
// Solo una tarjeta abierta a la vez.
const tarjetas = document.querySelectorAll('.equipo-card');

function alternarPerfil(card) {
    const yaAbierta = card.classList.contains('abierta');

    tarjetas.forEach(c => {
        c.classList.remove('abierta');
        c.setAttribute('aria-expanded', 'false');
        const ver = c.querySelector('.equipo-ver');
        if (ver) ver.innerHTML = "<i class='bx bx-show'></i> Ver perfil";
    });

    if (!yaAbierta) {
        card.classList.add('abierta');
        card.setAttribute('aria-expanded', 'true');
        const ver = card.querySelector('.equipo-ver');
        if (ver) ver.innerHTML = "<i class='bx bx-hide'></i> Cerrar perfil";
        if (card.scrollIntoView) {
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
}

tarjetas.forEach(card => {
    card.addEventListener('click', () => alternarPerfil(card));
    // Con teclado: Enter o espacio sobre la tarjeta enfocada
    card.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            alternarPerfil(card);
        }
    });
});
</script>

<?php include dirname(__DIR__) . '/plantillas/pie.php'; ?>
