-- ============================================
-- JERAM - ESQUEMA COMPLETO DE BASE DE DATOS
-- ============================================
-- Nota: en InfinityFree la base de datos ya se crea desde el panel,
-- por eso este archivo NO incluye CREATE DATABASE / USE.

-- ============================================
-- USUARIOS
-- ============================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TOKENS "RECORDARME"
-- ============================================
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token (token(8)),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- RESET DE CONTRASEÑA (preparado para email futuro)
-- ============================================
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_token (email, token(8)),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- CATÁLOGO DE JUEGOS
-- ============================================
CREATE TABLE IF NOT EXISTS juegos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    titulo VARCHAR(150) NOT NULL,
    descripcion_corta VARCHAR(300) DEFAULT NULL,
    descripcion_larga TEXT DEFAULT NULL,
    imagen_portada VARCHAR(255) DEFAULT NULL,
    imagen_miniatura VARCHAR(255) DEFAULT NULL,
    ruta_archivo VARCHAR(255) DEFAULT NULL,
    controles JSON DEFAULT NULL,
    objetivo TEXT DEFAULT NULL,
    activo BOOLEAN DEFAULT TRUE,
    orden INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activo_orden (activo, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- PUNTUACIONES (RANKING EXTENSIBLE)
-- ============================================
CREATE TABLE IF NOT EXISTS game_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_slug VARCHAR(50) NOT NULL,
    score INT NOT NULL,
    level_reached INT DEFAULT 1,
    metadata JSON DEFAULT NULL,
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_game_score (game_slug, score DESC),
    UNIQUE KEY uq_user_game (user_id, game_slug),
    INDEX idx_played_at (played_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- COMENTARIOS EN JUEGOS
-- ============================================
CREATE TABLE IF NOT EXISTS game_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_slug VARCHAR(50) NOT NULL,
    parent_id INT DEFAULT NULL,
    contenido TEXT NOT NULL,
    estado ENUM('visible', 'oculto', 'reportado') DEFAULT 'visible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES game_comments(id) ON DELETE SET NULL,
    INDEX idx_game_visible (game_slug, estado, created_at DESC),
    INDEX idx_user_game (user_id, game_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;