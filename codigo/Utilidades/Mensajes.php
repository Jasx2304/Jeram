<?php

namespace JERAM\Utilidades;

/**
 * Mensajes "flash": avisos que se guardan en $_SESSION para
 * mostrarlos UNA sola vez en la siguiente petición
 * (ej: "Juego creado" tras un POST y redirección).
 *
 * Uso: Mensajes::exito('Juego creado');  →  el layout los pinta y borra.
 */
class Mensajes
{
    private const FLASH_KEY = '_flash';

    public static function guardar(string $type, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION[self::FLASH_KEY][$type] = $message;
    }

    public static function exito(string $message): void
    {
        self::guardar('success', $message);
    }

    public static function error(string $message): void
    {
        self::guardar('error', $message);
    }

    public static function advertencia(string $message): void
    {
        self::guardar('warning', $message);
    }

    public static function informacion(string $message): void
    {
        self::guardar('info', $message);
    }

    public static function obtener(string $type): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $message = $_SESSION[self::FLASH_KEY][$type] ?? null;
        if ($message) {
            unset($_SESSION[self::FLASH_KEY][$type]);
        }
        return $message;
    }

    public static function todas(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $flash = $_SESSION[self::FLASH_KEY] ?? [];
        $_SESSION[self::FLASH_KEY] = [];
        return $flash;
    }

    public static function tiene(string $type): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return isset($_SESSION[self::FLASH_KEY][$type]);
    }
}