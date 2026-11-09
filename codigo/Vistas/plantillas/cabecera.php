<?php
// =========================================================
// cabecera.php → apertura HTML común a TODAS las páginas:
// <head> con CSS, barra de navegación, menú de usuario
// (o enlace a login) y mensajes flash pendientes.
// Cada vista define: $tituloPagina, $paginaActiva, $clasePagina.
// =========================================================
use JERAM\Utilidades\Recursos;
use JERAM\Autenticacion\Sesion;

$usuarioActual = Sesion::usuarioActual();
$esAdmin = $usuarioActual && $usuarioActual['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tituloPagina ?? 'JERAM' ?></title>
    <link rel="stylesheet" href="<?= Recursos::versionada(Recursos::css('estilos.css')) ?>">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script>window.APP_BASE = <?= json_encode(rtrim(Recursos::url(''), '/')) ?>;</script>
</head>
<body>

    <header>
        <a href="<?= Recursos::url('') ?>" class="logo">
            <img src="<?= Recursos::img('jeram-pixilart.png') ?>" alt="JERAM">
        </a>

        <ul class="navlist">
            <li><a href="<?= Recursos::url('') ?>" class="<?= $paginaActiva === 'home' ? 'active' : '' ?>">Inicio</a></li>
            <li><a href="<?= Recursos::url('juegos') ?>" class="<?= $paginaActiva === 'juegos' ? 'active' : '' ?>">Juegos</a></li>
            <li><a href="<?= Recursos::url('nosotros') ?>" class="<?= $paginaActiva === 'nosotros' ? 'active' : '' ?>">Nosotros</a></li>
            <li><a href="<?= Recursos::url('apoyanos') ?>" class="<?= $paginaActiva === 'apoyanos' ? 'active' : '' ?>">Apoyanos</a></li>
        </ul>

        <div class="nav-icons">
            <?php if ($usuarioActual): ?>
                <div class="user-menu" id="userMenu">
                    <a href="<?= Recursos::url('perfil') ?>" class="user-icon">
                        <img src="<?= htmlspecialchars($usuarioActual['avatar_url']) ?>" alt="Avatar" class="user-avatar">
                    </a>
                    <span class="user-name"><?= htmlspecialchars($usuarioActual['nombre']) ?></span>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="<?= Recursos::url('perfil') ?>"><i class='bx bx-user'></i> Perfil</a>
                        <?php if ($esAdmin): ?>
                            <a href="<?= Recursos::url('admin/dashboard') ?>"><i class='bx bx-shield'></i> Admin</a>
                        <?php endif; ?>
                        <a href="<?= Recursos::url('logout') ?>"><i class='bx bx-log-out'></i> Cerrar sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= Recursos::url('login') ?>" class="user-icon">
                    <i class="bx bx-user-circle"></i>
                </a>
            <?php endif; ?>
            <div class="bx bx-menu" id="menu-icon"></div>
        </div>
    </header>

    <?php include __DIR__ . '/../parciales/mensajes.php'; ?>

    <?php $mostrarEnvoltura = $mostrarEnvoltura ?? true; ?>
    <?php if ($mostrarEnvoltura): ?>
    <main class="<?= $clasePagina ?? 'default-main' ?>">
    <?php endif; ?>