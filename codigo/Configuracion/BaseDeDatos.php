<?php

namespace JERAM\Configuracion;

use PDO;
use PDOException;

/**
 * Patrón Singleton de la conexión a MySQL.
 * obtenerInstancia() crea la conexión PDO solo la primera vez y
 * la reutiliza en todos los controladores del sitio.
 */
class BaseDeDatos
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function obtenerInstancia(): PDO
    {
        if (self::$instance === null) {
            // Configuracion detecta si estamos en XAMPP local o en InfinityFree
            // y devuelve las credenciales correctas para cada entorno.
            $bd = Configuracion::conexionBD();
            $host = $bd['host'];
            $dbname = $bd['name'];
            $usuario = $bd['user'];
            $pass = $bd['pass'];
            $charset = 'utf8mb4';

            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $usuario, $pass, $options);
            } catch (PDOException $e) {
                // En debug se muestra el detalle; en producción solo queda el log
                if (Configuracion::enDepuracion()) {
                    throw new PDOException("Error al conectar: " . $e->getMessage(), (int)$e->getCode(), $e);
                }
                error_log("Error de conexión a la BD: " . $e->getMessage());
                throw new PDOException("No se pudo conectar a la base de datos", 0, $e);
            }
        }

        return self::$instance;
    }

    public static function desconectar(): void
    {
        self::$instance = null;
    }
}