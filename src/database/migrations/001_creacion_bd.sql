-- Create Database
CREATE DATABASE IF NOT EXISTS tourny CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tourny;

-- Disable Foreign Key Checks for clean table creation
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS partidos;
DROP TABLE IF EXISTS equipos;
DROP TABLE IF EXISTS torneos;
DROP TABLE IF EXISTS usuarios;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Tabla de Usuarios (Organizadores)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de Torneos
CREATE TABLE torneos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_organizador INT NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    formato ENUM('liga', 'eliminatoria') NOT NULL DEFAULT 'liga',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_organizador) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de Equipos
CREATE TABLE equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_torneo INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_torneo) REFERENCES torneos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla de Partidos (Fixture)
CREATE TABLE partidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_torneo INT NOT NULL,
    id_equipo_local INT NULL,
    id_equipo_visitante INT NULL,
    fecha_numero INT NOT NULL DEFAULT 1,
    goles_local INT DEFAULT NULL,
    goles_visitante INT DEFAULT NULL,
    estado ENUM('pendiente', 'finalizado') NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_torneo) REFERENCES torneos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_equipo_local) REFERENCES equipos(id) ON DELETE SET NULL,
    FOREIGN KEY (id_equipo_visitante) REFERENCES equipos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
