-- ============================================================
-- UNA SOLA PUNTUACIÓN POR CUENTA (la más alta)
-- Ejecutar en la base de datos (phpMyAdmin de InfinityFree):
--   1) Elimina puntuaciones duplicadas, conservando solo la más
--      alta de cada usuario en cada juego
--   2) Añade una restricción UNIQUE que impide duplicados
-- ============================================================

-- 1) Borrar filas que NO son la mejor de cada usuario+juego
--    (a igualdad de puntaje se conserva la más antigua)
DELETE gs FROM game_scores gs
JOIN game_scores mej
  ON  mej.user_id   = gs.user_id
  AND mej.game_slug = gs.game_slug
  AND (
        mej.score > gs.score
        OR (mej.score = gs.score AND mej.played_at < gs.played_at)
        OR (mej.score = gs.score AND mej.played_at = gs.played_at AND mej.id < gs.id)
      );

-- 2) Índice único primero (una fila por usuario+juego) y luego
--    se elimina el índice normal que era redundante.
ALTER TABLE game_scores ADD UNIQUE KEY IF NOT EXISTS uq_user_game (user_id, game_slug);
ALTER TABLE game_scores DROP INDEX IF EXISTS idx_user_game;
