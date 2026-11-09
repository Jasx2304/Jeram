<?php

namespace JERAM\Controladores;

use JERAM\Configuracion\BaseDeDatos;
use JERAM\Autenticacion\Sesion;
use JERAM\Utilidades\Validador;
use JERAM\Utilidades\Mensajes;
use JERAM\Utilidades\Recursos;

/**
 * Login, registro, logout y perfil.
 * La clase Sesion (Autenticación) se encarga de la sesión en sí;
 * aquí solo se validan los datos y se decide a dónde redirigir.
 */
class ControladorSesion
{
    /** GET /login → formulario de inicio de sesión. */
    public function mostrarLogin(array $params = []): void
    {
        $tituloPagina = 'Iniciar sesión - JERAM';
        $paginaActiva = 'login';
        $clasePagina = 'login-page';
        include dirname(__DIR__) . '/Vistas/paginas/iniciar-sesion.php';
    }

    /** GET /register → formulario de registro. */
    public function mostrarRegistro(array $params = []): void
    {
        $tituloPagina = 'Registrarse - JERAM';
        $paginaActiva = 'register';
        $clasePagina = 'login-page';
        include dirname(__DIR__) . '/Vistas/paginas/registrarse.php';
    }

    /**
     * POST /login → valida credenciales con password_verify().
     * Si son correctas inicia la sesión y redirige según el rol
     * (admin → panel, usuario → juegos).
     */
    public function iniciarSesion(array $params = []): void
    {
        $pdo = BaseDeDatos::obtenerInstancia();

        $validador = new Validador();
        if (!$validador->validar($_POST, [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ])) {
            Mensajes::error($validador->primerError());
            $paginaActiva = 'login';
            include dirname(__DIR__) . '/Vistas/paginas/iniciar-sesion.php';
            return;
        }

        $email = strtolower(trim($_POST['email']));
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT id, nombre, email, password, role, avatar FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($password, $usuario['password'])) {
            Mensajes::error('Correo o contraseña incorrectos');
            $paginaActiva = 'login';
            include dirname(__DIR__) . '/Vistas/paginas/iniciar-sesion.php';
            return;
        }

        // Auto-admin para el correo específico
        if ($email === 'jasx2304@gmail.com' && $usuario['role'] !== 'admin') {
            $stmt = $pdo->prepare("UPDATE usuarios SET role = 'admin' WHERE id = ?");
            $stmt->execute([$usuario['id']]);
            $usuario['role'] = 'admin';
        }

        Sesion::iniciar($usuario);

        if (isset($_POST['remember_me'])) {
            Sesion::recordarme($usuario['id']);
        }

        $redireccion = $_SESSION['_redirect_after_login'] ?? null;
        unset($_SESSION['_redirect_after_login']);

        // Redirigir según rol
        $destino = $redireccion ?: ($usuario['role'] === 'admin' ? Recursos::url('admin/dashboard') : Recursos::url('juegos'));

        Mensajes::exito('¡Bienvenido de nuevo, ' . htmlspecialchars($usuario['nombre']) . '!');
        header('Location: ' . $destino);
        exit;
    }

    /** POST /register → crea la cuenta (contraseña hasheada) y loguea. */
    public function registrarse(array $params = []): void
    {
        $pdo = BaseDeDatos::obtenerInstancia();

        $validador = new Validador();
        if (!$validador->validar($_POST, [
            'nombre' => ['required', 'min:2', 'max:100'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ])) {
            Mensajes::error($validador->primerError());
            $paginaActiva = 'register';
            include dirname(__DIR__) . '/Vistas/paginas/registrarse.php';
            return;
        }

        $nombre = trim($_POST['nombre']);
        $email = strtolower(trim($_POST['email']));
        $password = $_POST['password'];

        // Verificar si el usuario ya existe
        $stmt = $pdo->prepare("SELECT id, nombre, email, password, role, avatar FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $usuarioExistente = $stmt->fetch();

        if ($usuarioExistente) {
            // Si es el email admin especial, actualizar rol y loguear
            if ($email === 'jasx2304@gmail.com' && $usuarioExistente['role'] !== 'admin') {
                $stmt = $pdo->prepare("UPDATE usuarios SET role = 'admin' WHERE id = ?");
                $stmt->execute([$usuarioExistente['id']]);
                $usuarioExistente['role'] = 'admin';
            }
            // Verificar contraseña y loguear
            if (password_verify($password, $usuarioExistente['password'])) {
                Sesion::iniciar($usuarioExistente);
                if (isset($_POST['remember_me'])) {
                    Sesion::recordarme($usuarioExistente['id']);
                }
                Mensajes::exito('¡Bienvenido de nuevo, ' . htmlspecialchars($usuarioExistente['nombre']) . '!');
                header('Location: ' . Recursos::url('juegos'));
                exit;
            } else {
                Mensajes::error('El correo ya está registrado con otra contraseña');
                $paginaActiva = 'register';
                include dirname(__DIR__) . '/Vistas/paginas/registrarse.php';
                return;
            }
        }

        // Usuario nuevo
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $email, $hash]);
        $idUsuario = $pdo->lastInsertId();

        // Auto-admin para el email especial
        if ($email === 'jasx2304@gmail.com') {
            $stmt = $pdo->prepare("UPDATE usuarios SET role = 'admin' WHERE id = ?");
            $stmt->execute([$idUsuario]);
        }

        $stmt = $pdo->prepare("SELECT id, nombre, email, role, avatar FROM usuarios WHERE id = ?");
        $stmt->execute([$idUsuario]);
        $usuario = $stmt->fetch();

        Sesion::iniciar($usuario);

        Mensajes::exito('¡Cuenta creada correctamente! Bienvenido a JERAM.');
        header('Location: ' . Recursos::url('juegos'));
        exit;
    }

    /** POST|GET /logout → destruye la sesión y vuelve al inicio. */
    public function cerrarSesion(array $params = []): void
    {
        Sesion::cerrar();
        Mensajes::exito('Has cerrado sesión correctamente.');
        header('Location: ' . Recursos::url(''));
        exit;
    }

    /** GET /perfil → página de perfil del usuario. */
    public function mostrarPerfil(array $params = []): void
    {
        $tituloPagina = 'Mi Perfil - JERAM';
        $paginaActiva = 'perfil';
        $clasePagina = 'profile-page';
        include dirname(__DIR__) . '/Vistas/paginas/perfil.php';
    }

    /**
     * POST /perfil → actualiza solo los campos enviados
     * (construye el UPDATE dinámicamente) y refresca la sesión.
     */
    public function actualizarPerfil(array $params = []): void
    {
        $pdo = BaseDeDatos::obtenerInstancia();
        $idUsuario = Sesion::idUsuarioActual();

        $reglas = [];
        if (!empty($_POST['nombre'])) {
            $reglas['nombre'] = ['min:2', 'max:100'];
        }
        if (!empty($_POST['email'])) {
            $reglas['email'] = ['email', 'unique:usuarios,email,' . $idUsuario];
        }
        if (!empty($_POST['password'])) {
            $reglas['password'] = ['min:8', 'confirmed'];
        }

        if (!empty($reglas)) {
            $validador = new Validador();
            if (!$validador->validar($_POST, $reglas)) {
                Mensajes::error($validador->primerError());
                header('Location: ' . Recursos::url('perfil'));
                exit;
            }
        }

        $campos = [];
        $params = [];

        if (!empty($_POST['nombre'])) {
            $campos[] = "nombre = ?";
            $params[] = trim($_POST['nombre']);
        }
        if (!empty($_POST['email'])) {
            $campos[] = "email = ?";
            $params[] = strtolower(trim($_POST['email']));
        }
        if (!empty($_POST['password'])) {
            $campos[] = "password = ?";
            $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        if (!empty($campos)) {
            $params[] = $idUsuario;
            $sql = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $stmt = $pdo->prepare("SELECT id, nombre, email, role, avatar FROM usuarios WHERE id = ?");
            $stmt->execute([$idUsuario]);
            $usuario = $stmt->fetch();
            Sesion::iniciar($usuario);

            Mensajes::exito('Perfil actualizado correctamente.');
        }

        header('Location: ' . Recursos::url('perfil'));
        exit;
    }

    /** GET /api/auth/me → JSON con el usuario logueado (lo usa sesion.js). */
    public function usuarioActual(array $params = []): void
    {
        header('Content-Type: application/json');
        $usuario = Sesion::usuarioActual();
        if ($usuario) {
            echo json_encode(['authenticated' => true, 'user' => $usuario]);
        } else {
            echo json_encode(['authenticated' => false, 'user' => null]);
        }
    }
}