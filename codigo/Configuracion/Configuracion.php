<?php

namespace JERAM\Configuracion;

/**
 * Toda la configuración del sitio en un solo archivo:
 * credenciales de BD, tiempos de sesión y modo debug.
 *
 * IMPORTANTE: el sitio DETECTA solo si corre en XAMPP (local) o en
 * InfinityFree (hosting) y elige la base de datos correcta.
 * No hay que cambiar nada al subir los archivos.
 */
class Configuracion
{
    /**
     * Muestra los errores en pantalla.
     * - En local siempre se muestran (para poder depurar).
     * - En InfinityFree solo si cambias esto a true temporalmente.
     */
    public const APP_DEBUG = false;

    // --- Base de datos de PRODUCCIÓN (InfinityFree) ---
    public const DB_HOST = 'sql213.infinityfree.com';
    public const DB_NAME = 'if0_43316003_jeram'; // nombre exacto en tu panel
    public const DB_USER = 'if0_43316003';       // usuario exacto en tu panel
    public const DB_PASS = '26ZC1zlYS30GX9';

    // --- Base de datos LOCAL (XAMPP) ---
    public const LOCAL_DB_HOST = '127.0.0.1';
    public const LOCAL_DB_NAME = 'jeram_db';
    public const LOCAL_DB_USER = 'root';
    public const LOCAL_DB_PASS = '';

    // Tiempo de sesión en minutos
    public const SESSION_LIFETIME = 120;
    // Tiempo del "recuérdame" en minutos (30 días)
    public const REMEMBER_ME_LIFETIME = 43200;

    /** @var bool|null Resultado de la detección (se calcula una sola vez) */
    private static ?bool $esLocal = null;

    public static function enDepuracion(): bool
    {
        // Local: siempre ver errores. Hosting: solo si APP_DEBUG = true.
        return self::esLocal() || self::APP_DEBUG;
    }

    /**
     * Detecta el entorno: en XAMPP hay MySQL escuchando en 127.0.0.1:3306;
     * en InfinityFree el MySQL está en otro servidor (sql213...), así que el
     * puerto local está cerrado.
     */
    public static function esLocal(): bool
    {
        if (self::$esLocal !== null) {
            return self::$esLocal;
        }

        if (!function_exists('fsockopen')) {
            return self::$esLocal = false;
        }

        $socket = @fsockopen(self::LOCAL_DB_HOST, 3306, $errno, $errstr, 0.3);
        if ($socket) {
            fclose($socket);
            return self::$esLocal = true;
        }

        return self::$esLocal = false;
    }

    /** Devuelve los datos de conexión correctos según el entorno detectado. */
    public static function conexionBD(): array
    {
        if (self::esLocal()) {
            return [
                'host' => self::LOCAL_DB_HOST,
                'name' => self::LOCAL_DB_NAME,
                'user' => self::LOCAL_DB_USER,
                'pass' => self::LOCAL_DB_PASS,
            ];
        }

        return [
            'host' => self::DB_HOST,
            'name' => self::DB_NAME,
            'user' => self::DB_USER,
            'pass' => self::DB_PASS,
        ];
    }

    /**
     * Devuelve la ruta física de la carpeta web (assets, etc.).
     *
     * - En local (XAMPP): la raíz del proyecto tiene "public/" y "codigo/".
     * - En hosting (InfinityFree): todo está en htdocs/ sin carpeta "public/".
     *
     * Este método detecta en cuál de los dos casos estamos.
     */
    public static function rutaPublic(): string
    {
        $raiz = dirname(__DIR__, 2); // sube de Configuracion/ → codigo/ → raíz del proyecto o htdocs

        // Estructura local: existe public/ y codigo/ hermanos
        if (is_dir($raiz . '/public') && is_dir($raiz . '/codigo')) {
            return $raiz . '/public';
        }

        // Estructura de hosting: ya estamos dentro de htdocs (todo junto)
        return $raiz;
    }
}
