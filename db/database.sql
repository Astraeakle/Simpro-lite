-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS simpro_lite;
USE simpro_lite;

-- Crear tabla `usuarios`
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
  `area` ENUM('Administración','Contabilidad','Ingeniería','Marketing','Proyectos','Ambiental','Derecho') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supervisor_id` int DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `nombre_usuario` (`nombre_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla `actividad_apps`
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
  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla `actividades`
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
  FOREIGN KEY (`id_proyecto`) REFERENCES `proyectos` (`id_proyecto`) ON DELETE CASCADE,
  FOREIGN KEY (`id_asignado`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla `proyectos`
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
  FOREIGN KEY (`id_responsable`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla `configuracion`
CREATE TABLE `configuracion` (
  `id_config` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `editable` tinyint(1) DEFAULT '1',
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_config`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla `logs_sistema`
CREATE TABLE `logs_sistema` (
  `id_log` int NOT NULL AUTO_INCREMENT,
  `fecha_hora` datetime DEFAULT CURRENT_TIMESTAMP,
  `tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modulo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_usuario` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_log`),
  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla `notificaciones`
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
  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla `registros_asistencia`
CREATE TABLE `registros_asistencia` (
  `id_registro` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `tipo` enum('entrada','salida','break','fin_break') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dispositivo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metodo` enum('web','cliente','movil') COLLATE utf8mb4_unicode_ci DEFAULT 'web',
  PRIMARY KEY (`id_registro`),
  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla `sesiones_monitor`
CREATE TABLE `sesiones_monitor` (
  `id_sesion` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `tipo` enum('trabajo','break') COLLATE utf8mb4_unicode_ci DEFAULT 'trabajo',
  PRIMARY KEY (`id_sesion`),
  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla `tokens_auth`
CREATE TABLE `tokens_auth` (
  `id_token` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` datetime NOT NULL,
  `dispositivo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_token`),
  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar datos iniciales en `configuracion`
INSERT INTO configuracion(clave, valor, descripcion) VALUES 
('intervalo_monitor', '10', 'Intervalo de monitoreo en segundos'),
('duracion_minima_actividad', '5', 'Duración mínima para registrar actividad'),
('token_expiration_hours', '12', 'Horas de duración del token'),
('api_url', 'http://localhost/simpro-lite/api/v1', 'URL base de la API'),
('login_url', 'http://localhost/simpro-lite/api/v1/autenticar.php', 'Endpoint para autenticación'),
('activity_url', 'http://localhost/simpro-lite/api/v1/actividad.php', 'Endpoint para registrar actividades'),
('config_url', 'http://localhost/simpro-lite/api/v1/api_config.php' , 'Endpoint para obtener configuración'),
('apps_productivas', '["chrome.exe","firefox.exe","edge.exe","code.exe","vscode.exe","word.exe","excel.exe","powerpoint.exe","outlook.exe","teams.exe","zoom.exe","slack.exe","notepad.exe","sublime_text.exe","pycharm64.exe","atom.exe","idea64.exe","eclipse.exe","netbeans.exe","photoshop.exe","illustrator.exe","indesign.exe","blender.exe","unity.exe"]', 'Lista de aplicaciones consideradas productivas (JSON array)'),
('apps_distractoras', '["steam.exe","epicgameslauncher.exe","discord.exe","spotify.exe","netflix.exe","vlc.exe","tiktok.exe","facebook.exe","twitter.exe","instagram.exe","whatsapp.exe","telegram.exe","skype.exe","youtube.exe","twitch.exe","origin.exe","uplay.exe","battlenet.exe"]', 'Lista de aplicaciones consideradas distractoras (JSON array)'),
('estado_jornada_url', 'http://localhost/simpro-lite/api/v1/estado_jornada.php', 'Endpoint para verificar estado de jornada'),
('verificar_tabla_url', 'http://localhost/simpro-lite/api/v1/verificar_tabla.php', 'Endpoint para verificar estructura en servidor'),
('max_actividades_pendientes', '100', 'Máximo de actividades pendientes de sincronizar'),
('auto_sync_interval', '300', 'Intervalo para sincronización automática en segundos'),
('max_title_length', '255', 'Longitud máxima para títulos de ventana'),
('max_appname_length', '100', 'Longitud máxima para nombres de aplicación'),
('min_sync_duration', '5', 'Duración mínima para sincronizar actividad'),
('sync_retry_attempts', '3', 'Intentos de reintento para sincronización fallida');

-- Crear procedimientos almacenados
DELIMITER ;;

-- Procedimiento para actualizar un usuario
-- Procedimiento para actualizar un usuario
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
            SET v_campo = JSON_UNQUOTE(JSON_EXTRACT(v_keys, CONCAT('$[', v_i, ']')));
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

-- Procedimiento para crear un usuario
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

-- Procedimiento para eliminar un usuario
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

-- Procedimiento para obtener estadísticas de usuarios
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

-- Procedimiento para obtener distribución de tiempo
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


-- Procedimiento para obtener resumen general
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

-- Procedimiento para obtener las aplicaciones más usadas
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

-- Procedimiento para obtener usuarios con filtros
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

-- Procedimiento para validar login
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

DELIMITER ;


DELIMITER ;;

-- Procedimiento para obtener empleados disponibles para asignar a un supervisor
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

-- Procedimiento para obtener empleados asignados a un supervisor
CREATE PROCEDURE `sp_obtener_empleados_supervisor`(
    IN p_supervisor_id INT
)
BEGIN
    SELECT 
        u.id_usuario,
        u.nombre_usuario,
        u.nombre_completo,
        u.area,
        u.telefono,
        u.fecha_creacion,
        u.ultimo_acceso,
        u.estado,
        -- Estadísticas básicas de actividad (últimos 30 días)
        COALESCE(
            (SELECT SEC_TO_TIME(SUM(tiempo_segundos))
             FROM actividad_apps aa
             WHERE aa.id_usuario = u.id_usuario
             AND aa.fecha_hora_inicio >= DATE_SUB(NOW(), INTERVAL 30 DAY)), 
            '00:00:00'
        ) AS tiempo_total_mes,
        COALESCE(
            (SELECT COUNT(DISTINCT DATE(fecha_hora_inicio))
             FROM actividad_apps aa
             WHERE aa.id_usuario = u.id_usuario
             AND aa.fecha_hora_inicio >= DATE_SUB(NOW(), INTERVAL 30 DAY)),
            0
        ) AS dias_activos_mes
    FROM usuarios u
    WHERE u.supervisor_id = p_supervisor_id
    AND u.rol = 'empleado'
    ORDER BY u.nombre_completo;
END ;;

-- Procedimiento para asignar empleado a supervisor
CREATE PROCEDURE `sp_asignar_empleado_supervisor`(
    IN p_supervisor_id INT,
    IN p_empleado_id INT,
    OUT p_resultado JSON
)
BEGIN
    DECLARE v_empleado_existe INT DEFAULT 0;
    DECLARE v_supervisor_existe INT DEFAULT 0;
    DECLARE v_empleado_disponible INT DEFAULT 0;
    DECLARE v_supervisor_rol VARCHAR(20) DEFAULT '';
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

    -- Verificar que el supervisor existe y es supervisor
    SELECT COUNT(*), rol INTO v_supervisor_existe, v_supervisor_rol
    FROM usuarios 
    WHERE id_usuario = p_supervisor_id AND estado = 'activo';

    IF v_supervisor_existe = 0 OR v_supervisor_rol != 'supervisor' THEN
        SET p_resultado = JSON_OBJECT('success', false, 'error', 'Supervisor no válido');
        ROLLBACK;
    ELSE
        -- Verificar que el empleado existe y está disponible
        SELECT COUNT(*), nombre_completo INTO v_empleado_existe, v_empleado_nombre
        FROM usuarios 
        WHERE id_usuario = p_empleado_id 
        AND rol = 'empleado' 
        AND estado = 'activo'
        AND (supervisor_id IS NULL OR supervisor_id = p_supervisor_id);

        IF v_empleado_existe = 0 THEN
            SET p_resultado = JSON_OBJECT('success', false, 'error', 'Empleado no disponible para asignación');
            ROLLBACK;
        ELSE
            -- Asignar empleado al supervisor
            UPDATE usuarios 
            SET supervisor_id = p_supervisor_id
            WHERE id_usuario = p_empleado_id;

            -- Registrar en logs
            INSERT INTO logs_sistema (tipo, modulo, mensaje, id_usuario)
            VALUES ('INFO', 'SUPERVISOR', 
                   CONCAT('Empleado ', v_empleado_nombre, ' asignado al supervisor ID: ', p_supervisor_id),
                   p_supervisor_id);

            SET p_resultado = JSON_OBJECT(
                'success', true, 
                'message', CONCAT('Empleado "', v_empleado_nombre, '" asignado correctamente')
            );
            COMMIT;
        END IF;
    END IF;
END ;;

-- Procedimiento para remover empleado de supervisor
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

    -- Verificar que el empleado está asignado a este supervisor
    SELECT COUNT(*), nombre_completo INTO v_empleado_asignado, v_empleado_nombre
    FROM usuarios 
    WHERE id_usuario = p_empleado_id 
    AND supervisor_id = p_supervisor_id
    AND rol = 'empleado';

    IF v_empleado_asignado = 0 THEN
        SET p_resultado = JSON_OBJECT('success', false, 'error', 'Empleado no está asignado a este supervisor');
        ROLLBACK;
    ELSE
        -- Remover asignación
        UPDATE usuarios 
        SET supervisor_id = NULL
        WHERE id_usuario = p_empleado_id;

        -- Registrar en logs
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

-- Procedimiento para obtener estadísticas del equipo de un supervisor
CREATE PROCEDURE `sp_estadisticas_equipo_supervisor`(
    IN p_supervisor_id INT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    SELECT 
        -- Datos básicos del equipo
        COUNT(u.id_usuario) as total_empleados,
        SUM(CASE WHEN u.estado = 'activo' THEN 1 ELSE 0 END) as empleados_activos,
        
        -- Estadísticas de actividad
        SEC_TO_TIME(COALESCE(SUM(aa.tiempo_total), 0)) as tiempo_total_equipo,
        ROUND(COALESCE(AVG(aa.tiempo_total), 0) / 3600, 2) as promedio_horas_empleado,
        
        -- Productividad
        ROUND(
            COALESCE(
                SUM(CASE WHEN aa.categoria = 'productiva' THEN aa.tiempo_total ELSE 0 END) / 
                GREATEST(SUM(aa.tiempo_total), 1) * 100,
                0
            ), 2
        ) as porcentaje_productivo_equipo,
        
        -- Días trabajados
        COALESCE(MAX(aa.dias_trabajados), 0) as max_dias_trabajados,
        ROUND(COALESCE(AVG(aa.dias_trabajados), 0), 1) as promedio_dias_trabajados
        
    FROM usuarios u
    LEFT JOIN (
        SELECT 
            id_usuario,
            SUM(tiempo_segundos) as tiempo_total,
            COUNT(DISTINCT DATE(fecha_hora_inicio)) as dias_trabajados,
            categoria
        FROM actividad_apps
        WHERE DATE(fecha_hora_inicio) BETWEEN p_fecha_inicio AND p_fecha_fin
        GROUP BY id_usuario, categoria
    ) aa ON u.id_usuario = aa.id_usuario
    WHERE u.supervisor_id = p_supervisor_id
    AND u.rol = 'empleado';
END ;;

-- Procedimiento para crear solicitud de cambio (para empleados de otro area)
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

    -- Obtener información del supervisor y empleado
    SELECT area, nombre_completo INTO v_supervisor_depto, v_supervisor_nombre
    FROM usuarios 
    WHERE id_usuario = p_supervisor_id AND rol = 'supervisor';

    SELECT area, nombre_completo INTO v_empleado_depto, v_empleado_nombre
    FROM usuarios 
    WHERE id_usuario = p_empleado_id AND rol = 'empleado';

    -- Contar administradores para enviar notificaciones
    SELECT COUNT(*) INTO v_admin_count
    FROM usuarios 
    WHERE rol = 'admin' AND estado = 'activo';

    IF v_admin_count = 0 THEN
        SET p_resultado = JSON_OBJECT('success', false, 'error', 'No hay administradores disponibles para procesar la solicitud');
        ROLLBACK;
    ELSE
        -- Crear notificaciones para todos los administradores
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

        -- Registrar en logs
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

DELIMITER ;

-- 22/06/2025

DELIMITER //
-- Procedimiento corregido para obtener empleados asignados a un supervisor
-- Versión simplificada sin referencias a tablas que no existen
DROP PROCEDURE IF EXISTS sp_obtener_empleados_supervisor;
CREATE PROCEDURE sp_obtener_empleados_supervisor(
    IN p_supervisor_id INT
)
BEGIN
    SELECT 
        u.id_usuario,
        u.nombre_usuario,
        u.nombre_completo,
        u.area,
        u.telefono,
        u.fecha_creacion,
        u.ultimo_acceso,
        u.estado,
        '00:00:00' AS tiempo_total_mes,  -- Placeholder hasta tener la tabla correcta
        0 AS dias_activos_mes           -- Placeholder hasta tener la tabla correcta
    FROM usuarios u
    WHERE u.supervisor_id = p_supervisor_id
    AND u.rol = 'empleado'
    AND u.estado = 'activo'
    ORDER BY u.nombre_completo;
END;
//

-- Procedimiento para obtener empleados disponibles
-- Para excluir el rol 'admin' y 'supervisor'
DROP PROCEDURE IF EXISTS sp_obtener_empleados_disponibles;
CREATE PROCEDURE sp_obtener_empleados_disponibles(
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
END;
//

-- Procedimiento para obtener estadísticas del equipo del supervisor
DELIMITER //
CREATE PROCEDURE sp_estadisticas_equipo_supervisor(
    IN p_supervisor_id INT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    SELECT 
        COUNT(u.id_usuario) AS total_empleados,
        COUNT(u.id_usuario) AS empleados_activos,  -- Igual al total por ahora
        '00:00:00' AS tiempo_total_equipo,         -- Placeholder
        0 AS porcentaje_productivo_equipo          -- Placeholder
    FROM usuarios u
    WHERE u.supervisor_id = p_supervisor_id
    AND u.rol = 'empleado'
    AND u.estado = 'activo';
END;
//

DELIMITER ;

DELIMITER ;


















-- Procedimiento corregido para obtener empleados asignados a un supervisor
DELIMITER //

DROP PROCEDURE IF EXISTS sp_obtener_empleados_supervisor;

CREATE PROCEDURE sp_obtener_empleados_supervisor(
    IN p_supervisor_id INT
)
BEGIN
    SELECT 
        u.id_usuario,
        u.nombre_usuario,
        u.nombre_completo,
        u.area,
        u.telefono,
        u.fecha_creacion,
        u.ultimo_acceso,
        u.estado,
        '00:00:00' AS tiempo_total_mes,  -- Placeholder hasta tener la tabla correcta
        0 AS dias_activos_mes           -- Placeholder hasta tener la tabla correcta
    FROM usuarios u
    WHERE u.supervisor_id = p_supervisor_id
    AND u.rol = 'empleado'
    AND u.estado = 'activo'
    ORDER BY u.nombre_completo;
END;
//

-- Procedimiento corregido para obtener empleados disponibles
DELIMITER //

DROP PROCEDURE IF EXISTS sp_obtener_empleados_disponibles;

CREATE PROCEDURE sp_obtener_empleados_disponibles(
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
END;
//

DELIMITER ;
