<?php

namespace JERAM\Controladores;

use JERAM\Configuracion\BaseDeDatos;
use JERAM\Autenticacion\Sesion;
use JERAM\Utilidades\Validador;
use JERAM\Utilidades\Mensajes;

/**
 * API JSON de puntuaciones: los juegos envían su puntaje con
 * fetch() y reciben ranking, rango y récord personal (ver puntuaciones.js).
 */
class ControladorPuntuaciones
{
    /** POST /api/score/submit → guarda el puntaje y devuelve rango + récord. */
    public function enviar(array $params = []): void
    {
        header('Content-Type: application/json');

        $datos = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $validador = new Validador();
        if (!$validador->validar($datos, [
            'game_slug' => ['required'],
            'score' => ['required', 'numeric', 'min:0'],
        ])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $validador->primerError()]);
            return;
        }

        $idUsuario = Sesion::idUsuarioActual();
        if (!$idUsuario) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Inicia sesión para guardar puntuaciones']);
            return;
        }

        $slugJuego = $datos['game_slug'];
        $score = (int)$datos['score'];
        $level = (int)($datos['level_reached'] ?? 1);
        $metadata = isset($datos['metadata']) ? json_encode($datos['metadata']) : null;

        $pdo = BaseDeDatos::obtenerInstancia();

        $stmt = $pdo->prepare("SELECT id FROM juegos WHERE slug = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$slugJuego]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Juego no encontrado']);
            return;
        }

        // Solo se guarda UNA puntuación por cuenta: la más alta.
        $stmt = $pdo->prepare(
            "SELECT id, score FROM game_scores
             WHERE user_id = ? AND game_slug = ?
             ORDER BY score DESC, id ASC
             LIMIT 1"
        );
        $stmt->execute([$idUsuario, $slugJuego]);
        $anterior = $stmt->fetch();

        $esNuevoRecord = !$anterior || $score > (int)$anterior['score'];

        if (!$anterior) {
            $stmt = $pdo->prepare(
                "INSERT INTO game_scores (user_id, game_slug, score, level_reached, metadata) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$idUsuario, $slugJuego, $score, $level, $metadata]);
        } elseif ($esNuevoRecord) {
            // Superó su récord → se reemplaza la fila con el nuevo mejor puntaje.
            $stmt = $pdo->prepare(
                "UPDATE game_scores
                 SET score = ?, level_reached = ?, metadata = ?, played_at = CURRENT_TIMESTAMP
                 WHERE id = ?"
            );
            $stmt->execute([$score, $level, $metadata, $anterior['id']]);
        }
        // Si no superó su récord → no se guarda nada (sigue contando su mejor).

        $mejorAlmacenado = $esNuevoRecord ? $score : (int)$anterior['score'];

        // Rango = número de cuentas con un mejor puntaje (DISTINCT por seguridad).
        $stmt = $pdo->prepare(
            "SELECT COUNT(DISTINCT user_id) as rank FROM game_scores WHERE game_slug = ? AND score > ?"
        );
        $stmt->execute([$slugJuego, $mejorAlmacenado]);
        $rango = (int)$stmt->fetch()['rank'] + 1;

        echo json_encode([
            'success' => true,
            'rank' => $rango,
            'is_personal_best' => $esNuevoRecord,
            'total_scores' => $rango,
        ]);
    }

    /** GET /api/score/leaderboard → top de jugadores del juego (paginado). */
    public function ranking(array $params = []): void
    {
        header('Content-Type: application/json');

        $slugJuego = $_GET['game_slug'] ?? '';
        $limit = min((int)($_GET['limit'] ?? 50), 100);
        $offset = max((int)($_GET['offset'] ?? 0), 0);

        if (!$slugJuego) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'game_slug es requerido']);
            return;
        }

        $pdo = BaseDeDatos::obtenerInstancia();

        // Una sola fila por cuenta: la mejor puntuación del usuario
        // (funciona aunque aún existan registros duplicados sin migrar).
        $stmt = $pdo->prepare(
            "SELECT b.id, b.user_id, u.nombre, u.avatar, b.score, b.level_reached, b.played_at,
                    ROW_NUMBER() OVER (ORDER BY b.score DESC, b.played_at ASC) as rank
             FROM (
                 SELECT gs.id, gs.user_id, gs.score, gs.level_reached, gs.played_at,
                        ROW_NUMBER() OVER (
                            PARTITION BY gs.user_id
                            ORDER BY gs.score DESC, gs.played_at ASC, gs.id ASC
                        ) as rn
                 FROM game_scores gs
                 WHERE gs.game_slug = ?
             ) b
             JOIN usuarios u ON u.id = b.user_id
             WHERE b.rn = 1
             ORDER BY b.score DESC, b.played_at ASC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$slugJuego, $limit, $offset]);
        $scores = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT COUNT(DISTINCT user_id) as total FROM game_scores WHERE game_slug = ?"
        );
        $stmt->execute([$slugJuego]);
        $totalJugadores = (int)$stmt->fetch()['total'];

        $rangoUsuario = null;
        $idUsuario = Sesion::idUsuarioActual();
        if ($idUsuario) {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) + 1 as rank
                 FROM (SELECT DISTINCT user_id, MAX(score) as max_score
                       FROM game_scores WHERE game_slug = ?
                       GROUP BY user_id) as ranked
                 WHERE ranked.max_score > (SELECT MAX(score) FROM game_scores WHERE user_id = ? AND game_slug = ?)"
            );
            $stmt->execute([$slugJuego, $idUsuario, $slugJuego]);
            $rangoUsuario = (int)$stmt->fetch()['rank'];
        }

        echo json_encode([
            'success' => true,
            'game_slug' => $slugJuego,
            'scores' => $scores,
            'total_players' => $totalJugadores,
            'user_rank' => $rangoUsuario,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    /** GET /api/score/user-best → mejor puntaje del usuario en el juego. */
    public function miMejorPuntaje(array $params = []): void
    {
        header('Content-Type: application/json');

        $idUsuario = Sesion::idUsuarioActual();
        $slugJuego = $_GET['game_slug'] ?? '';

        if (!$slugJuego) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'game_slug es requerido']);
            return;
        }

        $pdo = BaseDeDatos::obtenerInstancia();

        $stmt = $pdo->prepare(
            "SELECT score, level_reached, played_at
             FROM game_scores
             WHERE user_id = ? AND game_slug = ?
             ORDER BY score DESC
             LIMIT 1"
        );
        $stmt->execute([$idUsuario, $slugJuego]);
        $mejorPuntaje = $stmt->fetch();

        echo json_encode([
            'success' => true,
            'best_score' => $mejorPuntaje ?: null,
        ]);
    }
}