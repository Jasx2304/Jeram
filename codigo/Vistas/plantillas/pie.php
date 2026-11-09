<?php
// =========================================================
// pie.php → cierre de TODAS las páginas: cierra el <main>,
// pone el footer y carga los JS comunes (conexión a la API,
// sesión y arranque). Las vistas pueden añadir sus propios JS
// con la variable $scriptsExtra.
// =========================================================
use JERAM\Utilidades\Recursos;
?>

    <?php if ($mostrarEnvoltura ?? true): ?>
    </main>
    <?php endif; ?>

    <footer class="footer" id="footer">
        <div class="footer-logos">
            <img src="<?= Recursos::img('footer_img/logo_lola.png') ?>" alt="Logo Lola">
            <img src="<?= Recursos::img('footer_img/logo_pascual.png') ?>" alt="Logo Pascual">
        </div>
        <p>Made in Colombia</p>
        <div class="social-icons">
            <a href="#"><i class='bx bxl-facebook'></i></a>
            <a href="#"><i class='bx bxl-instagram'></i></a>
            <a href="#"><i class='bx bxl-twitter'></i></a>
        </div>
    </footer>

    <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
    <script src="<?= Recursos::versionada(Recursos::js('conexion.js')) ?>"></script>
    <script src="<?= Recursos::versionada(Recursos::js('sesion.js')) ?>"></script>
    <script src="<?= Recursos::versionada(Recursos::js('principal.js')) ?>"></script>
    <?= $scriptsExtra ?? '' ?>
</body>
</html>