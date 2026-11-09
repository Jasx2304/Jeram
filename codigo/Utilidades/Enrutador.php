<?php

namespace JERAM\Utilidades;

/**
 * Enrutador: guarda la tabla de rutas del sitio y decide qué
 * controlador atiende cada petición.
 *
 * Flujo: index.php registra rutas (get/post/delete) → al llegar una
 * petición, despachar() compara método + URI → si coincide, ejecuta
 * los middlewares (exigirSesion, exigirAdmin...) y llama al controlador.
 * Si ninguna ruta coincide, muestra la página 404.
 */
class Enrutador
{
    /** @var array Lista de rutas registradas: [method, path, handler, middlewares] */
    private array $routes = [];

    public function get(string $path, $handler, array $middlewares = []): self
    {
        $this->agregarRuta('GET', $path, $handler, $middlewares);
        return $this;
    }

    public function post(string $path, $handler, array $middlewares = []): self
    {
        $this->agregarRuta('POST', $path, $handler, $middlewares);
        return $this;
    }

    public function delete(string $path, $handler, array $middlewares = []): self
    {
        $this->agregarRuta('DELETE', $path, $handler, $middlewares);
        return $this;
    }

    private function agregarRuta(string $method, string $path, $handler, array $middlewares): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function despachar(string $method, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        // Busca la primera ruta que coincida con método y URI exactos
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $uri) {
                // Middlewares: si alguno devuelve false, YA se respondió
                // (ej: redirección al login) y no se llega al controlador.
                foreach ($route['middlewares'] as $middleware) {
                    $result = $middleware([]);
                    if ($result === false) {
                        return;
                    }
                }

                $this->llamarControlador($route['handler']);
                return;
            }
        }

        // Nada coincidió: página 404
        http_response_code(404);
        require __DIR__ . '/../Vistas/paginas/404.php';
    }

    /**
     * Convierte "Controlador@metodo" en objeto + método y lo ejecuta.
     * Ej: 'ControladorJuegos@listar' → (new ControladorJuegos)->listar()
     */
    private function llamarControlador($handler): void
    {
        if (is_string($handler) && str_contains($handler, '@')) {
            [$controller, $method] = explode('@', $handler);
            $controllerClass = "JERAM\\Controladores\\{$controller}";
            if (class_exists($controllerClass)) {
                $controllerInstance = new $controllerClass();
                $controllerInstance->$method([]);
            } else {
                throw new \RuntimeException("No existe la clase del controlador: {$controllerClass}");
            }
        } else {
            throw new \InvalidArgumentException("Handler inválido: {$handler}");
        }
    }
}
