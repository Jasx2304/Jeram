-- ============================================
-- CORRECCIONES PARA HOSTING (InfinityFree)
-- Ejecutar en phpMyAdmin DESPUÉS de importar esquema.sql
-- ============================================

-- 1) Borra los juegos de prueba ("Próximamente") que venían inactivos
DELETE FROM juegos WHERE slug IN ('under-2', 'under-3', 'under-4');

-- 2) Corrige la portada de Scary Monster (antes apuntaba a un archivo inexistente)
UPDATE juegos
SET imagen_portada = 'portadas/portada_corre_scary_monster.jpg',
    imagen_miniatura = 'portadas/portada_corre_scary_monster.jpg',
    ruta_archivo = 'game?game=scary-monster',
    activo = 1,
    orden = 1
WHERE slug = 'scary-monster';

-- 3) Los 4 juegos reales (si ya existen, actualiza sus datos)
INSERT INTO juegos (slug, titulo, descripcion_corta, descripcion_larga, imagen_portada, imagen_miniatura, ruta_archivo, controles, objetivo, activo, orden) VALUES

('scary-monster', 'Corre Scary Monster',
 'Ayuda al Scary Monster a esquivar obstáculos. ¡Corre lo más lejos posible!',
 'En una época muy avanzada de nuestra vida en la ciudad de Jeram, por el poder y el avance de un humano ha logrado recrear la genética de un dinosaurio antiguo el cual se ha encariñado con su creador. Después de que Spectra secuestrara a su creador, este dinosaurio tratará de rescatarlo sorteando distintos obstáculos.',
 'portadas/portada_corre_scary_monster.jpg', 'portadas/portada_corre_scary_monster.jpg', 'game?game=scary-monster',
 '[{"tecla": "ESPACIO / ↑", "accion": "Saltar"}, {"tecla": "↓", "accion": "Agacharse"}, {"tecla": "ENTER", "accion": "Iniciar / Reiniciar"}]',
 'Esquiva obstáculos y llega lo más lejos posible. La velocidad aumenta con el tiempo y la distancia recorrida.',
 1, 1),

('el-encargo', 'El Encargo',
 'Pilota un dron en dos fases: esquiva con W/S y sobrevive al modo flappy con ESPACIO.',
 'El dron tiene dos vidas. En la primera fase muévete arriba y abajo para esquivar los obstáculos del camino. Si chocas, el dron entra en modo flappy: aletea con ESPACIO para mantenerte en el aire y pasa entre los tubos. Cuando acaben las vidas, la misión termina.',
 'portadas/portada_el_encargo.jpeg', 'portadas/portada_el_encargo.jpeg', 'game?game=el-encargo',
 '[{"tecla": "W / S  o  ↑ / ↓", "accion": "Mover el dron"}, {"tecla": "ESPACIO", "accion": "Aletear (modo flappy)"}, {"tecla": "ENTER", "accion": "Iniciar / Reiniciar"}]',
 'Gestiona tus dos vidas: sobrevive a la fase de vuelo directo y luego al modo flappy. Consigue la mayor puntuación posible.',
 1, 2),

('escape-neon', 'Escape Neon',
 'Corre por la ciudad neon esquivando barriles, tuberías y láseres.',
 'Una carrera infinita por una ciudad en neón. Corre, muévete y agáchate para esquivar tres tipos de obstáculos: barriles, tuberías y láseres bajos. La velocidad aumenta cuanto más lejos llegas.',
 'portadas/portada_escape_neon.jpg', 'portadas/portada_escape_neon.jpg', 'game?game=escape-neon',
 '[{"tecla": "← / →", "accion": "Moverse"}, {"tecla": "↓", "accion": "Agacharse"}, {"tecla": "ENTER", "accion": "Iniciar / Reiniciar"}]',
 'Llega lo más lejos posible esquivando los tres tipos de obstáculos. Cada vez serás más rápido.',
 1, 3),

('linea-de-produccion', 'Línea de Producción',
 'Memoriza y repite la secuencia de colores que produce la línea.',
 'La línea de producción fabrica secuencias de colores: cyan, magenta, amarillo y verde. Tu tarea es memorizar el patrón que se ilumina y repetirlo con el mouse. Cada acierto suma puntos y cada ronda la secuencia es más larga y rápida.',
 'portadas/portada_linea_de_produccion.jpeg', 'portadas/portada_linea_de_produccion.jpeg', 'game?game=linea-de-produccion',
 '[{"tecla": "CLIC", "accion": "Presionar los botones de color"}, {"tecla": "ENTER", "accion": "Iniciar / Reiniciar"}]',
 'Repite correctamente la secuencia de colores. No cometas errores: la memoria y el ritmo son la clave.',
 1, 4)

ON DUPLICATE KEY UPDATE
  titulo = VALUES(titulo),
  descripcion_corta = VALUES(descripcion_corta),
  descripcion_larga = VALUES(descripcion_larga),
  imagen_portada = VALUES(imagen_portada),
  imagen_miniatura = VALUES(imagen_miniatura),
  ruta_archivo = VALUES(ruta_archivo),
  controles = VALUES(controles),
  objetivo = VALUES(objetivo),
  activo = VALUES(activo),
  orden = VALUES(orden);

-- 4) Crea (o corrige) el usuario administrador
-- Contraseña: ArticMonk3*  (guardada con password_hash de PHP)
INSERT INTO usuarios (nombre, email, password, role)
VALUES ('Jasx2304', 'jasx2304@gmail.com', '$2y$12$B4EKSlZ7QqyJxQZoDttG6OpizLcU.7jdoRya.KHOe5RFRUcMnNuz2', 'admin')
ON DUPLICATE KEY UPDATE
  password = '$2y$12$B4EKSlZ7QqyJxQZoDttG6OpizLcU.7jdoRya.KHOe5RFRUcMnNuz2',
  role = 'admin';
