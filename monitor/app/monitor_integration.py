#!/usr/bin/env python
# File: monitor/app/monitor_integration.py
# Propósito: Servir como interfaz entre la aplicación web y el monitor de productividad

import os
import sys
import json
import time
import logging
import argparse
import requests
import subprocess
from pathlib import Path

# Configuración de logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler("monitor_integration.log"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger("SIMPRO-Integration")

# Obtener la ruta base del directorio del monitor
BASE_DIR = Path(__file__).resolve().parent.parent
CONFIG_DIR = BASE_DIR / "config"
CONFIG_FILE = CONFIG_DIR / "config.json"
MAIN_SCRIPT = BASE_DIR / "app" / "main.py"

# Asegurarse de que existe el directorio de configuración
CONFIG_DIR.mkdir(parents=True, exist_ok=True)


def cargar_configuracion():
    """Cargar la configuración desde el archivo config.json"""
    try:
        if CONFIG_FILE.exists():
            with open(CONFIG_FILE, 'r') as f:
                return json.load(f)
        logger.warning(
            f"Archivo de configuración no encontrado: {CONFIG_FILE}")
        return {}
    except Exception as e:
        logger.error(f"Error al cargar configuración: {e}")
        return {}


def guardar_configuracion(config):
    """Guardar la configuración en el archivo config.json"""
    try:
        with open(CONFIG_FILE, 'w') as f:
            json.dump(config, f, indent=4)
        logger.info(f"Configuración guardada en {CONFIG_FILE}")
        return True
    except Exception as e:
        logger.error(f"Error al guardar configuración: {e}")
        return False


def obtener_configuracion_monitor(api_base_url, token):
    """Obtener la configuración del monitor desde la aplicación web"""
    try:
        url = f"{api_base_url}/api/v1/monitor_bridge.php"
        headers = {
            'Content-Type': 'application/json',
            'Authorization': f'Bearer {token}'
        }

        response = requests.get(url, headers=headers, timeout=10)

        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                logger.info(
                    "Configuración obtenida con éxito desde el servidor")
                return data.get('config', {})
            else:
                logger.error(
                    f"Error en la respuesta: {data.get('error', 'Error desconocido')}")
        else:
            logger.error(
                f"Error HTTP: {response.status_code} - {response.text}")

        return None
    except Exception as e:
        logger.error(f"Error al obtener configuración: {e}")
        return None


def registrar_inicio_monitor(api_base_url, token):
    """Notificar al servidor que el monitor ha iniciado"""
    try:
        url = f"{api_base_url}/api/v1/monitor_bridge.php"
        headers = {
            'Content-Type': 'application/json',
            'Authorization': f'Bearer {token}'
        }
        payload = {
            'accion': 'iniciar'
        }

        response = requests.post(url, headers=headers,
                                 json=payload, timeout=10)

        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                logger.info("Inicio del monitor registrado en el servidor")
                return True
            else:
                logger.error(
                    f"Error al registrar inicio: {data.get('error', 'Error desconocido')}")
        else:
            logger.error(
                f"Error HTTP: {response.status_code} - {response.text}")

        return False
    except Exception as e:
        logger.error(f"Error al registrar inicio: {e}")
        return False

def iniciar_monitor(config):
    """Iniciar el script principal del monitor con la configuración actual"""
    try:
        # Verificar que el script principal existe
        if not MAIN_SCRIPT.exists():
            logger.error(
                f"No se encontró el script principal en {MAIN_SCRIPT}")
            return False

        python_exec = sys.executable
        cmd = [python_exec, str(MAIN_SCRIPT)]

        logger.info(f"Iniciando monitor con comando: {' '.join(cmd)}")
        subprocess.Popen(cmd,
                         stdout=subprocess.PIPE,
                         stderr=subprocess.PIPE,
                         start_new_session=True)

        logger.info("Monitor iniciado correctamente")
        return True
    except Exception as e:
        logger.error(f"Error al iniciar monitor: {e}")
        return False


def main():
    """Función principal del script de integración"""
    parser = argparse.ArgumentParser(
        description='Integración del Monitor de Productividad')
    parser.add_argument('--token', required=True,
                        help='Token JWT de autenticación')
    parser.add_argument('--api_base', required=True,
                        help='URL base de la API (ej: http://localhost/simpro-lite)')
    parser.add_argument('--user_id', required=True, help='ID del usuario')

    args = parser.parse_args()
    config_actual = cargar_configuracion()
    nueva_config = obtener_configuracion_monitor(args.api_base, args.token)
    if nueva_config:
        config_actual.update(nueva_config)
        config_actual['user_id'] = args.user_id
        if guardar_configuracion(config_actual):
            if registrar_inicio_monitor(args.api_base, args.token):
                if iniciar_monitor(config_actual):
                    logger.info("Proceso de integración completado con éxito")
                    return 0

    logger.error("No se pudo completar el proceso de integración")
    return 1

if __name__ == "__main__":
    sys.exit(main())
