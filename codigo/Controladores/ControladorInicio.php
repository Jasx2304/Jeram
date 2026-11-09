<?php

namespace JERAM\Controladores;

use JERAM\Utilidades\Recursos;

/** Página de portada (home). */
class ControladorInicio
{
    /** GET / → vista de inicio */
    public function inicio(array $params = []): void
    {
        $tituloPagina = 'JERAM - Portal de Juegos';
        $paginaActiva = 'home';
        $clasePagina = 'home-main';
        
        include dirname(__DIR__) . '/Vistas/paginas/inicio.php';
    }
}