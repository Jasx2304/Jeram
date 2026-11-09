<?php include dirname(__DIR__) . '/plantillas/cabecera.php'; ?>

<div class="login-container">
    <div class="login-card">
        <div class="tab-switcher">
            <button class="tab-btn active" onclick="window.location.href='<?= \JERAM\Utilidades\Recursos::url('login') ?>'">Iniciar Sesión</button>
            <button class="tab-btn" onclick="window.location.href='<?= \JERAM\Utilidades\Recursos::url('register') ?>'">Registrarse</button>
        </div>

        <div id="panelLogin">
            <div class="login-header">
                <h1>Buenas</h1>
                <p>Inicia sesión en tu cuenta</p>
            </div>

            <form id="loginForm" action="<?= \JERAM\Utilidades\Recursos::url('login') ?>" method="post" novalidate>
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" placeholder="tu@correo.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                        <button type="button" class="toggle-visibility">👁️</button>
                    </div>
                </div>

                <div class="form-group form-checkbox">
                    <label>
                        <input type="checkbox" name="remember_me" value="1">
                        Recordarme
                    </label>
                </div>

                <button type="submit" id="btnLogin">Ingresar</button>

                <p class="switch-hint">
                    ¿No tienes cuenta?
                    <a href="<?= \JERAM\Utilidades\Recursos::url('register') ?>">Regístrate aquí</a>
                </p>
            </form>
        </div>
    </div>
</div>

<script src="<?= \JERAM\Utilidades\Recursos::versionada(\JERAM\Utilidades\Recursos::js('sesion.js')) ?>"></script>
<?php include dirname(__DIR__) . '/plantillas/pie.php'; ?>