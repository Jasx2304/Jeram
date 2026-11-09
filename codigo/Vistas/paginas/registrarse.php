<?php include dirname(__DIR__) . '/plantillas/cabecera.php'; ?>

<div class="login-container">
    <div class="login-card">
        <div class="tab-switcher">
            <button class="tab-btn" type="button" onclick="window.location.href='<?= \JERAM\Utilidades\Recursos::url('login') ?>'">Iniciar Sesión</button>
            <button class="tab-btn active" id="tabRegister" type="button">Registrarse</button>
        </div>

        <div id="panelRegistro">
            <div class="login-header">
                <h1>Crear cuenta</h1>
                <p>Regístrate para Jugar</p>
            </div>

            <form id="registerForm" action="<?= \JERAM\Utilidades\Recursos::url('register') ?>" method="post" novalidate>
                <div class="form-group">
                    <label for="regNombre">Nombre completo</label>
                    <input type="text" id="regNombre" name="nombre" placeholder="pepito perez" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="regEmail">Correo electrónico</label>
                    <input type="email" id="regEmail" name="email" placeholder="tu@correo.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="regPassword">Contraseña</label>
                    <div class="input-wrap">
                        <input type="password" id="regPassword" name="password" placeholder="Mín. 8 caracteres, 1 mayúscula, 1 número" required>
                        <button type="button" id="toggleRegPass" class="toggle-visibility">👁️</button>
                    </div>
                    <div id="fortaleza"><div id="barraFuerza"></div></div>
                    <span id="textoFuerza"></span>
                </div>

                <div class="form-group">
                    <label for="regPasswordConf">Confirmar contraseña</label>
                    <div class="input-wrap">
                        <input type="password" id="regPasswordConf" name="password_confirmation" placeholder="Repite tu contraseña" required>
                        <button type="button" id="toggleRegPassConf" class="toggle-visibility">👁️</button>
                    </div>
                </div>

                <button type="submit" id="btnRegistro">Crear cuenta</button>

                <p class="switch-hint">
                    ¿Ya tienes cuenta?
                    <a href="<?= \JERAM\Utilidades\Recursos::url('login') ?>">Inicia sesión</a>
                </p>
            </form>
        </div>
    </div>
</div>

<script src="<?= \JERAM\Utilidades\Recursos::versionada(\JERAM\Utilidades\Recursos::js('sesion.js')) ?>"></script>
<?php include dirname(__DIR__) . '/plantillas/pie.php'; ?>