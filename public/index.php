<?php

// =========================================================
// Punto de entrada único de todo el sitio (Front Controller).
// Cada petición (aunque pida /juegos) termina aquí: se monta
// la sesión, se carga el autoloader, se registran las rutas
// y el enrutador decide qué controlador atiende la petición.
// =========================================================

session_start();

// cargador.php registra el autoloader: busca cada clase
// JERAM\... dentro de la carpeta codigo/ sin require manuales.
// En local está un nivel arriba (junto a public/); en hosting (InfinityFree)
// está en la misma carpeta que este index.php.
$cargador = __DIR__ . '/../cargador.php';
if (!file_exists($cargador)) {
    $cargador = __DIR__ . '/cargador.php';
}
require_once $cargador;

use JERAM\Configuracion\Configuracion;
use JERAM\Utilidades\Enrutador;
use JERAM\Autenticacion\Sesion;

error_reporting(E_ALL);
ini_set('display_errors', Configuracion::enDepuracion() ? '1' : '0');

$router = new Enrutador();

// --- Rutas Públicas (cualquiera puede verlas) ---
$router->get('/', 'ControladorInicio@inicio');
$router->get('/juegos', 'ControladorJuegos@listar');
$router->get('/game', 'ControladorJuegos@mostrar');
$router->get('/sinopsis', 'ControladorPaginas@sinopsis');
$router->get('/nosotros', 'ControladorPaginas@nosotros');
$router->get('/apoyanos', 'ControladorPaginas@apoyanos');

// --- Autenticación ---
// Cada ruta puede recibir middlewares: funciones que se ejecutan
// ANTES del controlador (ej: exigirVisitante = solo visitantes logueados).
$router->get('/login', 'ControladorSesion@mostrarLogin', [[Sesion::class, 'exigirVisitante']]);
$router->post('/login', 'ControladorSesion@iniciarSesion', [[Sesion::class, 'exigirVisitante']]);
$router->get('/register', 'ControladorSesion@mostrarRegistro', [[Sesion::class, 'exigirVisitante']]);
$router->post('/register', 'ControladorSesion@registrarse', [[Sesion::class, 'exigirVisitante']]);
$router->post('/logout', 'ControladorSesion@cerrarSesion', [[Sesion::class, 'exigirSesion']]);
$router->get('/logout', 'ControladorSesion@cerrarSesion', [[Sesion::class, 'exigirSesion']]);
$router->get('/perfil', 'ControladorSesion@mostrarPerfil', [[Sesion::class, 'exigirSesion']]);
$router->post('/perfil', 'ControladorSesion@actualizarPerfil', [[Sesion::class, 'exigirSesion']]);

// --- API: Puntuaciones (las consumen los juegos con fetch) ---
$router->post('/api/score/submit', 'ControladorPuntuaciones@enviar', [[Sesion::class, 'exigirSesion']]);
$router->get('/api/score/leaderboard', 'ControladorPuntuaciones@ranking');
$router->get('/api/score/user-best', 'ControladorPuntuaciones@miMejorPuntaje', [[Sesion::class, 'exigirSesion']]);

// --- API: Comentarios (JSON, sin vistas) ---
$router->post('/api/comment/add', 'ControladorComentarios@agregar', [[Sesion::class, 'exigirSesion']]);
$router->get('/api/comment/list', 'ControladorComentarios@listar');
$router->delete('/api/comment/delete', 'ControladorComentarios@eliminar', [[Sesion::class, 'exigirAdmin']]);

// --- API: Sesión (el frontend pregunta "¿sigo logueado?") ---
$router->get('/api/auth/me', 'ControladorSesion@usuarioActual');

// --- Zona de administración (solo rol admin, protegida por exigirAdmin) ---
$router->get('/admin/dashboard', 'ControladorAdmin@panel', [[Sesion::class, 'exigirAdmin']]);
$router->get('/admin/games', 'ControladorAdmin@juegos', [[Sesion::class, 'exigirAdmin']]);
$router->post('/admin/games', 'ControladorAdmin@crearJuego', [[Sesion::class, 'exigirAdmin']]);
$router->post('/admin/games/update', 'ControladorAdmin@actualizarJuego', [[Sesion::class, 'exigirAdmin']]);
$router->post('/admin/games/upload', 'ControladorAdmin@subirImagen', [[Sesion::class, 'exigirAdmin']]);
$router->post('/admin/games/delete', 'ControladorAdmin@eliminarJuego', [[Sesion::class, 'exigirAdmin']]);
$router->get('/admin/comments', 'ControladorAdmin@comentarios', [[Sesion::class, 'exigirAdmin']]);
$router->post('/admin/comments/moderate', 'ControladorAdmin@moderarComentario', [[Sesion::class, 'exigirAdmin']]);
$router->get('/admin/users', 'ControladorAdmin@usuarios', [[Sesion::class, 'exigirAdmin']]);
$router->post('/admin/users/update', 'ControladorAdmin@actualizarUsuario', [[Sesion::class, 'exigirAdmin']]);
$router->post('/admin/users/delete', 'ControladorAdmin@eliminarUsuario', [[Sesion::class, 'exigirAdmin']]);

// --- Despacho: buscar la ruta que coincida con la petición ---
$method = $_SERVER['REQUEST_METHOD'];

// Detecta la base path automáticamente (para correr bajo /jeram/public
// en XAMPP, o en la raíz si algún día se despliega en otro lado)
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$basePath = $scriptDir === '/' ? '' : $scriptDir;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Quita la base path de la URI para que el enrutador compare rutas limpias
if ($basePath && str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}
$uri = $uri ?: '/';

// BASE_PATH lo usan las vistas para armar enlaces con la raíz correcta
define('BASE_PATH', $basePath);

try {
    // Enrutador recorre las rutas registradas y llama al controlador
    $router->despachar($method, $uri);
} catch (\Throwable $e) {
    // Si estamos en modo debug se muestra el error real;
    // en producción se registra en el log y se muestra la página 500.
    if (Configuracion::enDepuracion()) {
        throw $e;
    }
    error_log("Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    include dirname(__DIR__) . '/codigo/Vistas/paginas/500.php';
}