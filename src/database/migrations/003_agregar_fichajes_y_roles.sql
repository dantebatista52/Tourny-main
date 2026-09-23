-- =========================================================================
-- MIGRACIÓN 003: Sistema de Roles de Usuario y Fichaje de Jugadores
-- Proyecto: Tourny (Quadruplex)
-- =========================================================================

-- 1. Modificar la tabla 'usuarios' para añadir la columna de rol
-- Por defecto, todo usuario nuevo se registra como 'jugador'
ALTER TABLE usuarios
ADD COLUMN rol ENUM('organizador', 'jugador') NOT NULL DEFAULT 'jugador' AFTER email;

-- 2. Modificar la tabla 'equipos' para añadir el código único de invitación
-- Permite que el capitán/organizador comparta un token de 6 caracteres
ALTER TABLE equipos
ADD COLUMN codigo_invitacion VARCHAR(10) NULL UNIQUE AFTER nombre;

-- 3. Crear la tabla relacional 'equipo_jugadores' (Fichajes)
-- Vincula a los usuarios con los equipos en los que están inscritos
CREATE TABLE IF NOT EXISTS equipo_jugadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_equipo INT NOT NULL,
    id_usuario INT NOT NULL,
    dorsal INT NULL,
    posicion VARCHAR(50) NULL,
    estado ENUM('activo', 'inactivo', 'suspendido') NOT NULL DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Restricciones de Claves Foráneas (FK)
    CONSTRAINT fk_fichaje_equipo 
        FOREIGN KEY (id_equipo) REFERENCES equipos(id) 
        ON DELETE CASCADE,
        
    CONSTRAINT fk_fichaje_usuario 
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id) 
        ON DELETE CASCADE,

    -- Un usuario no puede estar fichado dos veces en el mismo equipo
    CONSTRAINT uk_usuario_equipo UNIQUE (id_equipo, id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;