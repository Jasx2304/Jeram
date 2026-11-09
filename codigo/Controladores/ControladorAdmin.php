<?php

namespace JERAM\Controladores;

use JERAM\Configuracion\BaseDeDatos;
use JERAM\Autenticacion\Sesion;
use JERAM\Utilidades\Validador;
use JERAM\Utilidades\Mensajes;
use JERAM\Utilidades\Recursos;

/**
 * Zona de administración (protegida por el middleware exigirAdmin):
 * dashboard con estadísticas y CRUD de juegos, comentarios y usuarios.
 * Todas las escrituras usan prepared statements y redirigen con
 * un mensaje flash para evitar reenvíos del formulario (POST/redirect/GET).
 */
class ControladorAdmin
{
    /** GET /admin/dashboard → tarjetas de estadísticas del sitio. */
    public function panel(array $params = []): void
    {
        $pdo = BaseDeDatos::obtenerInstancia();

        $totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        $totalJuegos = $pdo->query("SELECT COUNT(*) FROM juegos WHERE activo = 1")->fetchColumn();
        $totalPuntuaciones = $pdo->query("SELECT COUNT(*) FROM game_scores")->fetchColumn();
        $totalComentarios = $pdo->query("SELECT COUNT(*) FROM game_comments WHERE estado = 'visible'")->fetchColumn();
        $comentariosReportados = $pdo->query("SELECT COUNT(*) FROM game_comments WHERE estado = 'reportado'")->fetchColumn();

        $stmt = $pdo->query(
            "SELECT gs.game_slug, COUNT(*) as jugadas, MAX(gs.score) as max_score
             FROM game_scores gs
             GROUP BY gs.game_slug
             ORDER BY jugadas DESC"
        );
        $estadisticasJuegos = $stmt->fetchAll();

        $tituloPagina = 'Panel Admin - JERAM';
        $paginaActiva = 'admin';
        $clasePagina = 'admin-page';
        include dirname(__DIR__) . '/Vistas/paginas/administracion/panel.php';
    }

    /** GET /admin/games → tabla de gestión de juegos. */
    public function juegos(array $params = []): void
    {
        $pdo = BaseDeDatos::obtenerInstancia();
        $stmt = $pdo->query("SELECT * FROM juegos ORDER BY orden ASC");
        $juegos = $stmt->fetchAll();

        $tituloPagina = 'Admin - Juegos - JERAM';
        $paginaActiva = 'admin';
        $clasePagina = 'admin-page';
        include dirname(__DIR__) . '/Vistas/paginas/administracion/juegos.php';
    }

    /** POST /admin/games → crea un juego (slug único validado). */
    public function crearJuego(array $params = []): void
    {
        $pdo = BaseDeDatos::obtenerInstancia();

        $validador = new Validador();
        if (!$validador->validar($_POST, [
            'titulo' => ['required', 'max:150'],
            'slug' => ['required', 'max:50', 'unique:juegos,slug'],
        ])) {
            Mensajes::error($validador->primerError());
            header('Location: ' . Recursos::url('admin/games'));
            exit;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO juegos (slug, titulo, descripcion_corta, descripcion_larga, imagen_portada, imagen_miniatura, ruta_archivo, controles, objetivo, activo, orden)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $_POST['slug'],
            $_POST['titulo'],
            $_POST['descripcion_corta'] ?? '',
            $_POST['descripcion_larga'] ?? '',
            $_POST['imagen_portada'] ?: 'en_construccion.png',
            $_POST['imagen_miniatura'] ?: ($_POST['imagen_portada'] ?: 'en_construccion.png'),
            $_POST['ruta_archivo'] ?? 'juego.php?game=' . $_POST['slug'],
            $this->convertirControles($_POST['controles_json'] ?? '[]'),
            $_POST['objetivo'] ?? '',
            isset($_POST['activo']) ? 1 : 0,
            (int)($_POST['orden'] ?? 0),
        ]);

        Mensajes::exito('Juego creado correctamente.');
        header('Location: ' . Recursos::url('admin/games'));
        exit;
    }

    /** POST /admin/games/update → actualiza los datos del juego. */
    public function actualizarJuego(array $params = []): void
    {
        $pdo = BaseDeDatos::obtenerInstancia();
        $id = (int)($_POST['id'] ?? 0);

        $validador = new Validador();
        if (!$validador->validar($_POST, [
            'titulo' => ['required', 'max:150'],
        ])) {
            Mensajes::error($validador->primerError());
            header('Location: ' . Recursos::url('admin/games'));
            exit;
        }

        $stmt = $pdo->prepare(
            "UPDATE juegos SET titulo = ?, descripcion_corta = ?, descripcion_larga = ?, imagen_portada = ?, imagen_miniatura = ?, ruta_archivo = ?, controles = ?, objetivo = ?, activo = ?, orden = ? WHERE id = ?"
        );
        $stmt->execute([
            $_POST['titulo'],
            $_POST['descripcion_corta'] ?? '',
            $_POST['descripcion_larga'] ?? '',
            $_POST['imagen_portada'] ?: 'en_construccion.png',
            $_POST['imagen_miniatura'] ?: ($_POST['imagen_portada'] ?: 'en_construccion.png'),
            $_POST['ruta_archivo'] ?? '',
            $this->convertirControles($_POST['controles_json'] ?? '[]'),
            $_POST['objetivo'] ?? '',
            isset($_POST['activo']) ? 1 : 0,
            (int)($_POST['orden'] ?? 0),
            $id,
        ]);

        Mensajes::exito('Juego actualizado correctamente.');
        header('Location: ' . Recursos::url('admin/games'));
        exit;
    }

    /**
     * POST /admin/games/upload → sube la portada del juego.
     * Seguridad: valida el MIME real con finfo (no el nombre del archivo),
     * limita a 2 MB y guarda con nombre aleatorio en /imagenes/juegos.
     */
    public function subirImagen(array $params = []): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_FILES['imagen']) || ($_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'No se recibió ninguna imagen']);
            exit;
        }

        $file = $_FILES['imagen'];

        if ($file['size'] > 2 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'La imagen no puede superar 2 MB']);
            exit;
        }

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!isset($allowed[$mime])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Formato no permitido. Usa JPG, PNG, GIF o WEBP']);
            exit;
        }

        $dir = \JERAM\Configuracion\Configuracion::rutaPublic() . '/assets/imagenes/juegos';
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se pudo crear la carpeta de imágenes']);
            exit;
        }

        $name = 'img_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se pudo guardar la imagen']);
            exit;
        }

        $relative = 'juegos/' . $name;
        echo json_encode([
            'ok'   => true,
            'path' => $relative,
            'url'  => Recursos::img($relative),
        ]);
    }

    /** Limpia el JSON de controles antes de guardarlo en la BD. */
    private function convertirControles(string $json): string
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return '[]';
        }
        $clean = [];
        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }
            $tecla  = trim((string)($item['tecla'] ?? ''));
            $accion = trim((string)($item['accion'] ?? ''));
            if ($tecla !== '' && $accion !== '') {
                $clean[] = ['tecla' => mb_substr($tecla, 0, 60), 'accion' => mb_substr($accion, 0, 120)];
            }
        }
        return json_encode($clean, JSON_UNESCAPED_UNICODE);
    }

    /** POST /admin/games/delete → elimina un juego. */
    public function eliminarJuego(array $params = []): void
    {
        $pdo = BaseDeDatos::obtenerInstancia();
        $id = (int)($_POST['id'] ?? 0);

        $stmt = $pdo->prepare("DELETE FROM juegos WHERE id = ?");
        $stmt->execute([$id]);

        Mensajes::exito('Juego eliminado correctamente.');
        header('Location: ' . Recursos::url('admin/games'));
        exit;
    }

    /**
     * GET /admin/comments → lista comentarios con filtros opcionales
     * (estado, juego y búsqueda). El WHERE se arma dinámicamente.
     */
    public function comentarios(array $params = []): void
    {
        $pdo = BaseDeDatos::obtenerInstancia();

        $estado = $_GET['estado'] ?? 'all';
        $slugJuego = $_GET['game_slug'] ?? '';
        $busqueda = $_GET['search'] ?? '';

        $where = [];
        $bindings = [];

        if ($estado !== 'all') {
            $where[] = "gc.estado = ?";
            $bindings[] = $estado;
        }
        if ($slugJuego) {
            $where[] = "gc.game_slug = ?";
            $bindings[] = $slugJuego;
        }
        if ($busqueda) {
            $where[] = "gc.contenido LIKE ?";
            $bindings[] = "%{$busqueda}%";
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare(
            "SELECT gc.*, u.nombre, u.email
             FROM game_comments gc
             JOIN usuarios u ON u.id = gc.user_id
             {$whereClause}
             ORDER BY gc.created_at DESC"
        );
        $stmt->execute($bindings);
        $comentarios = $stmt->fetchAll();

        $juegosDisponibles = $pdo->query("SELECT slug, titulo FROM juegos ORDER BY titulo")->fetchAll();

        $tituloPagina = 'Admin - Comentarios - JERAM';
        $paginaActiva = 'admin';
        $clasePagina = 'admin-page';
        include dirname(__DIR__) . '/Vistas/paginas/administracion/comentarios.php';
    }

    /** POST /admin/comments/moderate → cambia el estado (visible/oculto/reportado). */
    public function moderarComentario(array $params = []): void
    {
        $pdo = BaseDeDatos::obtenerInstancia();
        $id = (int)($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? 'oculto';

        $newState = match ($action) {
            'mostrar' => 'visible',
            'ocultar' => 'oculto',
            'reportar' => 'reportado',
            default => 'oculto',
        };

        $stmt = $pdo->prepare("UPDATE game_comments SET estado = ? WHERE id = ?");
        $stmt->execute([$newState, $id]);

        Mensajes::exito('Comentario moderado correctamente.');
        header('Location: ' . Recursos::url('admin/comments'));
        exit;
    }

    /** GET /admin/users → tabla de usuarios registrados. */
    public function usuarios(array $params = []): void
    {
        $pdo = BaseDeDatos::obtenerInstancia();
        $stmt = $pdo->query("SELECT id, nombre, email, role, created_at, email_verified_at FROM usuarios ORDER BY created_at DESC");
        $usuarios = $stmt->fetchAll();

        $tituloPagina = 'Admin - Usuarios - JERAM';
        $paginaActiva = 'admin';
        $clasePagina = 'admin-page';
        include dirname(__DIR__) . '/Vistas/paginas/administracion/usuarios.php';
    }

    /** POST /admin/users/update → cambia el rol (solo 'user' o 'admin'). */
    public function actualizarUsuario(array $params = []): void
    {
        $pdo = BaseDeDatos::obtenerInstancia();
        $id = (int)($_POST['id'] ?? 0);

        $role = $_POST['role'] ?? 'user';
        if (!in_array($role, ['user', 'admin'])) {
            $role = 'user';
        }

        $stmt = $pdo->prepare("UPDATE usuarios SET role = ? WHERE id = ?");
        $stmt->execute([$role, $id]);

        Mensajes::exito('Usuario actualizado correctamente.');
        header('Location: ' . Recursos::url('admin/users'));
        exit;
    }

    /** POST /admin/users/delete → elimina usuario (no permite el propio). */
    public function eliminarUsuario(array $params = []): void
    {
        $pdo = BaseDeDatos::obtenerInstancia();
        $id = (int)($_POST['id'] ?? 0);

        if ($id === Sesion::idUsuarioActual()) {
            Mensajes::error('No puedes eliminarte a ti mismo.');
            header('Location: ' . Recursos::url('admin/users'));
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);

        Mensajes::exito('Usuario eliminado correctamente.');
        header('Location: ' . Recursos::url('admin/users'));
        exit;
    }
}