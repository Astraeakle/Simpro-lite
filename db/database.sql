CREATE DATABASE IF NOT EXISTS `simpro_lite` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `simpro_lite`;

-- Tabla actividad_apps
CREATE TABLE `actividad_apps` (
  `id_actividad` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `nombre_app` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo_ventana` text COLLATE utf8mb4_unicode_ci,
  `fecha_hora_inicio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tiempo_segundos` int NOT NULL DEFAULT '0',
  `categoria` enum('productiva','distractora','neutral') COLLATE utf8mb4_unicode_ci DEFAULT 'neutral',
  `session_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_actividad`),
  KEY `idx_usuario_fecha` (`id_usuario`,`fecha_hora_inicio`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_app` (`nombre_app`),
  CONSTRAINT `actividad_apps_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=362 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla actividades
CREATE TABLE `actividades` (
  `id_actividad` int NOT NULL AUTO_INCREMENT,
  `id_proyecto` int NOT NULL,
  `titulo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_limite` datetime DEFAULT NULL,
  `prioridad` enum('baja','media','alta','urgente') COLLATE utf8mb4_unicode_ci DEFAULT 'media',
  `estado` enum('pendiente','en_progreso','en_revision','completada','cancelada') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `id_asignado` int DEFAULT NULL,
  PRIMARY KEY (`id_actividad`),
  KEY `id_proyecto` (`id_proyecto`),
  KEY `id_asignado` (`id_asignado`),
  CONSTRAINT `actividades_ibfk_1` FOREIGN KEY (`id_proyecto`) REFERENCES `proyectos` (`id_proyecto`) ON DELETE CASCADE,
  CONSTRAINT `actividades_ibfk_2` FOREIGN KEY (`id_asignado`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla configuracion
CREATE TABLE `configuracion` (
  `id_config` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `editable` tinyint(1) DEFAULT '1',
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_config`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla logs_sistema
CREATE TABLE `logs_sistema` (
  `id_log` int NOT NULL AUTO_INCREMENT,
  `fecha_hora` datetime DEFAULT CURRENT_TIMESTAMP,
  `tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modulo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_usuario` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_log`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `logs_sistema_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla notificaciones
CREATE TABLE `notificaciones` (
  `id_notificacion` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `titulo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_envio` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_leido` datetime DEFAULT NULL,
  `leido` tinyint(1) DEFAULT '0',
  `tipo` enum('sistema','asistencia','tarea','proyecto') COLLATE utf8mb4_unicode_ci DEFAULT 'sistema',
  `id_referencia` int DEFAULT NULL,
  PRIMARY KEY (`id_notificacion`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla proyectos
CREATE TABLE `proyectos` (
  `id_proyecto` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `fecha_inicio` date NOT NULL,
  `fecha_fin_estimada` date DEFAULT NULL,
  `fecha_fin_real` date DEFAULT NULL,
  `estado` enum('planificacion','en_progreso','completado','cancelado') COLLATE utf8mb4_unicode_ci DEFAULT 'planificacion',
  `id_responsable` int DEFAULT NULL,
  PRIMARY KEY (`id_proyecto`),
  KEY `id_responsable` (`id_responsable`),
  CONSTRAINT `proyectos_ibfk_1` FOREIGN KEY (`id_responsable`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla registros_asistencia
CREATE TABLE `registros_asistencia` (
  `id_registro` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `tipo` enum('entrada','salida','break','fin_break') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dispositivo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metodo` enum('web','cliente','movil') COLLATE utf8mb4_unicode_ci DEFAULT 'web',
  PRIMARY KEY (`id_registro`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `registros_asistencia_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla sesiones_monitor
CREATE TABLE `sesiones_monitor` (
  `id_sesion` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `tipo` enum('trabajo','break') COLLATE utf8mb4_unicode_ci DEFAULT 'trabajo',
  PRIMARY KEY (`id_sesion`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `sesiones_monitor_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla tokens_auth
CREATE TABLE `tokens_auth` (
  `id_token` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` datetime NOT NULL,
  `dispositivo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_token`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `tokens_auth_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=176 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla usuarios
CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `nombre_usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_completo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contraseña_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('admin','supervisor','empleado') COLLATE utf8mb4_unicode_ci DEFAULT 'empleado',
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `ultimo_acceso` datetime DEFAULT NULL,
  `estado` enum('activo','inactivo','bloqueado') COLLATE utf8mb4_unicode_ci DEFAULT 'activo',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area` enum('Administración','Contabilidad','Ingeniería','Marketing','Proyectos','Ambiental','Derecho') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supervisor_id` int DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  KEY `idx_usuarios_nombre_usuario` (`nombre_usuario`),
  KEY `idx_usuarios_rol` (`rol`),
  KEY `idx_usuarios_estado` (`estado`),
  KEY `idx_usuarios_fecha_creacion` (`fecha_creacion`),
  KEY `idx_usuarios_ultimo_acceso` (`ultimo_acceso`),
  KEY `idx_usuarios_departamento` (`area`),
  KEY `idx_supervisor` (`supervisor_id`),
  CONSTRAINT `fk_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserts para configuracion
INSERT INTO `configuracion` VALUES 
(1,'intervalo_monitor','10','Intervalo de monitoreo en segundos',1,'2025-06-20 22:47:26'),
(2,'duracion_minima_actividad','1','Duración mínima para registrar actividad',1,'2025-06-20 23:19:41'),
(3,'token_expiration_hours','12','Horas de duración del token',1,'2025-06-20 22:47:26'),
(4,'api_url','http://localhost/simpro-lite/api/v1','URL base de la API',1,'2025-06-20 23:15:51'),
(5,'login_url','http://localhost/simpro-lite/api/v1/autenticar.php','Endpoint para autenticación',1,'2025-06-20 23:15:51'),
(6,'activity_url','http://localhost/simpro-lite/api/v1/actividad.php','Endpoint para registrar actividades',1,'2025-06-20 23:15:51'),
(7,'config_url','http://localhost/simpro-lite/api/v1/api_config.php','Endpoint para obtener configuración',1,'2025-06-20 23:38:23'),
(8,'apps_productivas','[\"Chrome.exe\",\"python.exe\",\"Meet\",\"firefox.exe\",\"edge.exe\",\"code.exe\",\"vscode.exe\",\"word.exe\",\"excel.exe\",\"powerpoint.exe\",\"outlook.exe\",\"teams.exe\",\"zoom.exe\",\"slack.exe\",\"notepad.exe\",\"sublime_text.exe\",\"pycharm64.exe\",\"atom.exe\",\"idea64.exe\",\"eclipse.exe\",\"netbeans.exe\",\"photoshop.exe\",\"illustrator.exe\",\"indesign.exe\",\"blender.exe\",\"Code\"]','Lista de aplicaciones consideradas productivas (JSON array)',1,'2025-06-20 23:22:59'),
(9,'apps_distractoras','[\"steam.exe\",\"epicgameslauncher.exe\",\"discord.exe\",\"spotify.exe\",\"netflix.exe\",\"vlc.exe\",\"tiktok.exe\",\"facebook.exe\",\"twitter.exe\",\"instagram.exe\",\"whatsapp.exe\",\"telegram.exe\",\"skype.exe\",\"youtube.exe\",\"twitch.exe\",\"origin.exe\",\"uplay.exe\",\"battlenet.exe\"]','Lista de aplicaciones consideradas distractoras (JSON array)',1,'2025-06-20 23:15:51'),
(10,'estado_jornada_url','http://localhost/simpro-lite/api/v1/estado_jornada.php','Endpoint para verificar estado de jornada',1,'2025-06-20 23:15:51'),
(11,'verificar_tabla_url','http://localhost/simpro-lite/api/v1/verificar_tabla.php','Endpoint para verificar estructura en servidor',1,'2025-06-20 23:15:51'),
(12,'max_actividades_pendientes','100','Máximo de actividades pendientes de sincronizar',1,'2025-06-20 23:15:51'),
(13,'auto_sync_interval','300','Intervalo para sincronización automática en segundos',1,'2025-06-20 23:15:51'),
(14,'max_title_length','255','Longitud máxima para títulos de ventana',1,'2025-06-20 23:15:51'),
(15,'max_appname_length','100','Longitud máxima para nombres de aplicación',1,'2025-06-20 23:15:51'),
(16,'min_sync_duration','5','Duración mínima para sincronizar actividad',1,'2025-06-20 23:15:51'),
(17,'sync_retry_attempts','1','Intentos de reintento para sincronización fallida',1,'2025-06-30 21:48:13');

-- Inserts para usuarios
INSERT INTO `usuarios` VALUES 
(1,'admin','Administrador SIMPRO','$2y$10$ra3uVfglOefN.6X3CVUdUezRyUVdQq6sx9nln7QVx5c.3MXIrqR5u','admin','2025-05-04 18:09:12','2025-07-05 19:20:50','activo',NULL,'','Administración',NULL),
(2,'Stephany','Stephany Lisseth Huertas Huallcca','$2y$10$LXWdRopgCHuLGjcyrQrE5e4ArUuCwUP6hdrxMLSPLDJuVzH0Izp22','empleado','2025-05-06 12:56:06','2025-07-06 16:01:53','activo',NULL,'989164070','Ingeniería',7),
(3,'Ashley','Ashley Galarza','$2y$10$wLRUXwdQ0BgA.HA4/.y2xeezhxy7S1wwOzUu2JHqHQCQPA4p4lPRO','supervisor','2025-05-07 21:01:17','2025-07-04 22:44:17','activo',NULL,'','Ingeniería',NULL),
(4,'Joselyn','Joselyn Briggith Valverde Estrella','$2y$10$bBuu..aSc8nFlvIlmncoJuQrk1UF4JlQDF4tsmVIDgY/MEHjqRTie','empleado','2025-05-09 11:30:49','2025-07-05 19:21:30','activo',NULL,'910031973','Contabilidad',3),
(7,'Issac','Issac Vega','$2y$10$ydSg0uugFLlhbnuHBrGwdOTSH5hiMAdOR/U1RTqkN1JKCFqqvxWlK','supervisor','2025-06-20 17:41:25','2025-07-05 00:03:00','activo',NULL,'+57 302 4472008','Administración',NULL),
(8,'76512429','prueba ','$2y$10$CtR4lb1btPFYFNeX601lBOCR2yWZN2.YMU7ZQp.xgNYK9YMWP81iS','empleado','2025-06-21 09:54:50','2025-06-28 18:06:26','activo',NULL,'910031973','Administración',NULL);

-- Procedimientos almacenados
DELIMITER ;;

CREATE PROCEDURE `sp_actividades_dia`(
    IN p_id_usuario INT,
    IN p_fecha DATE
)
BEGIN
    SELECT 
        COUNT(*) as total_actividades,
        COUNT(CASE WHEN estado = 'completada' THEN 1 END) as completadas,
        COUNT(CASE WHEN estado = 'en_progreso' THEN 1 END) as en_progreso,
        COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as pendientes
    FROM actividades a
    INNER JOIN proyectos p ON a.id_proyecto = p.id_proyecto
    WHERE a.id_asignado = p_id_usuario 
    AND (
        DATE(a.fecha_creacion) = p_fecha OR 
        DATE(a.fecha_limite) = p_fecha OR
        a.estado IN ('en_progreso', 'en_revision')
    );
END ;;

CREATE PROCEDURE `sp_actualizar_estado_actividad`(
    IN p_id_actividad INT,
    IN p_nuevo_estado ENUM('pendiente','en_progreso','en_revision','completada','cancelada'),
    IN p_id_usuario INT
)
BEGIN
    DECLARE v_filas_afectadas INT DEFAULT 0;
    DECLARE v_estado_anterior VARCHAR(20);
    
    SELECT estado INTO v_estado_anterior
    FROM actividades 
    WHERE id_actividad = p_id_actividad AND id_asignado = p_id_usuario;
    
    IF v_estado_anterior IS NULL THEN
        SELECT FALSE as success, 'Actividad no encontrada o sin permisos' as mensaje;
    ELSE
        UPDATE actividades 
        SET estado = p_nuevo_estado
        WHERE id_actividad = p_id_actividad AND id_asignado = p_id_usuario;
        
        SET v_filas_afectadas = ROW_COUNT();
        
        IF v_filas_afectadas > 0 THEN
            IF p_nuevo_estado = 'completada' THEN
                INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, id_referencia)
                SELECT 
                    p.id_responsable,
                    CONCAT('Actividad completada: ', a.titulo),
                    CONCAT('La actividad "', a.titulo, '" del proyecto "', p.nombre, '" ha sido marcada como completada.'),
                    'tarea',
                    p_id_actividad
                FROM actividades a
                INNER JOIN proyectos p ON a.id_proyecto = p.id_proyecto
                WHERE a.id_actividad = p_id_actividad AND p.id_responsable IS NOT NULL;
            END IF;
            
            SELECT TRUE as success, 'Estado actualizado correctamente' as mensaje, v_estado_anterior as estado_anterior;
        ELSE
            SELECT FALSE as success, 'No se pudo actualizar el estado' as mensaje;
        END IF;
    END IF;
END ;;

CREATE PROCEDURE `sp_actualizar_usuario`(
    IN p_id_usuario INT,
    IN p_campos JSON,
    OUT p_resultado JSON
)
BEGIN
    DECLARE v_existe INT DEFAULT 0;
    DECLARE v_sql TEXT DEFAULT '';
    DECLARE v_set_clause TEXT DEFAULT '';
    DECLARE v_error_msg VARCHAR(255) DEFAULT '';
    DECLARE v_keys JSON;
    DECLARE v_key_count INT DEFAULT 0;
    DECLARE v_i INT DEFAULT 0;
    DECLARE v_campo VARCHAR(50);
    DECLARE v_valor TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            v_error_msg = MESSAGE_TEXT;
        SET p_resultado = JSON_OBJECT('success', false, 'error', v_error_msg);
        ROLLBACK;
    END;

    START TRANSACTION;

    SELECT COUNT(*) INTO v_existe
    FROM usuarios 
    WHERE id_usuario = p_id_usuario;

    IF v_existe = 0 THEN
        SET p_resultado = JSON_OBJECT('success', false, 'error', 'Usuario no encontrado');
        ROLLBACK;
    ELSE
        SET v_keys = JSON_KEYS(p_campos);
        SET v_key_count = JSON_LENGTH(v_keys);

        WHILE v_i < v_key_count DO
            SET v_campo = JSON_UNQUOTE(JSON_EXTRACT(v_keys, CONCAT('$[', v_i, ']'));
            SET v_valor = JSON_UNQUOTE(JSON_EXTRACT(p_campos, CONCAT('$.', v_campo)));

            IF v_campo IN ('nombre_usuario', 'nombre_completo', 'rol', 'estado', 'telefono', 'area', 'contraseña_hash') THEN
                IF v_set_clause != '' THEN
                    SET v_set_clause = CONCAT(v_set_clause, ', ');
                END IF;
                SET v_set_clause = CONCAT(v_set_clause, v_campo, ' = "', REPLACE(v_valor, '"', '\\"'), '"');
            END IF;

            SET v_i = v_i + 1;
        END WHILE;

        IF v_set_clause = '' THEN
            SET p_resultado = JSON_OBJECT('success', false, 'error', 'No hay campos válidos para actualizar');
            ROLLBACK;
        ELSE
            SET v_sql = CONCAT('UPDATE usuarios SET ', v_set_clause, ' WHERE id_usuario = ', p_id_usuario);
            SET @sql = v_sql;
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;

            SET p_resultado = JSON_OBJECT('success', true, 'message', 'Usuario actualizado correctamente');
            COMMIT;
        END IF;
    END IF;
END ;;

CREATE PROCEDURE `sp_apps_usadas_dia`(
    IN p_id_usuario INT,
    IN p_fecha DATE
)
BEGIN
    SELECT COUNT(DISTINCT nombre_app) as total_apps
    FROM actividad_apps 
    WHERE id_usuario = p_id_usuario 
    AND DATE(fecha_hora_inicio) = p_fecha;
END ;;

CREATE PROCEDURE `sp_contar_no_leidas`(
    IN p_id_usuario INT
)
BEGIN
    SELECT COUNT(*) as total_no_leidas 
    FROM notificaciones 
    WHERE id_usuario = p_id_usuario AND leido = 0;
END ;;

CREATE PROCEDURE `sp_crear_notificacion`(
    IN p_id_usuario INT,
    IN p_titulo VARCHAR(100),
    IN p_mensaje TEXT,
    IN p_tipo ENUM('sistema','asistencia','tarea','proyecto'),
    IN p_id_referencia INT
)
BEGIN
    INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, id_referencia) 
    VALUES (p_id_usuario, p_titulo, p_mensaje, p_tipo, p_id_referencia);
    
    SELECT LAST_INSERT_ID() as id_notificacion;
END ;;

CREATE PROCEDURE `sp_crear_solicitud_cambio`(
    IN p_supervisor_id INT,
    IN p_empleado_id INT,
    IN p_motivo TEXT,
    OUT p_resultado JSON
)
BEGIN
    DECLARE v_supervisor_depto VARCHAR(50) DEFAULT '';
    DECLARE v_empleado_depto VARCHAR(50) DEFAULT '';
    DECLARE v_empleado_nombre VARCHAR(100) DEFAULT '';
    DECLARE v_supervisor_nombre VARCHAR(100) DEFAULT '';
    DECLARE v_admin_count INT DEFAULT 0;
    DECLARE v_error_msg VARCHAR(255) DEFAULT '';

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            v_error_msg = MESSAGE_TEXT;
        SET p_resultado = JSON_OBJECT('success', false, 'error', v_error_msg);
        ROLLBACK;
    END;

    START TRANSACTION;

    SELECT area, nombre_completo INTO v_supervisor_depto, v_supervisor_nombre
    FROM usuarios 
    WHERE id_usuario = p_supervisor_id AND rol = 'supervisor';

    SELECT area, nombre_completo INTO v_empleado_depto, v_empleado_nombre
    FROM usuarios 
    WHERE id_usuario = p_empleado_id AND rol = 'empleado';

    SELECT COUNT(*) INTO v_admin_count
    FROM usuarios 
    WHERE rol = 'admin' AND estado = 'activo';

    IF v_admin_count = 0 THEN
        SET p_resultado = JSON_OBJECT('success', false, 'error', 'No hay administradores disponibles para procesar la solicitud');
        ROLLBACK;
    ELSE
        INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, id_referencia)
        SELECT 
            id_usuario,
            'Solicitud de Asignación Inter-departamental',
            CONCAT('El supervisor ', v_supervisor_nombre, ' (', v_supervisor_depto, ') solicita asignar al empleado ', 
                   v_empleado_nombre, ' (', v_empleado_depto, '). Motivo: ', p_motivo),
            'sistema',
            p_empleado_id
        FROM usuarios 
        WHERE rol = 'admin' AND estado = 'activo';

        INSERT INTO logs_sistema (tipo, modulo, mensaje, id_usuario)
        VALUES ('INFO', 'SUPERVISOR', 
               CONCAT('Solicitud creada para asignar empleado de otro area: ', v_empleado_nombre),
               p_supervisor_id);

        SET p_resultado = JSON_OBJECT(
            'success', true,
            'message', 'Solicitud enviada a los administradores para revisión'
        );
        COMMIT;
    END IF;
END ;;

CREATE PROCEDURE `sp_crear_usuario`(
    IN p_nombre_usuario VARCHAR(50),
    IN p_nombre_completo VARCHAR(100),
    IN p_contraseña_hash VARCHAR(255),
    IN p_rol ENUM('admin', 'supervisor', 'empleado'),
    IN p_estado ENUM('activo', 'inactivo', 'bloqueado'),
    IN p_telefono VARCHAR(20),
    IN p_area ENUM('Administración', 'Contabilidad', 'Ingeniería', 'Marketing', 'Proyectos', 'Ambiental', 'Derecho'),
    OUT p_resultado JSON
)
BEGIN
    DECLARE v_existe INT DEFAULT 0;
    DECLARE v_nuevo_id INT DEFAULT 0;
    DECLARE v_error_msg VARCHAR(255) DEFAULT '';

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            v_error_msg = MESSAGE_TEXT;
        SET p_resultado = JSON_OBJECT('success', false, 'error', v_error_msg);
        ROLLBACK;
    END;

    START TRANSACTION;

    SELECT COUNT(*) INTO v_existe
    FROM usuarios 
    WHERE nombre_usuario = p_nombre_usuario;

    IF v_existe > 0 THEN
        SET p_resultado = JSON_OBJECT('success', false, 'error', 'El nombre de usuario ya existe');
        ROLLBACK;
    ELSE
        INSERT INTO usuarios (
            nombre_usuario, nombre_completo, contraseña_hash, rol, 
            estado, telefono, area
        ) VALUES (
            p_nombre_usuario, p_nombre_completo, p_contraseña_hash, p_rol,
            COALESCE(p_estado, 'activo'), p_telefono, p_area
        );

        SET v_nuevo_id = LAST_INSERT_ID();

        SELECT JSON_OBJECT(
            'success', true,
            'message', 'Usuario creado correctamente',
            'data', JSON_OBJECT(
                'id_usuario', v_nuevo_id,
                'nombre_usuario', p_nombre_usuario,
                'nombre_completo', p_nombre_completo,
                'rol', p_rol,
                'estado', p_estado,
                'telefono', p_telefono,
                'area', p_area
            )
        ) INTO p_resultado;

        COMMIT;
    END IF;
END ;;

CREATE PROCEDURE `sp_eliminar_usuario`(
    IN p_id_usuario INT,
    IN p_usuario_actual INT,
    OUT p_resultado JSON
)
BEGIN
    DECLARE v_error_msg VARCHAR(255) DEFAULT '';
    DECLARE v_nombre_usuario VARCHAR(50) DEFAULT '';
    DECLARE v_count INT DEFAULT 0;
    DECLARE v_rol VARCHAR(20) DEFAULT '';
    DECLARE v_admin_count INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            v_error_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_resultado = JSON_OBJECT(
            'success', FALSE,
            'error', CONCAT('Error en base de datos: ', v_error_msg)
        );
    END;

    START TRANSACTION;

    SELECT COUNT(*) INTO v_count
    FROM usuarios 
    WHERE id_usuario = p_id_usuario;

    IF v_count = 0 THEN
        SET p_resultado = JSON_OBJECT(
            'success', FALSE,
            'error', 'Usuario no encontrado'
        );
        ROLLBACK;
    ELSE
        SELECT nombre_usuario, rol 
        INTO v_nombre_usuario, v_rol
        FROM usuarios 
        WHERE id_usuario = p_id_usuario;

        IF p_id_usuario = p_usuario_actual THEN
            SET p_resultado = JSON_OBJECT(
                'success', FALSE,
                'error', 'No puedes eliminar tu propia cuenta'
            );
            ROLLBACK;
        ELSE
            IF v_rol = 'admin' THEN
                SELECT COUNT(*) INTO v_admin_count
                FROM usuarios 
                WHERE rol = 'admin' AND estado = 'activo' AND id_usuario != p_id_usuario;

                IF v_admin_count = 0 THEN
                    SET p_resultado = JSON_OBJECT(
                        'success', FALSE,
                        'error', 'No se puede eliminar el último administrador activo'
                    );
                    ROLLBACK;
                ELSE
                    DELETE FROM usuarios WHERE id_usuario = p_id_usuario;

                    SET p_resultado = JSON_OBJECT(
                        'success', TRUE,
                        'message', CONCAT('Usuario "', v_nombre_usuario, '" eliminado correctamente')
                    );
                    COMMIT;
                END IF;
            ELSE
                DELETE FROM usuarios WHERE id_usuario = p_id_usuario;

                SET p_resultado = JSON_OBJECT(
                    'success', TRUE,
                    'message', CONCAT('Usuario "', v_nombre_usuario, '" eliminado correctamente')
                );
                COMMIT;
            END IF;
        END IF;
    END IF;
END ;;

CREATE PROCEDURE `sp_estadisticas_equipo_supervisor`(
    IN p_supervisor_id INT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    SELECT 
        COUNT(u.id_usuario) AS total_empleados,
        COUNT(u.id_usuario) AS empleados_activos,
        
        COALESCE(
            (SELECT SEC_TO_TIME(SUM(aa.tiempo_segundos))
             FROM actividad_apps aa
             INNER JOIN usuarios emp ON aa.id_usuario = emp.id_usuario
             WHERE emp.supervisor_id = p_supervisor_id
             AND emp.rol = 'empleado'
             AND emp.estado = 'activo'
             AND DATE(aa.fecha_hora_inicio) BETWEEN p_fecha_inicio AND p_fecha_fin),
            '00:00:00'
        ) AS tiempo_total_equipo,
        
        COALESCE(
            (SELECT ROUND(
                SUM(CASE WHEN aa.categoria = 'productiva' THEN aa.tiempo_segundos ELSE 0 END) * 100.0 / 
                GREATEST(SUM(aa.tiempo_segundos), 1), 2
            )
             FROM actividad_apps aa
             INNER JOIN usuarios emp ON aa.id_usuario = emp.id_usuario
             WHERE emp.supervisor_id = p_supervisor_id
             AND emp.rol = 'empleado'
             AND emp.estado = 'activo'
             AND DATE(aa.fecha_hora_inicio) BETWEEN p_fecha_inicio AND p_fecha_fin),
            0
        ) AS porcentaje_productivo_equipo
        
    FROM usuarios u
    WHERE u.supervisor_id = p_supervisor_id
    AND u.rol = 'empleado'
    AND u.estado = 'activo';
END ;;

CREATE PROCEDURE `sp_estadisticas_notificaciones`(
    IN p_id_usuario INT
)
BEGIN
    SELECT 
        COUNT(*) as total_notificaciones,
        SUM(CASE WHEN leido = 0 THEN 1 ELSE 0 END) as no_leidas,
        SUM(CASE WHEN leido = 1 THEN 1 ELSE 0 END) as leidas,
        SUM(CASE WHEN tipo = 'tarea' THEN 1 ELSE 0 END) as tareas,
        SUM(CASE WHEN tipo = 'proyecto' THEN 1 ELSE 0 END) as proyectos,
        SUM(CASE WHEN tipo = 'sistema' THEN 1 ELSE 0 END) as sistema,
        SUM(CASE WHEN tipo = 'asistencia' THEN 1 ELSE 0 END) as asistencia
    FROM notificaciones 
    WHERE id_usuario = p_id_usuario;
END ;;

CREATE PROCEDURE `sp_estadisticas_usuarios`()
BEGIN
    SELECT 
        COUNT(*) as total_usuarios,
        SUM(CASE WHEN rol = 'admin' THEN 1 ELSE 0 END) as total_admins,
        SUM(CASE WHEN rol = 'supervisor' THEN 1 ELSE 0 END) as total_supervisores,
        SUM(CASE WHEN rol = 'empleado' THEN 1 ELSE 0 END) as total_empleados,
        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as usuarios_activos,
        SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as usuarios_inactivos,
        SUM(CASE WHEN estado = 'bloqueado' THEN 1 ELSE 0 END) as usuarios_bloqueados,
        SUM(CASE WHEN ultimo_acceso IS NULL THEN 1 ELSE 0 END) as nunca_ingresaron,
        SUM(CASE WHEN ultimo_acceso >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as activos_ultima_semana,
        SUM(CASE WHEN ultimo_acceso >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as activos_ultimo_mes
    FROM usuarios;
END ;;

CREATE PROCEDURE `sp_marcar_leida`(
    IN p_id_notificacion INT,
    IN p_id_usuario INT
)
BEGIN
    UPDATE notificaciones 
    SET leido = 1, fecha_leido = NOW() 
    WHERE id_notificacion = p_id_notificacion 
    AND id_usuario = p_id_usuario;
    
    SELECT ROW_COUNT() as affected_rows;
END ;;

CREATE PROCEDURE `sp_marcar_notificacion_leida`(
    IN p_id_notificacion INT,
    IN p_id_usuario INT
)
BEGIN
    UPDATE notificaciones 
    SET leido = TRUE, fecha_leido = NOW()
    WHERE id_notificacion = p_id_notificacion 
    AND id_usuario = p_id_usuario
    AND leido = FALSE;
    
    SELECT ROW_COUNT() as filas_actualizadas;
END ;;

CREATE PROCEDURE `sp_marcar_todas_leidas`(
    IN p_id_usuario INT
)
BEGIN
    UPDATE notificaciones 
    SET leido = 1, fecha_leido = NOW() 
    WHERE id_usuario = p_id_usuario AND leido = 0;
    
    SELECT ROW_COUNT() as affected_rows;
END ;;

CREATE PROCEDURE `sp_notificar_asignacion_tarea`(
    IN p_id_empleado INT,
    IN p_id_actividad INT,
    IN p_nombre_tarea VARCHAR(100),
    IN p_supervisor_nombre VARCHAR(100)
)
BEGIN
    DECLARE v_titulo VARCHAR(100);
    DECLARE v_mensaje TEXT;
    
    SET v_titulo = 'Nueva tarea asignada';
    SET v_mensaje = CONCAT('El supervisor ', p_supervisor_nombre, ' te ha asignado la tarea: ', p_nombre_tarea);
    
    INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, id_referencia) 
    VALUES (p_id_empleado, v_titulo, v_mensaje, 'tarea', p_id_actividad);
    
    SELECT LAST_INSERT_ID() as id_notificacion;
END ;;

CREATE PROCEDURE `sp_notificar_ausencia_empleado`(
    IN p_id_supervisor INT,
    IN p_nombre_empleado VARCHAR(100),
    IN p_fecha DATE
)
BEGIN
    DECLARE v_titulo VARCHAR(100);
    DECLARE v_mensaje TEXT;
    
    SET v_titulo = 'Empleado sin registro de entrada';
    SET v_mensaje = CONCAT('El empleado ', p_nombre_empleado, ' no ha registrado su entrada el ', DATE_FORMAT(p_fecha, '%d/%m/%Y'));
    
    INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, id_referencia) 
    VALUES (p_id_supervisor, v_titulo, v_mensaje, 'asistencia', NULL);
    
    SELECT LAST_INSERT_ID() as id_notificacion;
END ;;

CREATE PROCEDURE `sp_notificar_tarea_vencimiento`(
    IN p_id_empleado INT,
    IN p_id_actividad INT,
    IN p_nombre_tarea VARCHAR(100),
    IN p_dias_restantes INT
)
BEGIN
    DECLARE v_titulo VARCHAR(100);
    DECLARE v_mensaje TEXT;
    
    SET v_titulo = 'Tarea próxima a vencer';
    
    IF p_dias_restantes = 0 THEN
        SET v_mensaje = CONCAT('La tarea "', p_nombre_tarea, '" vence hoy');
    ELSE
        SET v_mensaje = CONCAT('La tarea "', p_nombre_tarea, '" vence en ', p_dias_restantes, ' día(s)');
    END IF;
    
    INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo, id_referencia) 
    VALUES (p_id_empleado, v_titulo, v_mensaje, 'tarea', p_id_actividad);
    
    SELECT LAST_INSERT_ID() as id_notificacion;
END ;;

CREATE PROCEDURE `sp_obtener_actividades_recientes`(
    IN p_id_usuario INT,
    IN p_limite INT
)
BEGIN
    SELECT 
        a.id_actividad,
        a.titulo,
        a.descripcion,
        a.fecha_creacion,
        a.fecha_limite,
        a.prioridad,
        a.estado,
        p.nombre as proyecto_nombre,
        p.id_proyecto,
        u.nombre_completo as responsable_nombre,
        CASE 
            WHEN a.fecha_limite < NOW() AND a.estado NOT IN ('completada', 'cancelada') THEN 'vencida'
            WHEN a.fecha_limite <= DATE_ADD(NOW(), INTERVAL 3 DAY) AND a.estado NOT IN ('completada', 'cancelada') THEN 'proxima_vencer'
            ELSE 'normal'
        END as urgencia,
        DATEDIFF(a.fecha_limite, NOW()) as dias_restantes
    FROM actividades a
    INNER JOIN proyectos p ON a.id_proyecto = p.id_proyecto
    LEFT JOIN usuarios u ON p.id_responsable = u.id_usuario
    WHERE a.id_asignado = p_id_usuario
    ORDER BY 
        CASE a.estado 
            WHEN 'en_progreso' THEN 1
            WHEN 'pendiente' THEN 2
            WHEN 'en_revision' THEN 3
            WHEN 'completada' THEN 4
            WHEN 'cancelada' THEN 5
        END,
        CASE a.prioridad 
            WHEN 'urgente' THEN 1
            WHEN 'alta' THEN 2
            WHEN 'media' THEN 3
            WHEN 'baja' THEN 4
        END,
        a.fecha_limite ASC
    LIMIT p_limite;
END ;;

CREATE PROCEDURE `sp_obtener_actividades_usuario`(
    IN p_id_usuario INT,
    IN p_limite INT
)
BEGIN
    SELECT 
        a.id_actividad,
        a.titulo,
        a.descripcion,
        a.fecha_creacion,
        a.fecha_limite,
        a.prioridad,
        a.estado,
        p.nombre as proyecto_nombre,
        p.id_proyecto,
        DATEDIFF(a.fecha_limite, NOW()) as dias_restantes
    FROM actividades a
    INNER JOIN proyectos p ON a.id_proyecto = p.id_proyecto
    WHERE a.id_asignado = p_id_usuario
    AND a.estado NOT IN ('completada', 'cancelada')
    ORDER BY 
        CASE a.prioridad 
            WHEN 'urgente' THEN 1
            WHEN 'alta' THEN 2
            WHEN 'media' THEN 3
            WHEN 'baja' THEN 4
        END,
        a.fecha_limite ASC
    LIMIT p_limite;
END ;;

CREATE PROCEDURE `sp_obtener_comparativa_productividad`(
    IN p_id_usuario INT,
    IN p_dias_atras INT
)
BEGIN
    DECLARE v_fecha_inicio_actual DATE;
    DECLARE v_fecha_fin_actual DATE;
    DECLARE v_fecha_inicio_anterior DATE;
    DECLARE v_fecha_fin_anterior DATE;
    
    SET v_fecha_fin_actual = CURDATE();
    SET v_fecha_inicio_actual = DATE_SUB(CURDATE(), INTERVAL p_dias_atras DAY);
    SET v_fecha_fin_anterior = DATE_SUB(v_fecha_inicio_actual, INTERVAL 1 DAY);
    SET v_fecha_inicio_anterior = DATE_SUB(v_fecha_fin_anterior, INTERVAL p_dias_atras DAY);
    
    SELECT 
        'actual' as periodo,
        SEC_TO_TIME(COALESCE(SUM(tiempo_segundos), 0)) AS tiempo_total,
        ROUND(
            COALESCE(
                SUM(CASE WHEN categoria = 'productiva' THEN tiempo_segundos ELSE 0 END) / 
                GREATEST(SUM(tiempo_segundos), 1) * 100,
                0
            ), 
            2
        ) AS porcentaje_productivo,
        COUNT(DISTINCT DATE(fecha_hora_inicio)) AS dias_trabajados,
        COUNT(DISTINCT nombre_app) AS apps_distintas
    FROM actividad_apps
    WHERE id_usuario = p_id_usuario
    AND DATE(fecha_hora_inicio) BETWEEN v_fecha_inicio_actual AND v_fecha_fin_actual
    
    UNION ALL
    
    SELECT 
        'anterior' as periodo,
        SEC_TO_TIME(COALESCE(SUM(tiempo_segundos), 0)) AS tiempo_total,
        ROUND(
            COALESCE(
                SUM(CASE WHEN categoria = 'productiva' THEN tiempo_segundos ELSE 0 END) / 
                GREATEST(SUM(tiempo_segundos), 1) * 100,
                0
            ), 
            2
        ) AS porcentaje_productivo,
        COUNT(DISTINCT DATE(fecha_hora_inicio)) AS dias_trabajados,
        COUNT(DISTINCT nombre_app) AS apps_distintas
    FROM actividad_apps
    WHERE id_usuario = p_id_usuario
    AND DATE(fecha_hora_inicio) BETWEEN v_fecha_inicio_anterior AND v_fecha_fin_anterior;
END ;;

CREATE PROCEDURE `sp_obtener_distribucion_tiempo`(
    IN p_id_usuario INT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    SELECT 
        COALESCE(categoria, 'neutral') AS categoria,
        SEC_TO_TIME(SUM(tiempo_segundos)) AS tiempo_total,
        ROUND(
            SUM(tiempo_segundos) / 
            GREATEST(
                (SELECT SUM(tiempo_segundos) 
                 FROM actividad_apps 
                 WHERE id_usuario = p_id_usuario
                 AND DATE(fecha_hora_inicio) BETWEEN p_fecha_inicio AND p_fecha_fin), 
                1
            ) * 100, 
            2
        ) AS porcentaje
    FROM actividad_apps
    WHERE id_usuario = p_id_usuario
    AND DATE(fecha_hora_inicio) BETWEEN p_fecha_inicio AND p_fecha_fin
    GROUP BY categoria
    ORDER BY SUM(tiempo_segundos) DESC;
END ;;

CREATE PROCEDURE `sp_obtener_empleados_disponibles`(
    IN p_supervisor_id INT,
    IN p_area VARCHAR(50)
)
BEGIN
    SELECT 
        id_usuario,
        nombre_usuario,
        nombre_completo,
        area,
        telefono,
        fecha_creacion,
        ultimo_acceso,
        estado
    FROM usuarios
    WHERE rol = 'empleado'
    AND estado = 'activo'
    AND (supervisor_id IS NULL OR supervisor_id = p_supervisor_id)
    AND (p_area IS NULL OR area = p_area)
    ORDER BY nombre_completo;
END ;;

CREATE PROCEDURE `sp_obtener_empleados_supervisor`(
    IN p_supervisor_id INT
)
BEGIN
    DECLARE fecha_inicio_mes DATE DEFAULT DATE_FORMAT(NOW(), '%Y-%m-01');
    DECLARE fecha_fin_mes DATE DEFAULT LAST_DAY(NOW());
    
    SELECT 
        u.id_usuario,
        u.nombre_usuario,
        u.nombre_completo,
        u.area,
        u.telefono,
        u.fecha_creacion,
        u.ultimo_acceso,
        u.estado,
        
        COALESCE(
            (SELECT SEC_TO_TIME(SUM(aa.tiempo_segundos))
             FROM actividad_apps aa
             WHERE aa.id_usuario = u.id_usuario
             AND DATE(aa.fecha_hora_inicio) BETWEEN fecha_inicio_mes AND fecha_fin_mes), 
            '00:00:00'
        ) AS tiempo_total_mes,
        
        COALESCE(
            (SELECT COUNT(DISTINCT DATE(aa.fecha_hora_inicio))
             FROM actividad_apps aa
             WHERE aa.id_usuario = u.id_usuario
             AND DATE(aa.fecha_hora_inicio) BETWEEN fecha_inicio_mes AND fecha_fin_mes),
            0
        ) AS dias_activos_mes
        
    FROM usuarios u
    WHERE u.supervisor_id = p_supervisor_id
    AND u.rol = 'empleado'
    AND u.estado = 'activo'
    ORDER BY u.nombre_completo;
END ;;

CREATE PROCEDURE `sp_obtener_estadisticas_semana`(
    IN p_id_usuario INT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    DECLARE v_tiempo_total_segundos INT DEFAULT 0;
    DECLARE v_dias_trabajados INT DEFAULT 0;
    DECLARE v_tiempo_productivo_segundos INT DEFAULT 0;
    DECLARE v_productividad DECIMAL(5,2) DEFAULT 0;
    DECLARE v_promedio_dia VARCHAR(20) DEFAULT '0h 0m';

    SELECT COALESCE(SUM(tiempo_segundos), 0) INTO v_tiempo_total_segundos
    FROM actividad_apps
    WHERE id_usuario = p_id_usuario
    AND DATE(fecha_hora_inicio) BETWEEN p_fecha_inicio AND p_fecha_fin;

    SELECT COUNT(DISTINCT DATE(fecha_hora_inicio)) INTO v_dias_trabajados
    FROM actividad_apps
    WHERE id_usuario = p_id_usuario
    AND DATE(fecha_hora_inicio) BETWEEN p_fecha_inicio AND p_fecha_fin;

    SELECT COALESCE(SUM(tiempo_segundos), 0) INTO v_tiempo_productivo_segundos
    FROM actividad_apps
    WHERE id_usuario = p_id_usuario
    AND DATE(fecha_hora_inicio) BETWEEN p_fecha_inicio AND p_fecha_fin
    AND categoria = 'productiva';

    IF v_tiempo_total_segundos > 0 THEN
        SET v_productividad = ROUND((v_tiempo_productivo_segundos / v_tiempo_total_segundos) * 100, 2);
    END IF;

    IF v_dias_trabajados > 0 THEN
        SET v_promedio_dia = SEC_TO_TIME(v_tiempo_total_segundos / v_dias_trabajados);
    END IF;

    SELECT 
        SEC_TO_TIME(v_tiempo_total_segundos) AS tiempo_total,
        v_dias_trabajados AS dias_trabajados,
        v_productividad AS productividad,
        v_promedio_dia AS promedio_dia,
        v_tiempo_total_segundos AS tiempo_total_segundos;
END ;;

CREATE PROCEDURE `sp_obtener_historial_asistencia_detallado`(
    IN p_id_usuario INT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE,
    IN p_limite INT
)
BEGIN
    SELECT 
        ra.id_registro,
        ra.tipo,
        ra.fecha_hora,
        ra.latitud,
        ra.longitud,
        ra.direccion,
        ra.dispositivo,
        ra.metodo,
        DATE(ra.fecha_hora) as fecha_dia,
        TIME(ra.fecha_hora) as hora_registro
    FROM registros_asistencia ra
    WHERE ra.id_usuario = p_id_usuario
    AND DATE(ra.fecha_hora) BETWEEN p_fecha_inicio AND p_fecha_fin
    ORDER BY ra.fecha_hora DESC
    LIMIT p_limite;
END ;;

CREATE PROCEDURE `sp_obtener_horarios_productivos`(
    IN p_id_usuario INT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    SELECT 
        HOUR(fecha_hora_inicio) as hora,
        SEC_TO_TIME(SUM(tiempo_segundos)) as tiempo_total,
        ROUND(
            SUM(CASE WHEN categoria = 'productiva' THEN tiempo_segundos ELSE 0 END) / 
            GREATEST(SUM(tiempo_segundos), 1) * 100,
            2
        ) as porcentaje_productivo,
        COUNT(*) as total_actividades,
        CASE 
            WHEN HOUR(fecha_hora_inicio) BETWEEN 6 AND 11 THEN 'Mañana'
            WHEN HOUR(fecha_hora_inicio) BETWEEN 12 AND 17 THEN 'Tarde'
            WHEN HOUR(fecha_hora_inicio) BETWEEN 18 AND 23 THEN 'Noche'
            ELSE 'Madrugada'
        END as periodo_dia
    FROM actividad_apps
    WHERE id_usuario = p_id_usuario
    AND DATE(fecha_hora_inicio) BETWEEN p_fecha_inicio AND p_fecha_fin
    GROUP BY HOUR(fecha_hora_inicio)
    HAVING SUM(tiempo_segundos) > 0
    ORDER BY porcentaje_productivo DESC, SUM(tiempo_segundos) DESC;
END ;;

CREATE PROCEDURE `sp_obtener_notificaciones`(
    IN p_id_usuario INT,
    IN p_solo_no_leidas BOOLEAN,
    IN p_limite INT
)
BEGIN
    DECLARE v_where_clause VARCHAR(50) DEFAULT '';
    
    IF p_solo_no_leidas THEN
        SET v_where_clause = 'AND n.leido = 0';
    END IF;
    
    SET @sql = CONCAT('
        SELECT 
            n.id_notificacion,
            n.titulo,
            n.mensaje,
            n.fecha_envio,
            n.fecha_leido,
            n.leido,
            n.tipo,
            n.id_referencia,
            CASE 
                WHEN n.tipo = "tarea" AND n.id_referencia IS NOT NULL THEN a.titulo
                WHEN n.tipo = "proyecto" AND n.id_referencia IS NOT NULL THEN p.nombre
                ELSE NULL
            END as referencia_nombre,
            TIMESTAMPDIFF(MINUTE, n.fecha_envio, NOW()) as minutos_transcurridos,
            CASE 
                WHEN TIMESTAMPDIFF(MINUTE, n.fecha_envio, NOW()) < 60 THEN 
                    CONCAT(TIMESTAMPDIFF(MINUTE, n.fecha_envio, NOW()), " min")
                WHEN TIMESTAMPDIFF(HOUR, n.fecha_envio, NOW()) < 24 THEN 
                    CONCAT(TIMESTAMPDIFF(HOUR, n.fecha_envio, NOW()), " h")
                ELSE 
                    CONCAT(TIMESTAMPDIFF(DAY, n.fecha_envio, NOW()), " d")
            END as tiempo_relativo
        FROM notificaciones n
        LEFT JOIN actividades a ON n.tipo = "tarea" AND n.id_referencia = a.id_actividad
        LEFT JOIN proyectos p ON n.tipo = "proyecto" AND n.id_referencia = p.id_proyecto
        WHERE n.id_usuario = ? ', v_where_clause, '
        ORDER BY n.fecha_envio DESC
        LIMIT ?'
    );

    PREPARE stmt FROM @sql;

    SET @usuario = p_id_usuario;
    SET @limite = p_limite;

    EXECUTE stmt USING @usuario, @limite;

    DEALLOCATE PREPARE stmt;
END ;;

CREATE PROCEDURE `sp_obtener_notificaciones_usuario`(
    IN p_id_usuario INT,
    IN p_solo_no_leidas BOOLEAN,
    IN p_limite INT
)
BEGIN
    SELECT 
        n.id_notificacion,
        n.titulo,
        n.mensaje,
        n.fecha_envio,
        n.fecha_leido,
        n.leido,
        n.tipo,
        n.id_referencia,
        TIMESTAMPDIFF(MINUTE, n.fecha_envio, NOW()) as minutos_transcurridos,
        CASE 
            WHEN TIMESTAMPDIFF(MINUTE, n.fecha_envio, NOW()) < 60 THEN 
                CONCAT(TIMESTAMPDIFF(MINUTE, n.fecha_envio, NOW()), ' min')
            WHEN TIMESTAMPDIFF(HOUR, n.fecha_envio, NOW()) < 24 THEN 
                CONCAT(TIMESTAMPDIFF(HOUR, n.fecha_envio, NOW()), ' h')
            ELSE 
                CONCAT(TIMESTAMPDIFF(DAY, n.fecha_envio, NOW()), ' d')
        END as tiempo_relativo
    FROM notificaciones n
    WHERE n.id_usuario = p_id_usuario
    AND (p_solo_no_leidas = FALSE OR n.leido = FALSE)
    ORDER BY n.fecha_envio DESC
    LIMIT p_limite;
END ;;

CREATE PROCEDURE `sp_obtener_resumen_asistencia_mensual`(
    IN p_id_usuario INT,
    IN p_año INT,
    IN p_mes INT
)
BEGIN
    DECLARE v_primer_dia DATE;
    DECLARE v_ultimo_dia DATE;
    
    SET v_primer_dia = DATE(CONCAT(p_año, '-', LPAD(p_mes, 2, '0'), '-01'));
    SET v_ultimo_dia = LAST_DAY(v_primer_dia);
    
    SELECT 
        COUNT(DISTINCT DATE(fecha_hora)) as dias_asistidos,
        DAY(v_ultimo_dia) as dias_mes,
        ROUND(COUNT(DISTINCT DATE(fecha_hora)) / DAY(v_ultimo_dia) * 100, 2) as porcentaje_asistencia,
        COUNT(CASE WHEN tipo = 'entrada' THEN 1 END) as total_entradas,
        COUNT(CASE WHEN tipo = 'salida' THEN 1 END) as total_salidas,
        COUNT(CASE WHEN tipo = 'break' THEN 1 END) as total_breaks,
        AVG(
            TIMESTAMPDIFF(MINUTE, 
                MIN(CASE WHEN tipo = 'entrada' THEN fecha_hora END),
                MAX(CASE WHEN tipo = 'salida' THEN fecha_hora END)
            )
        ) as promedio_minutos_diarios,
        SEC_TO_TIME(
            AVG(
                TIMESTAMPDIFF(SECOND, 
                    MIN(CASE WHEN tipo = 'entrada' THEN fecha_hora END),
                    MAX(CASE WHEN tipo = 'salida' THEN fecha_hora END)
                )
            )
        ) as promedio_tiempo_diario
    FROM registros_asistencia
    WHERE id_usuario = p_id_usuario
    AND DATE(fecha_hora) BETWEEN v_primer_dia AND v_ultimo_dia
    GROUP BY id_usuario;
END ;;

CREATE PROCEDURE `sp_obtener_resumen_completo`(
    IN p_id_usuario INT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    SELECT 
        SEC_TO_TIME(COALESCE(SUM(tiempo_segundos), 0)) AS tiempo_total,
        COUNT(DISTINCT DATE(fecha_hora_inicio)) AS dias_trabajados,
        COUNT(DISTINCT nombre_app) AS total_apps,
        COUNT(*) AS total_actividades,
        ROUND(
            COALESCE(
                SUM(CASE WHEN categoria = 'productiva' THEN tiempo_segundos ELSE 0 END) / 
                GREATEST(SUM(tiempo_segundos), 1) * 100,
                0
            ), 
            2
        ) AS porcentaje_productivo,
        ROUND(
            COALESCE(
                SUM(CASE WHEN categoria = 'distractora' THEN tiempo_segundos ELSE 0 END) / 
                GREATEST(SUM(tiempo_segundos), 1) * 100,
                0
            ), 
            2
        ) AS porcentaje_distractora,
        ROUND(
            COALESCE(
                SUM(CASE WHEN categoria = 'neutral' THEN tiempo_segundos ELSE 0 END) / 
                GREATEST(SUM(tiempo_segundos), 1) * 100,
                0
            ), 
            2
        ) AS porcentaje_neutral
    FROM actividad_apps
    WHERE id_usuario = p_id_usuario
    AND DATE(fecha_hora_inicio) BETWEEN p_fecha_inicio AND p_fecha_fin;
END ;;

CREATE PROCEDURE `sp_obtener_resumen_general`(
    IN p_id_usuario INT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    SELECT 
        SEC_TO_TIME(COALESCE(SUM(tiempo_segundos), 0)) AS tiempo_total,
        COUNT(DISTINCT DATE(fecha_hora_inicio)) AS dias_trabajados,
        COUNT(*) AS total_actividades,
        ROUND(
            COALESCE(
                SUM(CASE WHEN categoria = 'productiva' THEN tiempo_segundos ELSE 0 END) / 
                GREATEST(SUM(tiempo_segundos), 1) * 100,
                0
            ), 
            2
        ) AS porcentaje_productivo
    FROM actividad_apps
    WHERE id_usuario = p_id_usuario
    AND DATE(fecha_hora_inicio) BETWEEN p_fecha_inicio AND p_fecha_fin;
END ;;

CREATE PROCEDURE `sp_obtener_tiempo_diario`(
    IN p_id_usuario INT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    SELECT 
        DATE(ra.fecha_hora) as fecha,
        MIN(CASE WHEN ra.tipo = 'entrada' THEN TIME(ra.fecha_hora) END) as hora_entrada,
        MAX(CASE WHEN ra.tipo = 'salida' THEN TIME(ra.fecha_hora) END) as hora_salida,
        COUNT(CASE WHEN ra.tipo = 'break' THEN 1 END) as total_breaks,
        TIMESTAMPDIFF(MINUTE, 
            MIN(CASE WHEN ra.tipo = 'entrada' THEN ra.fecha_hora END),
            MAX(CASE WHEN ra.tipo = 'salida' THEN ra.fecha_hora END)
        ) as minutos_trabajados,
        SEC_TO_TIME(
            TIMESTAMPDIFF(SECOND, 
                MIN(CASE WHEN ra.tipo = 'entrada' THEN ra.fecha_hora END),
                MAX(CASE WHEN ra.tipo = 'salida' THEN ra.fecha_hora END)
            )
        ) as tiempo_total_formato
    FROM registros_asistencia ra
    WHERE ra.id_usuario = p_id_usuario
    AND DATE(ra.fecha_hora) BETWEEN p_fecha_inicio AND p_fecha_fin
    GROUP BY DATE(ra.fecha_hora)
    ORDER BY DATE(ra.fecha_hora) DESC;
END ;;

CREATE PROCEDURE `sp_obtener_top_apps`(
    IN p_id_usuario INT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE,
    IN p_limit INT
)
BEGIN
    SELECT 
        nombre_app AS aplicacion,
        COALESCE(categoria, 'neutral') AS categoria,
        SEC_TO_TIME(SUM(tiempo_segundos)) AS tiempo_total,
        ROUND(SUM(tiempo_segundos) / 3600, 2) AS tiempo_horas,
        COUNT(*) AS frecuencia_uso,
        ROUND(
            SUM(tiempo_segundos) / 
            GREATEST(
                (SELECT SUM(tiempo_segundos) 
                 FROM actividad_apps 
                 WHERE id_usuario = p_id_usuario
                 AND DATE(fecha_hora_inicio) BETWEEN p_fecha_inicio AND p_fecha_fin),
                1
            ) * 100, 
            2
        ) AS porcentaje
    FROM actividad_apps
    WHERE id_usuario = p_id_usuario
    AND DATE(fecha_hora_inicio) BETWEEN p_fecha_inicio AND p_fecha_fin
    GROUP BY nombre_app, categoria
    ORDER BY SUM(tiempo_segundos) DESC
    LIMIT p_limit;
END ;;

CREATE PROCEDURE `sp_obtener_usuarios`(
    IN p_filtro_rol VARCHAR(20),
    IN p_filtro_estado VARCHAR(20),
    IN p_busqueda VARCHAR(100),
    IN p_limite INT,
    IN p_offset INT
)
BEGIN
    DECLARE sql_query TEXT DEFAULT '';
    DECLARE where_clause TEXT DEFAULT '';

    SET where_clause = 'WHERE 1=1';

    IF p_filtro_rol IS NOT NULL AND p_filtro_rol != '' THEN
        SET where_clause = CONCAT(where_clause, ' AND rol = "', p_filtro_rol, '"');
    END IF;

    IF p_filtro_estado IS NOT NULL AND p_filtro_estado != '' THEN
        SET where_clause = CONCAT(where_clause, ' AND estado = "', p_filtro_estado, '"');
    END IF;

    IF p_busqueda IS NOT NULL AND p_busqueda != '' THEN
        SET where_clause = CONCAT(where_clause, ' AND (nombre_usuario LIKE "%', p_busqueda, '%" OR nombre_completo LIKE "%', p_busqueda, '%")');
    END IF;

    SET sql_query = CONCAT(
        'SELECT id_usuario, nombre_usuario, nombre_completo, rol, fecha_creacion, ultimo_acceso, estado, avatar, telefono, area FROM usuarios ',
        where_clause,
        ' ORDER BY fecha_creacion DESC'
    );

    IF p_limite IS NOT NULL AND p_limite > 0 THEN
        SET sql_query = CONCAT(sql_query, ' LIMIT ', p_limite);
        IF p_offset IS NOT NULL AND p_offset > 0 THEN
            SET sql_query = CONCAT(sql_query, ' OFFSET ', p_offset);
        END IF;
    END IF;

    SET @sql = sql_query;
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END ;;

CREATE PROCEDURE `sp_procesar_ausencias`()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_id_empleado INT;
    DECLARE v_nombre_empleado VARCHAR(100);
    DECLARE v_id_supervisor INT;
    DECLARE v_hoy DATE;
    
    DECLARE cur_ausentes CURSOR FOR
        SELECT DISTINCT u.id_usuario, u.nombre_completo, u.supervisor_id
        FROM usuarios u
        LEFT JOIN registros_asistencia r ON u.id_usuario = r.id_usuario 
            AND DATE(r.fecha_hora) = CURDATE() AND r.tipo = 'entrada'
        WHERE u.rol = 'empleado' 
            AND u.estado = 'activo' 
            AND r.id_registro IS NULL
            AND TIME(NOW()) > '09:30:00'
            AND u.supervisor_id IS NOT NULL;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    SET v_hoy = CURDATE();
    
    OPEN cur_ausentes;
    
    read_loop: LOOP
        FETCH cur_ausentes INTO v_id_empleado, v_nombre_empleado, v_id_supervisor;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        CALL sp_notificar_ausencia_empleado(v_id_supervisor, v_nombre_empleado, v_hoy);
    END LOOP;
    
    CLOSE cur_ausentes;
    
    SELECT 'Procesamiento de ausencias completado' as resultado;
END ;;

CREATE PROCEDURE `sp_procesar_tareas_vencimiento`()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_id_actividad INT;
    DECLARE v_id_asignado INT;
    DECLARE v_titulo VARCHAR(100);
    DECLARE v_dias_restantes INT;
    
    DECLARE cur_tareas CURSOR FOR
        SELECT a.id_actividad, a.id_asignado, a.titulo,
               DATEDIFF(a.fecha_limite, NOW()) as dias_restantes
        FROM actividades a
        WHERE a.estado IN ('pendiente', 'en_progreso')
            AND a.fecha_limite IS NOT NULL
            AND a.id_asignado IS NOT NULL
            AND DATEDIFF(a.fecha_limite, NOW()) <= 2
            AND DATEDIFF(a.fecha_limite, NOW()) >= 0;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur_tareas;
    
    read_loop: LOOP
        FETCH cur_tareas INTO v_id_actividad, v_id_asignado, v_titulo, v_dias_restantes;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        IF NOT EXISTS (
            SELECT 1 FROM notificaciones 
            WHERE id_usuario = v_id_asignado 
                AND tipo = 'tarea' 
                AND id_referencia = v_id_actividad
                AND titulo = 'Tarea próxima a vencer'
                AND DATE(fecha_envio) = CURDATE()
        ) THEN
            CALL sp_notificar_tarea_vencimiento(v_id_asignado, v_id_actividad, v_titulo, v_dias_restantes);
        END IF;
    END LOOP;
    
    CLOSE cur_tareas;
    
    SELECT 'Procesamiento de tareas completado' as resultado;
END ;;

CREATE PROCEDURE `sp_productividad_dia`(
    IN p_id_usuario INT,
    IN p_fecha DATE
)
BEGIN
    DECLARE total_tiempo_apps INT DEFAULT 0;
    DECLARE tiempo_productivo INT DEFAULT 0;
    DECLARE porcentaje_productividad DECIMAL(5,2) DEFAULT 0;
    
    SELECT COALESCE(SUM(tiempo_segundos), 0) INTO total_tiempo_apps
    FROM actividad_apps 
    WHERE id_usuario = p_id_usuario 
    AND DATE(fecha_hora_inicio) = p_fecha;
    
    SELECT COALESCE(SUM(tiempo_segundos), 0) INTO tiempo_productivo
    FROM actividad_apps 
    WHERE id_usuario = p_id_usuario 
    AND DATE(fecha_hora_inicio) = p_fecha
    AND categoria = 'productiva';
    
    IF total_tiempo_apps > 0 THEN
        SET porcentaje_productividad = (tiempo_productivo * 100.0) / total_tiempo_apps;
    END IF;
    
    SELECT 
        porcentaje_productividad as porcentaje,
        tiempo_productivo as segundos_productivos,
        total_tiempo_apps as segundos_totales;
END ;;

CREATE PROCEDURE `sp_registrar_actividad_app`(
    IN p_id_usuario INT,
    IN p_nombre_app VARCHAR(255),
    IN p_titulo_ventana TEXT,
    IN p_tiempo_segundos INT,
    IN p_categoria ENUM('productiva','distractora','neutral'),
    IN p_session_id VARCHAR(100)
)
BEGIN
    DECLARE v_id_actividad INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
    
    INSERT INTO actividad_apps (
        id_usuario, 
        nombre_app, 
        titulo_ventana, 
        tiempo_segundos, 
        categoria, 
        session_id
    ) VALUES (
        p_id_usuario, 
        p_nombre_app, 
        p_titulo_ventana, 
        p_tiempo_segundos, 
        p_categoria, 
        p_session_id
    );
    
    SET v_id_actividad = LAST_INSERT_ID();
    
    COMMIT;
    
    SELECT v_id_actividad as id_actividad_creada, 'success' as resultado;
END ;;

CREATE PROCEDURE `sp_remover_empleado_supervisor`(
    IN p_supervisor_id INT,
    IN p_empleado_id INT,
    OUT p_resultado JSON
)
BEGIN
    DECLARE v_empleado_asignado INT DEFAULT 0;
    DECLARE v_empleado_nombre VARCHAR(100) DEFAULT '';
    DECLARE v_error_msg VARCHAR(255) DEFAULT '';

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            v_error_msg = MESSAGE_TEXT;
        SET p_resultado = JSON_OBJECT('success', false, 'error', v_error_msg);
        ROLLBACK;
    END;

    START TRANSACTION;

    SELECT COUNT(*), nombre_completo INTO v_empleado_asignado, v_empleado_nombre
    FROM usuarios 
    WHERE id_usuario = p_empleado_id 
    AND supervisor_id = p_supervisor_id
    AND rol = 'empleado';

    IF v_empleado_asignado = 0 THEN
        SET p_resultado = JSON_OBJECT('success', false, 'error', 'Empleado no está asignado a este supervisor');
        ROLLBACK;
    ELSE
        UPDATE usuarios 
        SET supervisor_id = NULL
        WHERE id_usuario = p_empleado_id;

        INSERT INTO logs_sistema (tipo, modulo, mensaje, id_usuario)
        VALUES ('INFO', 'SUPERVISOR', 
               CONCAT('Empleado ', v_empleado_nombre, ' removido del supervisor ID: ', p_supervisor_id),
               p_supervisor_id);

        SET p_resultado = JSON_OBJECT(
            'success', true, 
            'message', CONCAT('Empleado "', v_empleado_nombre, '" removido del equipo')
        );
        COMMIT;
    END IF;
END ;;

CREATE PROCEDURE `sp_reporte_comparativo_empleados`(
    IN p_supervisor_id INT,
    IN p_empleado_id INT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    IF p_empleado_id IS NULL OR p_empleado_id = 0 THEN
        SELECT 
            u.id_usuario,
            u.nombre_completo,
            u.area,
            COUNT(DISTINCT ra.fecha_hora) AS dias_activos,
            SEC_TO_TIME(SUM(TIME_TO_SEC(a.tiempo_segundos))) AS tiempo_total,
            AVG(CASE WHEN a.categoria = 'productiva' THEN 1 ELSE 0 END) * 100 AS productividad
        FROM usuarios u
        LEFT JOIN registros_asistencia ra ON u.id_usuario = ra.id_usuario 
            AND DATE(ra.fecha_hora) BETWEEN p_fecha_inicio AND p_fecha_fin
        LEFT JOIN actividad_apps a ON u.id_usuario = a.id_usuario 
            AND DATE(a.fecha_hora_inicio) BETWEEN p_fecha_inicio AND p_fecha_fin
        WHERE u.supervisor_id = p_supervisor_id
        GROUP BY u.id_usuario, u.nombre_completo, u.area;
    ELSE
        SELECT 
            u.id_usuario,
            u.nombre_completo,
            u.area,
            DATE(ra.fecha_hora) AS fecha,
            SEC_TO_TIME(SUM(TIME_TO_SEC(a.tiempo_segundos))) AS tiempo_diario,
            AVG(CASE WHEN a.categoria = 'productiva' THEN 1 ELSE 0 END) * 100 AS productividad_diaria
        FROM usuarios u
        LEFT JOIN registros_asistencia ra ON u.id_usuario = ra.id_usuario 
            AND DATE(ra.fecha_hora) BETWEEN p_fecha_inicio AND p_fecha_fin
        LEFT JOIN actividad_apps a ON u.id_usuario = a.id_usuario 
            AND DATE(a.fecha_hora_inicio) BETWEEN p_fecha_inicio AND p_fecha_fin
        WHERE u.id_usuario = p_empleado_id
        GROUP BY DATE(ra.fecha_hora);
    END IF;
END ;;

CREATE PROCEDURE `sp_reporte_comparativo_equipo`(
    IN p_supervisor_id INT,
    IN p_empleado_id INT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    IF p_empleado_id IS NULL OR p_empleado_id = 0 THEN
        SELECT 
            u.id_usuario,
            u.nombre_completo,
            u.area,
            COALESCE(
                TIME_FORMAT(
                    SEC_TO_TIME(SUM(aa.tiempo_segundos)), 
                    '%H:%i:%s'
                ), 
                '00:00:00'
            ) as tiempo_total_mes,
            COUNT(DISTINCT DATE(aa.fecha_hora_inicio)) as dias_activos_mes,
            ROUND(
                COALESCE(
                    (SUM(CASE WHEN aa.categoria = 'productiva' THEN aa.tiempo_segundos ELSE 0 END) / 
                     NULLIF(SUM(aa.tiempo_segundos), 0)) * 100,
                    0
                ), 
                2
            ) as porcentaje_productivo
        FROM usuarios u
        LEFT JOIN actividad_apps aa ON u.id_usuario = aa.id_usuario 
            AND aa.fecha_hora_inicio BETWEEN p_fecha_inicio AND p_fecha_fin
        WHERE u.id_supervisor = p_supervisor_id AND u.estado = 'activo'
        GROUP BY u.id_usuario, u.nombre_completo, u.area
        ORDER BY u.nombre_completo;
    ELSE
        SELECT 
            u.id_usuario,
            u.nombre_completo,
            u.area,
            DATE(aa.fecha_hora_inicio) AS fecha,
            COALESCE(
                TIME_FORMAT(
                    SEC_TO_TIME(SUM(aa.tiempo_segundos)), 
                    '%H:%i:%s'
                ), 
                '00:00:00'
            ) as tiempo_diario,
            ROUND(
                COALESCE(
                    (SUM(CASE WHEN aa.categoria = 'productiva' THEN aa.tiempo_segundos ELSE 0 END) / 
                     NULLIF(SUM(aa.tiempo_segundos), 0)) * 100,
                    0
                ), 
                2
            ) as productividad_diaria
        FROM usuarios u
        LEFT JOIN actividad_apps aa ON u.id_usuario = aa.id_usuario 
            AND aa.fecha_hora_inicio BETWEEN p_fecha_inicio AND p_fecha_fin
        WHERE u.id_usuario = p_empleado_id
        GROUP BY DATE(aa.fecha_hora_inicio)
        ORDER BY fecha;
    END IF;
END ;;

CREATE PROCEDURE `sp_reporte_usuarios`(
    IN p_fecha_desde DATE,
    IN p_fecha_hasta DATE,
    IN p_formato ENUM('json', 'tabla')
)
BEGIN
    DECLARE v_where_clause TEXT DEFAULT '';
    DECLARE v_sql TEXT DEFAULT '';
    
    SET v_where_clause = 'WHERE 1=1';
    
    IF p_fecha_desde IS NOT NULL THEN
        SET v_where_clause = CONCAT(v_where_clause, ' AND DATE(fecha_creacion) >= "', p_fecha_desde, '"');
    END IF;
    
    IF p_fecha_hasta IS NOT NULL THEN
        SET v_where_clause = CONCAT(v_where_clause, ' AND DATE(fecha_creacion) <= "', p_fecha_hasta, '"');
    END IF;
    
    IF p_formato = 'json' THEN
        SET v_sql = CONCAT(
            'SELECT JSON_ARRAYAGG(
                JSON_OBJECT(
                    "id_usuario", id_usuario,
                    "nombre_usuario", nombre_usuario,
                    "nombre_completo", nombre_completo,
                    "rol", rol,
                    "estado", estado,
                    "telefono", telefono,
                    "departamento", departamento,
                    "fecha_creacion", DATE_FORMAT(fecha_creacion, "%d/%m/%Y %H:%i"),
                    "ultimo_acceso", CASE WHEN ultimo_acceso IS NULL THEN "Nunca" ELSE DATE_FORMAT(ultimo_acceso, "%d/%m/%Y %H:%i") END
                )
            ) as reporte_json FROM usuarios ',
            v_where_clause,
            ' ORDER BY fecha_creacion DESC'
        );
    ELSE
        SET v_sql = CONCAT(
            'SELECT id_usuario, nombre_usuario, nombre_completo, rol, estado, telefono, departamento,
                    DATE_FORMAT(fecha_creacion, "%d/%m/%Y %H:%i") as fecha_creacion,
                    CASE WHEN ultimo_acceso IS NULL THEN "Nunca" ELSE DATE_FORMAT(ultimo_acceso, "%d/%m/%Y %H:%i") END as ultimo_acceso
             FROM usuarios ',
            v_where_clause,
            ' ORDER BY fecha_creacion DESC'
        );
    END IF;
    
    SET @sql = v_sql;
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END ;;

CREATE PROCEDURE `sp_resumen_completo_dia`(
    IN p_id_usuario INT,
    IN p_fecha DATE
)
BEGIN
    DECLARE v_segundos_trabajados INT DEFAULT 0;
    DECLARE v_porcentaje_productividad DECIMAL(5,2) DEFAULT 0;
    DECLARE v_aplicaciones_usadas INT DEFAULT 0;
    DECLARE v_total_actividades INT DEFAULT 0;
    DECLARE v_actividades_completadas INT DEFAULT 0;
    DECLARE v_tiempo_productivo INT DEFAULT 0;
    DECLARE v_tiempo_total_apps INT DEFAULT 0;
    
    SELECT 
        COALESCE(
            TIMESTAMPDIFF(SECOND, 
                MIN(CASE WHEN tipo = 'entrada' THEN fecha_hora END),
                COALESCE(MAX(CASE WHEN tipo = 'salida' THEN fecha_hora END), NOW())
            ) - 
            (COUNT(CASE WHEN tipo = 'break' THEN 1 END) * 900),
            0
        )
    INTO v_segundos_trabajados
    FROM registros_asistencia 
    WHERE id_usuario = p_id_usuario 
    AND DATE(fecha_hora) = p_fecha;
    
    SELECT 
        COALESCE(SUM(tiempo_segundos), 0),
        COALESCE(SUM(CASE WHEN categoria = 'productiva' THEN tiempo_segundos ELSE 0 END), 0)
    INTO v_tiempo_total_apps, v_tiempo_productivo
    FROM actividad_apps 
    WHERE id_usuario = p_id_usuario 
    AND DATE(fecha_hora_inicio) = p_fecha;
    
    IF v_tiempo_total_apps > 0 THEN
        SET v_porcentaje_productividad = (v_tiempo_productivo / v_tiempo_total_apps) * 100;
    END IF;
    
    SELECT COUNT(DISTINCT nombre_app)
    INTO v_aplicaciones_usadas
    FROM actividad_apps 
    WHERE id_usuario = p_id_usuario 
    AND DATE(fecha_hora_inicio) = p_fecha;
    
    SELECT 
        COUNT(*),
        COUNT(CASE WHEN estado = 'completada' THEN 1 END)
    INTO v_total_actividades, v_actividades_completadas
    FROM actividades a
    INNER JOIN proyectos p ON a.id_proyecto = p.id_proyecto
    WHERE a.id_asignado = p_id_usuario 
    AND (
        DATE(a.fecha_creacion) = p_fecha OR 
        DATE(a.fecha_limite) = p_fecha OR
        a.estado IN ('en_progreso', 'en_revision')
    );
    
    SELECT 
        v_segundos_trabajados as segundos_trabajados,
        v_porcentaje_productividad as porcentaje_productividad,
        v_aplicaciones_usadas as aplicaciones_usadas,
        v_total_actividades as total_actividades,
        v_actividades_completadas as actividades_completadas;
        
END ;;

CREATE PROCEDURE `sp_tiempo_total_dia`(
    IN p_id_usuario INT,
    IN p_fecha DATE
)
BEGIN
    DECLARE total_segundos INT DEFAULT 0;
    DECLARE tiempo_entrada DATETIME;
    DECLARE tiempo_salida DATETIME;
    DECLARE tiempo_break_inicio DATETIME;
    DECLARE tiempo_break_fin DATETIME;
    DECLARE total_breaks_segundos INT DEFAULT 0;
    DECLARE done INT DEFAULT FALSE;
    DECLARE reg_tipo VARCHAR(20);
    DECLARE reg_fecha DATETIME;

    DECLARE cur CURSOR FOR 
        SELECT tipo, fecha_hora 
        FROM registros_asistencia 
        WHERE id_usuario = p_id_usuario 
        AND DATE(fecha_hora) = p_fecha 
        ORDER BY fecha_hora ASC;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    SET tiempo_entrada = NULL;
    SET tiempo_salida = NULL;
    SET tiempo_break_inicio = NULL;
    SET tiempo_break_fin = NULL;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO reg_tipo, reg_fecha;
        IF done THEN
            LEAVE read_loop;
        END IF;

        CASE reg_tipo
            WHEN 'entrada' THEN
                SET tiempo_entrada = reg_fecha;
            WHEN 'salida' THEN
                SET tiempo_salida = reg_fecha;
            WHEN 'break' THEN
                SET tiempo_break_inicio = reg_fecha;
            WHEN 'fin_break' THEN
                SET tiempo_break_fin = reg_fecha;
                IF tiempo_break_inicio IS NOT NULL THEN
                    SET total_breaks_segundos = total_breaks_segundos + 
                        TIMESTAMPDIFF(SECOND, tiempo_break_inicio, tiempo_break_fin);
                    SET tiempo_break_inicio = NULL;
                END IF;
        END CASE;
    END LOOP;

    CLOSE cur;

    IF tiempo_break_inicio IS NOT NULL THEN
        SET total_breaks_segundos = total_breaks_segundos + 
            TIMESTAMPDIFF(SECOND, tiempo_break_inicio, NOW());
    END IF;

    IF tiempo_entrada IS NOT NULL THEN
        IF tiempo_salida IS NOT NULL THEN
            SET total_segundos = TIMESTAMPDIFF(SECOND, tiempo_entrada, tiempo_salida) - total_breaks_segundos;
        ELSE
            SET total_segundos = TIMESTAMPDIFF(SECOND, tiempo_entrada, NOW()) - total_breaks_segundos;
        END IF;
    END IF;

    IF total_segundos < 0 THEN
        SET total_segundos = 0;
    END IF;

    SELECT total_segundos AS segundos_trabajados;
END ;;

CREATE PROCEDURE `sp_validar_login`(
    IN p_nombre_usuario VARCHAR(50),
    OUT p_resultado JSON
)
BEGIN
    DECLARE v_existe INT DEFAULT 0;
    DECLARE v_error_msg VARCHAR(255) DEFAULT '';

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            v_error_msg = MESSAGE_TEXT;
        SET p_resultado = JSON_OBJECT('success', false, 'error', v_error_msg);
    END;

    SELECT COUNT(*) INTO v_existe
    FROM usuarios 
    WHERE nombre_usuario = p_nombre_usuario AND estado = 'activo';

    IF v_existe = 0 THEN
        SET p_resultado = JSON_OBJECT('success', false, 'error', 'Usuario no encontrado o inactivo');
    ELSE
        SELECT JSON_OBJECT(
            'success', true,
            'data', JSON_OBJECT(
                'id_usuario', id_usuario,
                'nombre_usuario', nombre_usuario,
                'nombre_completo', nombre_completo,
                'contraseña_hash', contraseña_hash,
                'rol', rol,
                'estado', estado
            )
        ) INTO p_resultado
        FROM usuarios 
        WHERE nombre_usuario = p_nombre_usuario AND estado = 'activo';

        UPDATE usuarios 
        SET ultimo_acceso = NOW() 
        WHERE nombre_usuario = p_nombre_usuario;
    END IF;
END ;;
-- NEW
CREATE PROCEDURE `sp_verificar_usuario_supervisor`(
    IN p_id_usuario INT,
    IN p_id_supervisor INT
)
BEGIN
    SELECT 1 
    FROM usuarios 
    WHERE id_usuario = p_id_usuario 
    AND supervisor_id = p_id_supervisor
    AND estado = 'activo';
END ;;
DELIMITER ;