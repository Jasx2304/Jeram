<?php

namespace JERAM\Controladores;

use JERAM\Configuracion\BaseDeDatos;
use JERAM\Utilidades\Recursos;

/** Páginas informativas del sitio: sinopsis, nosotros y apóyanos. */
class ControladorPaginas
{
    /** GET /sinopsis?game=slug → sinopsis del juego (si viene slug). */
    public function sinopsis(array $params = []): void
    {
        $slug = $_GET['game'] ?? '';

        $juego = null;
        if ($slug) {
            $pdo = BaseDeDatos::obtenerInstancia();
            $stmt = $pdo->prepare("SELECT * FROM juegos WHERE slug = ? AND activo = 1 LIMIT 1");
            $stmt->execute([$slug]);
            $juego = $stmt->fetch();
        }

        $tituloPagina = ($juego ? $juego['titulo'] . ' - ' : '') . 'Sinopsis - JERAM';
        $paginaActiva = 'sinopsis';
        $clasePagina = 'sinopsis-page';
        include dirname(__DIR__) . '/Vistas/paginas/sinopsis.php';
    }

    /** GET /nosotros → equipo del proyecto. */
    public function nosotros(array $params = []): void
    {
        $tituloPagina = 'Nosotros - JERAM';
        $paginaActiva = 'nosotros';
        $clasePagina = 'nosotros-page';
        include dirname(__DIR__) . '/Vistas/paginas/nosotros.php';
    }

    /** GET /apoyanos → información de donaciones/apoyo. */
    public function apoyanos(array $params = []): void
    {
        $tituloPagina = 'Apoyanos - JERAM';
        $paginaActiva = 'apoyanos';
        $clasePagina = 'apoyo-page';
        include dirname(__DIR__) . '/Vistas/paginas/apoyanos.php';
    }
}