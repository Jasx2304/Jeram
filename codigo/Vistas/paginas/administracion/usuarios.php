<?php
// Gestión de usuarios: cambiar rol (user/admin) o eliminar cuentas.
// Recibe $usuarios desde ControladorAdmin::usuarios().
use JERAM\Utilidades\Recursos;
$subPaginaActiva = 'users';
$mostrarEnvoltura = false;
?>
<?php include dirname(__DIR__, 2) . '/plantillas/cabecera.php'; ?>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <h2><i class='bx bx-shield'></i> Admin</h2>
        <nav class="admin-nav">
            <a href="<?= Recursos::url('admin/dashboard') ?>"><i class='bx bx-home'></i> Dashboard</a>
            <a href="<?= Recursos::url('admin/games') ?>"><i class='bx bx-game'></i> Juegos</a>
            <a href="<?= Recursos::url('admin/comments') ?>"><i class='bx bx-comment'></i> Comentarios</a>
            <a href="<?= Recursos::url('admin/users') ?>" class="active"><i class='bx bx-user'></i> Usuarios</a>
        </nav>
    </aside>

    <main class="admin-content">
        <div class="admin-header">
            <h1><i class='bx bx-user'></i> Gestión de Usuarios</h1>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Registro</th>
                    <th>Verificado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td>#<?= $usuario['id'] ?></td>
                        <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                        <td><?= htmlspecialchars($usuario['email']) ?></td>
                        <td>
                            <form method="post" action="<?= Recursos::url('admin/users/update') ?>" style="display:inline">
                                <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
                                <select name="role" onchange="this.form.submit()">
                                    <option value="user" <?= $usuario['role'] === 'user' ? 'selected' : '' ?>>Usuario</option>
                                    <option value="admin" <?= $usuario['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </form>
                        </td>
                        <td><?= date('d/m/Y', strtotime($usuario['created_at'])) ?></td>
                        <td>
                            <span class="badge badge-<?= $usuario['email_verified_at'] ? 'success' : 'secondary' ?>">
                                <?= $usuario['email_verified_at'] ? 'Sí' : 'No' ?>
                            </span>
                        </td>
                        <td class="actions">
                            <form method="post" action="<?= Recursos::url('admin/users/delete') ?>" style="display:inline">
                                <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
                                <button type="submit" class="btn-sm btn-danger" onclick="return confirm('¿Eliminar este usuario? Se perderán sus puntuaciones y comentarios.')">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</div>

<?php include dirname(__DIR__, 2) . '/plantillas/pie.php'; ?>