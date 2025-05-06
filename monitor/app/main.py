# File: monitor/app/main.py
import psutil
import requests
import time
from datetime import datetime
import json
import hashlib

CONFIG = {
    'api_url': 'http://tuservidor/api/v1/actividad',
    'intervalo': 30,  # segundos
    'max_reintentos': 3
}


def get_active_app_info():
    """Obtiene información de la aplicación activa"""
    for proc in psutil.process_iter(['name', 'pid', 'create_time']):
        try:
            if proc.info['name'] not in ['System Idle', 'svchost.exe']:
                return {
                    'nombre': proc.info['name'],
                    'pid': proc.info['pid'],
                    'inicio': datetime.fromtimestamp(proc.info['create_time']).isoformat()
                }
        except:
            continue
    return None


def enviar_datos(datos):
    """Envía datos al servidor con reintentos"""
    headers = {
        'Content-Type': 'application/json',
        'Authorization': f'Bearer {get_token()}'
    }

    for intento in range(CONFIG['max_reintentos']):
        try:
            response = requests.post(
                CONFIG['api_url'],
                headers=headers,
                data=json.dumps(datos),
                timeout=10
            )
            response.raise_for_status()
            return True
        except Exception as e:
            print(f"Intento {intento+1} fallido: {str(e)}")
            time.sleep(5)

    return False


def get_token():
    """Obtiene token de autenticación (implementar según tu sistema)"""
    # Implementar lógica para obtener/renovar token
    return "token_actual"


def main():
    last_app = None
    start_time = time.time()

    while True:
        current_app = get_active_app_info()

        if current_app and (not last_app or current_app['pid'] != last_app['pid']):
            # Nueva app detectada
            if last_app:
                # Registrar tiempo de la app anterior
                last_app['tiempo_segundos'] = time.time() - start_time
                if not enviar_datos(last_app):
                    print("Error al enviar datos")

            # Actualizar para la nueva app
            last_app = current_app
            start_time = time.time()

        time.sleep(CONFIG['intervalo'])


if __name__ == "__main__":
    main()
