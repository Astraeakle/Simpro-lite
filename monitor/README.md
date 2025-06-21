# SIMPRO Lite - Cliente de Monitoreo

Este es el cliente de monitoreo para el sistema SIMPRO Lite, desarrollado para registrar la actividad de aplicaciones y gestionar la asistencia de usuarios.

## Requisitos

- Python 3.6 o superior
- Conexión a internet
- Acceso al servidor de SIMPRO Lite

## Dependencias

Instale las dependencias requeridas usando pip:

```bash
pip install -r requirements.txt
```

O instale manualmente los siguientes paquetes:

```bash
pip install psutil requests
```

Dependencias adicionales según el sistema operativo:

**Windows:**

```bash
pip install pywin32
```

**macOS:**
No se requieren dependencias adicionales, pero se utiliza AppleScript para algunas funcionalidades.

**Linux:**
Asegúrese de tener instalados los siguientes paquetes:

```bash
sudo apt-get install wmctrl xdotool
```

## Configuración

Antes de usar la aplicación, configure los ajustes en el rol `administrador`:

- `api_url`: URL base de la API del servidor SIMPRO Lite.
- `usuario_id`: ID del usuario (se configura automáticamente tras la autenticación).
- `token`: Token de autenticación (se configura automáticamente tras la autenticación).
- `intervalo_monitoreo`: Intervalo en segundos para enviar datos de actividad (por defecto 30).
- `registro_automatico`: Si se debe registrar la entrada/salida automáticamente.
- `notificaciones`: Activar o desactivar notificaciones.
- `iniciar_con_sistema`: Iniciar automáticamente con el sistema.
- `minimizar_al_iniciar`: Minimizar la aplicación al iniciar.

## Uso

### Iniciar la aplicación

```bash
python app/main.py
```

En el primer inicio, se solicitará iniciar sesión con sus credenciales de SIMPRO Lite.

### Registrar entrada/salida

Al iniciar la aplicación, se le preguntará si desea registrar su entrada. De manera similar, al cerrar la aplicación (Ctrl+C), se le preguntará si desea registrar su salida.

### Monitoreo automático

Una vez iniciada, la aplicación monitoreará automáticamente las aplicaciones que utiliza y enviará esta información al servidor cada cierto intervalo de tiempo (configurable).

## Características

- Monitoreo de aplicaciones activas
- Registro de tiempo de uso por aplicación
- Registro de entrada/salida
- Clasificación de aplicaciones (productivas/distractoras)
- Generación de reportes de productividad

## Desarrolladores

Para extender la funcionalidad:

1. La lógica principal está en `app/main.py`
2. La captura de aplicaciones está en `app/monitor_apps.py`
3. Configure las aplicaciones productivas/distractoras en el rol `administrador` en Configuración del sistema

## Solución de problemas

Si encuentra problemas:

1. Verifique la conectividad con el servidor
2. Asegúrese de que sus credenciales sean correctas
3. Revise los logs en `simpro_monitor.log`
4. Verifique la configuración en la web/bd

Si el problema persiste, contacte al administrador del sistema.
