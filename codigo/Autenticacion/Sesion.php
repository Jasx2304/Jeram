<?php

namespace JERAM\Autenticacion;

use JERAM\Configuracion\Configuracion;
use JERAM\Utilidades\Recursos;

/**
 * Control de sesión y permisos.
 *
 * - iniciar()   guarda al usuario en $_SESSION (tras login correcto)
 * - cerrar()    destruye la sesión (logout)
 * - exigirSesion / exigirVisitante / exigirAdmin  → middlewares que
 *   usan las rutas de index.php para proteger zonas del sitio.
 * - recordarme() / intentarRecordarme()  → cookie "remember_me" con
 *   token cifrado en BD para mantener la sesión 7 días.
 */
class Sesion
{
    /**
     * Middleware: exige estar logueado. Si no, redirige al login
     * (guardando la URL pendiente para volver ahí tras iniciar sesión).
     */
    public static function exigirSesion(array $params = []): bool
    {
        if (!isset($_SESSION['user_id'])) {
            if (self::intentarRecordarme()) {
                return true;
            }
            $_SESSION['_redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . Recursos::url('login'));
            exit;
        }

        $lifetime = Configuracion::SESSION_LIFETIME * 60;
        if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity']) > $lifetime) {
            self::cerrar();
            header('Location: ' . Recursos::url('login'));
            exit;
        }

        $_SESSION['_last_activity'] = time();
        return true;
    }

    /** Middleware: exige NO estar logueado (páginas login/registro). */
    public static function exigirVisitante(array $params = []): bool
    {
        if (isset($_SESSION['user_id'])) {
            header('Location: ' . Recursos::url('juegos'));
            exit;
        }
        if (self::intentarRecordarme()) {
            header('Location: ' . Recursos::url('juegos'));
            exit;
        }
        return true;
    }

    /** Middleware: exige rol "admin". Si no, muestra la página 403. */
    public static function exigirAdmin(array $params = []): bool
    {
        self::exigirSesion();
        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            http_response_code(403);
            include dirname(__DIR__, 2) . '/codigo/Vistas/paginas/403.php';
            exit;
        }
        return true;
    }

    /** Devuelve los datos del usuario logueado (o null si no hay sesión). */
    public static function usuarioActual(): ?array
    {
        if (isset($_SESSION['user_id'])) {
            return [
                'id' => $_SESSION['user_id'],
                'nombre' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'] ?? '',
                'role' => $_SESSION['user_role'] ?? 'user',
                'avatar_url' => $_SESSION['user_avatar'] ?? self::avatarPorDefecto($_SESSION['user_name'] ?? 'U'),
            ];
        }

        if (self::intentarRecordarme()) {
            return self::usuarioActual();
        }

        return null;
    }

    public static function idUsuarioActual(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function esAdmin(): bool
    {
        return ($_SESSION['user_role'] ?? '') === 'admin';
    }

    /** Llena $_SESSION con los datos del usuario tras autenticarlo. */
    public static function iniciar(array $usuario): void
    {
        $_SESSION['user_id'] = (int)$usuario['id'];
        $_SESSION['user_name'] = $usuario['nombre'];
        $_SESSION['user_email'] = $usuario['email'];
        $_SESSION['user_role'] = $usuario['role'] ?? 'user';
        $_SESSION['user_avatar'] = $usuario['avatar_url'] ?? $usuario['avatar'] ?? self::avatarPorDefecto($usuario['nombre']);
        $_SESSION['_last_activity'] = time();
    }

    /** Cierra la sesión: borra la cookie, los datos y destruye $_SESSION. */
    public static function cerrar(): void
    {
        self::olvidarRecordarme();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();
    }

    private static function intentarRecordarme(): bool
    {
        $token = $_COOKIE['remember_me'] ?? null;
        if (!$token) {
            return false;
        }

        try {
            $pdo = \JERAM\Configuracion\BaseDeDatos::obtenerInstancia();
            $stmt = $pdo->prepare(
                "SELECT r.*, u.id, u.nombre, u.email, u.role, u.avatar 
                 FROM remember_tokens r 
                 JOIN usuarios u ON u.id = r.user_id 
                 WHERE r.token = ? AND r.expires_at > NOW() 
                 LIMIT 1"
            );
            $stmt->execute([hash('sha256', $token)]);
            $result = $stmt->fetch();

            if ($result) {
                $usuario = [
                    'id' => $result['id'],
                    'nombre' => $result['nombre'],
                    'email' => $result['email'],
                    'role' => $result['role'],
                    'avatar_url' => $result['avatar'] ?? self::avatarPorDefecto($result['nombre']),
                ];
                self::iniciar($usuario);

                $newToken = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + (Configuracion::REMEMBER_ME_LIFETIME * 60));

                $stmt = $pdo->prepare("UPDATE remember_tokens SET token = ?, expires_at = ? WHERE id = ?");
                $stmt->execute([hash('sha256', $newToken), $expires, $result['id']]);

                setcookie('remember_me', $newToken, strtotime($expires), '/', '', false, true);

                return true;
            }
        } catch (\Exception $e) {
            error_log("Remember me error: " . $e->getMessage());
        }

        setcookie('remember_me', '', time() - 3600, '/');
        return false;
    }

    public static function recordarme(int $idUsuario): void
    {
        try {
            $pdo = \JERAM\Configuracion\BaseDeDatos::obtenerInstancia();

            $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
            $stmt->execute([$idUsuario]);

            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + (Configuracion::REMEMBER_ME_LIFETIME * 60));

            $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$idUsuario, hash('sha256', $token), $expires]);

            setcookie('remember_me', $token, strtotime($expires), '/', '', false, true);
        } catch (\Exception $e) {
            error_log("Set remember me error: " . $e->getMessage());
        }
    }

    private static function olvidarRecordarme(): void
    {
        $token = $_COOKIE['remember_me'] ?? null;
        if ($token) {
            try {
                $pdo = \JERAM\Configuracion\BaseDeDatos::obtenerInstancia();
                $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token = ?");
                $stmt->execute([hash('sha256', $token)]);
            } catch (\Exception $e) {
                error_log("Clear remember me error: " . $e->getMessage());
            }
        }
        setcookie('remember_me', '', time() - 3600, '/');
    }

    public static function avatarPorDefecto(string $name): string
    {
        $seed = urlencode($name);
        return "https://api.dicebear.com/7.x/initials/svg?seed={$seed}&backgroundType=gradientLinear&backgroundColor[]=ff00fb&backgroundColor[]=00d4ff&size=40";
    }
}