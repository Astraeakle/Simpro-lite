-- Base de datos simplificada para SIMPRO Lite

CREATE DATABASE IF NOT EXISTS simpro_lite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE simpro_lite;
-- Tabla de usuarios
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) UNIQUE NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    contraseña_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'supervisor', 'empleado') DEFAULT 'empleado',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME,
    estado ENUM('activo', 'inactivo', 'bloqueado') DEFAULT 'activo',
    avatar VARCHAR(255) DEFAULT NULL,
    telefono VARCHAR(20) DEFAULT NULL,
    departamento VARCHAR(50) DEFAULT NULL
);

-- Tabla de asistencia
CREATE TABLE registros_asistencia (
    id_registro INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    tipo ENUM('entrada', 'salida') NOT NULL,
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    latitud DECIMAL(10, 8) NULL,
    longitud DECIMAL(11, 8) NULL,
    ip_address VARCHAR(45) NULL,
    notas TEXT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- Tabla de actividades del usuario
CREATE TABLE actividad_apps (
    id_actividad INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    nombre_app VARCHAR(255) NOT NULL,
    titulo_ventana TEXT NULL,
    fecha_hora_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tiempo_segundos INT NOT NULL DEFAULT 0,
    session_id VARCHAR(100) NULL,
    categoria ENUM('productiva', 'distractora', 'neutral') DEFAULT 'neutral',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    INDEX idx_usuario_fecha (id_usuario, fecha_hora_inicio),
    INDEX idx_categoria (categoria),
    INDEX idx_app (nombre_app)
);

-- Tabla de jornadas laborales
CREATE TABLE jornadas (
    id_jornada INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NULL,
    hora_fin TIME NULL,
    estado ENUM('trabajando', 'break', 'finalizada') DEFAULT 'trabajando',
    total_horas DECIMAL(4,2) DEFAULT 0,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    UNIQUE KEY unique_user_date (id_usuario, fecha)
);

-- Tabla de tokens de autenticación
CREATE TABLE tokens_auth (
    id_token INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    token VARCHAR(500) NOT NULL,
    fecha_expiracion TIMESTAMP NOT NULL,
    dispositivo VARCHAR(255) NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- Tabla de configuración del sistema
CREATE TABLE configuracion_sistema (
    id_config INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT NOT NULL,
    descripcion TEXT NULL,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar datos iniciales
INSERT INTO usuarios (nombre_usuario, nombre_completo, contraseña_hash, rol, fecha_creacion, ultimo_acceso, estado, telefono, departamento) VALUES
('admin', 'Administrador SIMPRO', '$2y$10$ra3uVfglOefN.6X3CVUdUezRyUVdQq6sx9nln7QVx5c.3MXIrqR5u', 'admin', '2025-05-04 18:09:12', '2025-05-10 19:29:57', 'activo', NULL, NULL),
('Stephany', 'Stephany Lisseth Huertas Huallcca', '$2y$10$LXWdRopgCHuLGjcyrQrE5e4ArUuCwUP6hdrxMLSPLDJuVzH0Izp22', 'empleado', '2025-05-06 12:56:06', '2025-05-12 16:32:01', 'activo', '989164070', 'Desarrollo'),
('Ashley', 'Ashley Galarza', '$2y$10$wLRUXwdQ0BgA.HA4/.y2xeezhxy7S1wwOzUu2JHqHQCQPA4p4lPRO', 'supervisor', '2025-05-07 21:01:17', '2025-05-12 16:31:45', 'activo', NULL, NULL),
('Joselyn', 'Joselyn Briggith Valverde Estrella', '$2y$10$bBuu..aSc8nFlvIlmncoJuQrk1UF4JlQDF4tsmVIDgY/MEHjqRTie', 'empleado', '2025-05-09 11:30:49', '2025-05-12 16:38:13', 'activo', '910031973', NULL);


-- Configuración inicial
INSERT INTO configuracion_sistema (clave, valor, descripcion) VALUES 
('intervalo_monitor', '10', 'Intervalo de monitoreo en segundos'),
('duracion_minima_actividad', '5', 'Duración mínima para registrar actividad'),
('token_expiration_hours', '12', 'Horas de duración del token');
