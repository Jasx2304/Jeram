<?php
use JERAM\Autenticacion\Sesion;
$usuario = Sesion::usuarioActual();
?>
<?php include dirname(__DIR__) . '/plantillas/cabecera.php'; ?>

<div class="profile-container">
    <div class="profile-card">
        <div class="profile-header">
            <img src="<?= htmlspecialchars($usuario['avatar_url']) ?>" alt="Avatar" class="profile-avatar">
            <h1><?= htmlspecialchars($usuario['nombre']) ?></h1>
            <p class="profile-email"><?= htmlspecialchars($usuario['email']) ?></p>
            <span class="profile-role badge badge-<?= $usuario['role'] ?>"><?= $usuario['role'] === 'admin' ? 'Administrador' : 'Usuario' ?></span>
        </div>

        <form class="profile-form" action="<?= \JERAM\Utilidades\Recursos::url('perfil') ?>" method="post">
            <div class="form-group">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
            </div>

            <hr class="form-divider">

            <div class="form-group">
                <label for="password">Nueva contraseña (dejar vacío para mantener)</label>
                <input type="password" id="password" name="password" placeholder="Mín. 8 caracteres" minlength="8">
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirmar contraseña</label>
                <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Repite la contraseña">
            </div>

            <button type="submit" class="btn">Guardar cambios</button>
        </form>
    </div>
</div>

<?php include dirname(__DIR__) . '/plantillas/pie.php'; ?>