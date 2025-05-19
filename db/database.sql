-- File: db/database.sql
-- Creación de la base de datos
CREATE DATABASE IF NOT EXISTS simpro_lite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE simpro_lite;
-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
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
) ENGINE=InnoDB;

-- Tabla de tokens de autenticación
CREATE TABLE IF NOT EXISTS tokens_auth (
    id_token INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    dispositivo VARCHAR(100) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de registros de asistencia
CREATE TABLE IF NOT EXISTS registros_asistencia (
    id_registro INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    tipo ENUM('entrada', 'salida', 'break', 'fin_break') NOT NULL,
    fecha_hora DATETIME NOT NULL,
    latitud DECIMAL(10, 8) DEFAULT NULL,
    longitud DECIMAL(11, 8) DEFAULT NULL,
    direccion VARCHAR(255) DEFAULT NULL,
    dispositivo VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    metodo ENUM('web', 'cliente', 'movil') DEFAULT 'web',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de actividad de aplicaciones
CREATE TABLE IF NOT EXISTS actividad_apps (
    id_actividad INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    nombre_app VARCHAR(100) NOT NULL,
    titulo_ventana VARCHAR(255) DEFAULT NULL,
    fecha_hora_inicio DATETIME NOT NULL,
    fecha_hora_fin DATETIME DEFAULT NULL,
    tiempo_segundos INT DEFAULT NULL,
    categoria ENUM('productiva', 'distractora', 'neutral') DEFAULT 'neutral',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de proyectos
CREATE TABLE IF NOT EXISTS proyectos (
    id_proyecto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin_estimada DATE DEFAULT NULL,
    fecha_fin_real DATE DEFAULT NULL,
    estado ENUM('planificacion', 'en_progreso', 'completado', 'cancelado') DEFAULT 'planificacion',
    id_responsable INT DEFAULT NULL,
    FOREIGN KEY (id_responsable) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabla de tareas
CREATE TABLE IF NOT EXISTS tareas (
    id_tarea INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto INT NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_limite DATETIME DEFAULT NULL,
    prioridad ENUM('baja', 'media', 'alta', 'urgente') DEFAULT 'media',
    estado ENUM('pendiente', 'en_progreso', 'en_revision', 'completada', 'cancelada') DEFAULT 'pendiente',
    id_asignado INT DEFAULT NULL,
    FOREIGN KEY (id_proyecto) REFERENCES proyectos(id_proyecto) ON DELETE CASCADE,
    FOREIGN KEY (id_asignado) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabla de configuración del sistema
CREATE TABLE IF NOT EXISTS configuracion (
    id_config INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) NOT NULL UNIQUE,
    valor TEXT NOT NULL,
    descripcion TEXT DEFAULT NULL,
    editable BOOLEAN DEFAULT TRUE,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de logs del sistema
CREATE TABLE IF NOT EXISTS logs_sistema (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    tipo VARCHAR(20) NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    mensaje TEXT NOT NULL,
    id_usuario INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabla de notificaciones
CREATE TABLE IF NOT EXISTS notificaciones (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    mensaje TEXT NOT NULL,
    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_leido DATETIME DEFAULT NULL,
    leido BOOLEAN DEFAULT FALSE,
    tipo ENUM('sistema', 'asistencia', 'tarea', 'proyecto') DEFAULT 'sistema',
    id_referencia INT DEFAULT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE sesiones_monitor (
    id_sesion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME DEFAULT NULL,
    tipo ENUM('trabajo', 'break') DEFAULT 'trabajo',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

-- Índices para mejorar el rendimiento
CREATE INDEX idx_usuario_asistencia ON registros_asistencia(id_usuario);
CREATE INDEX idx_fecha_asistencia ON registros_asistencia(fecha_hora);
CREATE INDEX idx_usuario_actividad ON actividad_apps(id_usuario);
CREATE INDEX idx_fecha_actividad ON actividad_apps(fecha_hora_inicio);
CREATE INDEX idx_proyecto_tarea ON tareas(id_proyecto);
CREATE INDEX idx_usuario_notificaciones ON notificaciones(id_usuario);



-- Insertar los nuevos usuarios
INSERT INTO usuarios (nombre_usuario, nombre_completo, contraseña_hash, rol, fecha_creacion, ultimo_acceso, estado, telefono, departamento) VALUES
('admin', 'Administrador SIMPRO', '$2y$10$ra3uVfglOefN.6X3CVUdUezRyUVdQq6sx9nln7QVx5c.3MXIrqR5u', 'admin', '2025-05-04 18:09:12', '2025-05-10 19:29:57', 'activo', NULL, NULL),
('Stephany', 'Stephany Lisseth Huertas Huallcca', '$2y$10$LXWdRopgCHuLGjcyrQrE5e4ArUuCwUP6hdrxMLSPLDJuVzH0Izp22', 'empleado', '2025-05-06 12:56:06', '2025-05-12 16:32:01', 'activo', '989164070', 'Desarrollo'),
('Ashley', 'Ashley Galarza', '$2y$10$wLRUXwdQ0BgA.HA4/.y2xeezhxy7S1wwOzUu2JHqHQCQPA4p4lPRO', 'supervisor', '2025-05-07 21:01:17', '2025-05-12 16:31:45', 'activo', NULL, NULL),
('Joselyn', 'Joselyn Briggith Valverde Estrella', '$2y$10$bBuu..aSc8nFlvIlmncoJuQrk1UF4JlQDF4tsmVIDgY/MEHjqRTie', 'empleado', '2025-05-09 11:30:49', '2025-05-12 16:38:13', 'activo', '910031973', NULL);
