-- ============================================
-- DATOS INICIALES DE JUEGOS
-- ============================================

INSERT IGNORE INTO juegos (slug, titulo, descripcion_corta, descripcion_larga, imagen_portada, imagen_miniatura, ruta_archivo, controles, objetivo, activo, orden) VALUES
('scary-monster', 'Corre Scary Monster',
 'Ayuda al Scary Monster a esquivar obstáculos. ¡Corre lo más lejos posible!',
 'En una época muy avanzada de nuestra vida en la ciudad de Jeram, por el poder y el avance de un humano ha logrado recrear la genética de un dinosaurio antiguo el cual se ha encariñado con su creador. Después de que Spectra secuestrara a su creador, este dinosaurio tratará de rescatarlo sorteando distintos obstáculos.',
 'scary_monster.jpg', 'scary_monster.jpg', 'juego.php?game=scary-monster',
 '[{"tecla": "ESPACIO / ↑", "accion": "Saltar"}, {"tecla": "↓", "accion": "Agacharse"}, {"tecla": "ENTER", "accion": "Iniciar / Reiniciar"}]',
 'Esquiva obstáculos y llega lo más lejos posible. La velocidad aumenta con el tiempo y la distancia recorrida.',
 1, 1),

('under-2', 'Próximamente 1',
 'Nuevo juego en desarrollo',
 '', 'en_construccion.png', 'en_construccion.png', '#',
 '[]', '',
 0, 2),

('under-3', 'Próximamente 2',
 'Nuevo juego en desarrollo',
 '', 'en_construccion.png', 'en_construccion.png', '#',
 '[]', '',
 0, 3),

('under-4', 'Próximamente 3',
 'Nuevo juego en desarrollo',
 '', 'en_construccion.png', 'en_construccion.png', '#',
 '[]', '',
 0, 4);