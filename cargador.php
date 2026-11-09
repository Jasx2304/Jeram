<?php

/**
 * ============================================================
 * CARGADOR AUTOMÁTICO DE CLASES (Autoloader)
 * ============================================================
 *
 * Este archivo se encarga de cargar (incluir) las clases del
 * proyecto automáticamente cada vez que PHP las necesita.
 *
 * ¿Cómo funciona? Cuando el código usa una clase que todavía no
 * está cargada (por ejemplo: new ControladorAdmin), PHP llama a
 * esta función y nosotros le decimos en qué archivo está.
 *
 * Regla: el nombre de la clase indica su ubicación.
 *   JERAM\Controladores\ControladorAdmin
 *   -> carpeta Controladores/ -> archivo ControladorAdmin.php
 *
 * Así no es necesario escribir "require" a mano en cada archivo.
 */

spl_autoload_register(function (string $clase): void {

    // Solo nos interesan las clases de nuestro proyecto (las que
    // empiezan con "JERAM\"); las demás (como PDO o la propia PHP)
    // las carga el sistema por su cuenta.
    $prefijo = 'JERAM\\';

    if (!str_starts_with($clase, $prefijo)) {
        return;
    }

    // Quitamos el prefijo y convertimos las barras invertidas en
    // carpetas reales:
    //   "JERAM\Controladores\ControladorAdmin"
    //   -> "Controladores/ControladorAdmin"
    $rutaRelativa = str_replace('\\', '/', substr($clase, strlen($prefijo)));

    // Ruta final: carpeta "codigo/" + la ruta calculada + ".php"
    //   codigo/Controladores/ControladorAdmin.php
    $archivo = __DIR__ . '/codigo/' . $rutaRelativa . '.php';

    // Si el archivo existe, lo incluimos. Si no existe, no hacemos
    // nada (PHP mostrará su error habitual "class not found").
    if (file_exists($archivo)) {
        require $archivo;
    }
});
