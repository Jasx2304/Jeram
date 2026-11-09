<?php

namespace JERAM\Controladores;

use JERAM\Configuracion\BaseDeDatos;
use JERAM\Autenticacion\Sesion;
use JERAM\Utilidades\Validador;
use JERAM\Utilidades\Recursos;

/**
 * API JSON de comentarios: se llama con fetch() desde cada juego
 * (ver public/assets/js/comentarios.js). Siempre responde JSON.
 */
class ControladorComentarios
{
    /** POST /api/comment/add → guarda y devuelve el comentario en HTML. */
    public function agregar(array $params = []): void
    {
        header('Content-Type: application/json');

        $datos = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $validador = new Validador();
        if (!$validador->validar($datos, [
            'game_slug' => ['required'],
            'contenido' => ['required', 'min:1', 'max:2000'],
        ])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $validador->primerError()]);
            return;
        }

        $idUsuario = Sesion::idUsuarioActual();
        $slugJuego = $datos['game_slug'];
        $contenido = trim($datos['contenido']);

        $pdo = BaseDeDatos::obtenerInstancia();

        $stmt = $pdo->prepare("SELECT id FROM juegos WHERE slug = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$slugJuego]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Juego no encontrado']);
            return;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO game_comments (user_id, game_slug, contenido) VALUES (?, ?, ?)"
        );
        $stmt->execute([$idUsuario, $slugJuego, $contenido]);
        $idComentario = $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            "SELECT gc.*, u.nombre, u.avatar
             FROM game_comments gc
             JOIN usuarios u ON u.id = gc.user_id
             WHERE gc.id = ?"
        );
        $stmt->execute([$idComentario]);
        $comentario = $stmt->fetch();

        $htmlComentario = $this->dibujarComentario($comentario);

        echo json_encode([
            'success' => true,
            'comment' => $comentario,
            'comment_html' => $htmlComentario,
        ]);
    }

    /** GET /api/comment/list → lista comentarios visibles con paginación. */
    public function listar(array $params = []): void
    {
        header('Content-Type: application/json');

        $slugJuego = $_GET['game_slug'] ?? '';
        $page = max((int)($_GET['page'] ?? 1), 1);
        $limit = min((int)($_GET['limit'] ?? 20), 100);
        $offset = ($page - 1) * $limit;

        if (!$slugJuego) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'game_slug es requerido']);
            return;
        }

        $pdo = BaseDeDatos::obtenerInstancia();

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as total FROM game_comments WHERE game_slug = ? AND estado = 'visible'"
        );
        $stmt->execute([$slugJuego]);
        $total = (int)$stmt->fetch()['total'];

        $stmt = $pdo->prepare(
            "SELECT gc.id, gc.user_id, gc.game_slug, gc.contenido, gc.created_at,
                    u.nombre, u.avatar
             FROM game_comments gc
             JOIN usuarios u ON u.id = gc.user_id
             WHERE gc.game_slug = ? AND gc.estado = 'visible'
             ORDER BY gc.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$slugJuego, $limit, $offset]);
        $comentarios = $stmt->fetchAll();

        $hayMas = ($offset + $limit) < $total;

        $html = '';
        foreach ($comentarios as $comentario) {
            $html .= $this->dibujarComentario($comentario);
        }

        echo json_encode([
            'success' => true,
            'comments' => $comentarios,
            'html' => $html,
            'total' => $total,
            'page' => $page,
            'has_more' => $hayMas,
        ]);
    }

    /** DELETE /api/comment/delete → oculta un comentario (solo admin). */
    public function eliminar(array $params = []): void
    {
        header('Content-Type: application/json');

        $datos = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $idComentario = (int)($datos['comment_id'] ?? 0);

        if (!$idComentario) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'comment_id es requerido']);
            return;
        }

        $pdo = BaseDeDatos::obtenerInstancia();
        $stmt = $pdo->prepare("UPDATE game_comments SET estado = 'oculto' WHERE id = ?");
        $stmt->execute([$idComentario]);

        echo json_encode(['success' => true]);
    }

    /** Genera el HTML de un comentario (avatar + autor + fecha + texto). */
    private function dibujarComentario(array $comentario): string
    {
        $avatar = $comentario['avatar'] ?? Sesion::avatarPorDefecto($comentario['nombre']);
        $fecha = date('d/m/Y H:i', strtotime($comentario['created_at']));
        $contenido = htmlspecialchars($comentario['contenido']);
        $nombre = htmlspecialchars($comentario['nombre']);

        return <<<HTML
        <div class="comment-item" data-comment-id="{$comentario['id']}">
            <div class="comment-header">
                <img src="{$avatar}" alt="" class="comment-avatar">
                <span class="comment-author">{$nombre}</span>
                <span class="comment-date">{$fecha}</span>
            </div>
            <p class="comment-text">{$contenido}</p>
        </div>
HTML;
    }
}