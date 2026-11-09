<?php

namespace JERAM\Utilidades;

/**
 * Ayudante para armar URLs de assets (CSS, JS, imágenes) con la
 * ruta base correcta y "cache busting" (versión ?v=fecha de modificación)
 * para que al editar un archivo el navegador no use la copia cacheada.
 */
class Recursos
{
    public static function url(string $path): string
    {
        $base = defined('BASE_PATH') ? BASE_PATH : '';
        return $base . '/' . ltrim($path, '/');
    }

    public static function css(string $file): string
    {
        return self::url('assets/css/' . $file);
    }

    public static function js(string $file): string
    {
        return self::url('assets/js/' . $file);
    }

    public static function img(string $file): string
    {
        return self::url('assets/imagenes/' . ltrim($file, '/'));
    }

    public static function versionada(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $base = defined('BASE_PATH') ? BASE_PATH : '';
        $stripped = $base ? substr($path, strlen($base)) : $path;
        $filePath = \JERAM\Configuracion\Configuracion::rutaPublic() . $stripped;
        if (file_exists($filePath)) {
            return $url . '?v=' . filemtime($filePath);
        }
        return $url;
    }
}