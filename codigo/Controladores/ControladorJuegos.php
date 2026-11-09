<?php

namespace JERAM\Controladores;

use JERAM\Configuracion\BaseDeDatos;
use JERAM\Utilidades\Recursos;

/** Catálogo de juegos: listado público y página de juego. */
class ControladorJuegos
{
    /** GET /juegos → lista de juegos activos (tarjetas con portada). */
    public function listar(array $params = []): void
    {
        $pdo = BaseDeDatos::obtenerInstancia();
        $stmt = $pdo->query("SELECT * FROM juegos WHERE activo = 1 ORDER BY orden ASC");
        $juegos = $stmt->fetchAll();

        $tituloPagina = 'Juegos - JERAM';
        $paginaActiva = 'juegos';
        $clasePagina = 'juegos-page';
        
        include dirname(__DIR__) . '/Vistas/paginas/juegos.php';
    }

    /**
     * GET /game?game=slug → carga el juego desde la BD y muestra la
     * vista que embebe su archivo (JS) según 'ruta_archivo'.
     * Si no existe o está inactivo → página 404.
     */
    public function mostrar(array $params = []): void
    {
        $slug = $_GET['game'] ?? '';

        if (!$slug) {
            http_response_code(404);
            include dirname(__DIR__) . '/Vistas/paginas/404.php';
            return;
        }

        $pdo = BaseDeDatos::obtenerInstancia();
        $stmt = $pdo->prepare("SELECT * FROM juegos WHERE slug = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$slug]);
        $juego = $stmt->fetch();

        if (!$juego) {
            http_response_code(404);
            include dirname(__DIR__) . '/Vistas/paginas/404.php';
            return;
        }

        $tituloPagina = $juego['titulo'] . ' - JERAM';
        $paginaActiva = 'juego';
        $clasePagina = 'game-page';

        $controles = json_decode($juego['controles'] ?? '[]', true);
        
        include dirname(__DIR__) . '/Vistas/paginas/juego.php';
    }
}