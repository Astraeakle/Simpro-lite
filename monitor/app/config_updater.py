# File: monitor/app/config_updater.py
import os
import sys
import json
import argparse
import logging
from datetime import datetime

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger("SIMPRO-Updater")

CONFIG_DIR = os.path.join(os.path.dirname(os.path.dirname(__file__)), 'config')
CONFIG_FILE = os.path.join(CONFIG_DIR, 'config.json')


def guardar_configuracion(config_data):
    """Guarda la configuración en el archivo de configuración"""
    try:
        os.makedirs(CONFIG_DIR, exist_ok=True)

        config_actual = {}
        if os.path.exists(CONFIG_FILE):
            with open(CONFIG_FILE, 'r') as f:
                config_actual = json.load(f)
        config_actual.update(config_data)
        with open(CONFIG_FILE, 'w') as f:
            json.dump(config_actual, f, indent=4)

        logger.info(
            f"Configuración actualizada correctamente en {CONFIG_FILE}")
        return True
    except Exception as e:
        logger.error(f"Error al guardar la configuración: {e}")
        return False

def main():
    parser = argparse.ArgumentParser(
        description='Actualiza la configuración del monitor')
    parser.add_argument('--token', help='Token JWT para autenticación')
    parser.add_argument('--api_url', help='URL de la API')
    parser.add_argument('--login_url', help='URL de login')
    parser.add_argument('--user_id', help='ID del usuario')
    parser.add_argument('--intervalo', type=int,
                        help='Intervalo de monitoreo en segundos')
    parser.add_argument(
        '--config_file', help='Ruta del archivo de configuración JSON')

    args = parser.parse_args()

    config_data = {}

    # Si se proporciona un archivo de configuración
    if args.config_file and os.path.exists(args.config_file):
        try:
            with open(args.config_file, 'r') as f:
                config_data = json.load(f)
            logger.info(f"Configuración cargada desde {args.config_file}")
        except Exception as e:
            logger.error(f"Error al cargar el archivo de configuración: {e}")
            return 1

    # Actualizar con los argumentos individuales
    if args.token:
        config_data['token'] = args.token
    if args.api_url:
        config_data['api_url'] = args.api_url
    if args.login_url:
        config_data['login_url'] = args.login_url
    if args.user_id:
        config_data['user_id'] = args.user_id
    if args.intervalo:
        config_data['intervalo'] = args.intervalo

    # Añadir fecha de actualización
    config_data['ultima_actualizacion'] = datetime.now().isoformat()

    # Guardar la configuración
    if guardar_configuracion(config_data):
        logger.info("Configuración actualizada correctamente")
        return 0
    else:
        logger.error("Error al actualizar la configuración")
        return 1

if __name__ == "__main__":
    sys.exit(main())
