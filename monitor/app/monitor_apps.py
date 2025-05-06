# File: monitor/app/monitor_apps.py
#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
SIMPRO Lite - Monitor de Aplicaciones
Módulo para capturar información detallada de aplicaciones activas
"""

import os
import time
import psutil
import platform
import logging
from datetime import datetime, timedelta

# Configuración de logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)

logger = logging.getLogger("SIMPRO-MonitorApps")

class MonitorApps:
    def __init__(self):
        """Inicializa el monitor de aplicaciones"""
        self.sistema = platform.system()
        self.apps_productivas = []
        self.apps_distractoras = []
        self.cargar_configuracion_apps()
        
    def cargar_configuracion_apps(self):
        """Carga la configuración de aplicaciones productivas/distractoras"""
        try:
            # Ruta al archivo de configuración
            config_path = os.path.join(os.path.dirname(__file__), '..', 'config', 'apps_config.json')
            
            # Si no existe, crear configuración por defecto
            if not os.path.exists(config_path):
                import json
                config = {
                    "apps_productivas": [
                        "word.exe", "excel.exe", "powerpoint.exe", "outlook.exe",
                        "code.exe", "chrome.exe", "firefox.exe", "safari.exe",
                        "teams.exe", "zoom.exe", "slack.exe", "notepad.exe",
                        "python.exe", "java.exe", "eclipse.exe", "idea64.exe",
                        "adobe.exe", "photoshop.exe", "illustrator.exe",
                        "Word", "Excel", "PowerPoint", "Outlook",
                        "Code", "Chrome", "Firefox", "Safari",
                        "Teams", "Zoom", "Slack", "Terminal"
                    ],
                    "apps_distractoras": [
                        "steam.exe", "epicgameslauncher.exe", "discord.exe",
                        "spotify.exe", "netflix.exe", "vlc.exe", "tiktok.exe",
                        "facebook.exe", "twitter.exe", "instagram.exe",
                        "Steam", "Discord", "Spotify", "Netflix", "TikTok",
                        "Facebook", "Twitter", "Instagram"
                    ]
                }
                
                # Crear directorio si no existe
                os.makedirs(os.path.dirname(config_path), exist_ok=True)
                
                # Guardar configuración por defecto
                with open(config_path, 'w') as f:
                    json.dump(config, f, indent=4)
                
                self.apps_productivas = config["apps_productivas"]
                self.apps_distractoras = config["apps_distractoras"]
                logger.info("Configuración por defecto de apps creada")
            else:
                # Cargar configuración existente
                import json
                with open(config_path, 'r') as f:
                    config = json.load(f)
                
                self.apps_productivas = config.get("apps_productivas", [])
                self.apps_distractoras = config.get("apps_distractoras", [])
                logger.info("Configuración de apps cargada correctamente")
        
        except Exception as e:
            logger.error(f"Error al cargar configuración de apps: {e}")
            # Configuración por defecto en caso de error
            self.apps_productivas = ["word", "excel", "code", "chrome", "firefox"]
            self.apps_distractoras = ["steam", "netflix", "facebook", "twitter"]
    
    def es_app_productiva(self, nombre_app):
        """Determina si una aplicación es productiva según la configuración"""
        nombre_app = nombre_app.lower()
        return any(app.lower() in nombre_app for app in self.apps_productivas)
    
    def es_app_distractora(self, nombre_app):
        """Determina si una aplicación es distractora según la configuración"""
        nombre_app = nombre_app.lower()
        return any(app.lower() in nombre_app for app in self.apps_distractoras)
    
    def get_tiempo_uso(self, inicio, fin=None):
        """Calcula el tiempo de uso entre dos marcas de tiempo"""
        if fin is None:
            fin = datetime.now()
        
        # Si es string, convertir a datetime
        if isinstance(inicio, str):
            inicio = datetime.strptime(inicio, "%Y-%m-%d %H:%M:%S")
        if isinstance(fin, str):
            fin = datetime.strptime(fin, "%Y-%m-%d %H:%M:%S")
        
        diferencia = fin - inicio
        return diferencia
    
    def format_tiempo(self, tiempo):
        """Formatea un objeto timedelta en formato legible"""
        if isinstance(tiempo, timedelta):
            total_segundos = int(tiempo.total_seconds())
            horas = total_segundos // 3600
            minutos = (total_segundos % 3600) // 60
            segundos = total_segundos % 60
            
            if horas > 0:
                return f"{horas}h {minutos}m {segundos}s"
            elif minutos > 0:
                return f"{minutos}m {segundos}s"
            else:
                return f"{segundos}s"
        return tiempo
    
    def obtener_procesos_activos(self):
        """Obtiene todos los procesos activos en el sistema"""
        procesos = []
        
        for proc in psutil.process_iter(['pid', 'name', 'username', 'memory_info', 'cpu_percent']):
            try:
                # Actualizar información de CPU
                proc.cpu_percent(interval=0.1)
                
                # Obtener información de memoria
                mem_info = proc.info['memory_info']
                mem_mb = mem_info.rss / (1024 * 1024) if mem_info else 0
                
                procesos.append({
                    'pid': proc.info['pid'],
                    'nombre': proc.info['name'],
                    'usuario': proc.info['username'],
                    'memoria_mb': round(mem_mb, 2),
                    'cpu_percent': round(proc.info['cpu_percent'], 1)
                })
            except:
                pass
        
        # Ordenar por uso de CPU
        procesos.sort(key=lambda x: x['cpu_percent'], reverse=True)
        return procesos
    
    def obtener_clasificacion_app(self, nombre_app):
        """Clasifica una aplicación como productiva, distractora o neutral"""
        if self.es_app_productiva(nombre_app):
            return "productiva"
        elif self.es_app_distractora(nombre_app):
            return "distractora"
        else:
            return "neutral"
    
    def generar_reporte_uso(self, datos_uso):
        """Genera un reporte de uso de aplicaciones"""
        if not datos_uso:
            return None
        
        tiempo_total = timedelta()
        tiempo_productivo = timedelta()
        tiempo_distractor = timedelta()
        
        apps_por_categoria = {
            "productiva": {},
            "distractora": {},
            "neutral": {}
        }
        
        # Procesar datos de uso
        for app in datos_uso:
            nombre = app['nombre_app']
            duracion = self.get_tiempo_uso(app['fecha_hora_inicio'], app['fecha_hora_fin'])
            categoria = self.obtener_clasificacion_app(nombre)
            
            # Acumular tiempo
            tiempo_total += duracion
            
            if categoria == "productiva":
                tiempo_productivo += duracion
            elif categoria == "distractora":
                tiempo_distractor += duracion
            
            # Acumular por app
            if nombre not in apps_por_categoria[categoria]:
                apps_por_categoria[categoria][nombre] = duracion
            else:
                apps_por_categoria[categoria][nombre] += duracion
        
        # Calcular porcentajes
        if tiempo_total.total_seconds() > 0:
            porcentaje_productivo = (tiempo_productivo.total_seconds() / tiempo_total.total_seconds()) * 100
            porcentaje_distractor = (tiempo_distractor.total_seconds() / tiempo_total.total_seconds()) * 100
            porcentaje_neutral = 100 - porcentaje_productivo - porcentaje_distractor
        else:
            porcentaje_productivo = 0
            porcentaje_distractor = 0
            porcentaje_neutral = 0
        
        # Ordenar apps por tiempo de uso
        top_apps_productivas = sorted(
            apps_por_categoria["productiva"].items(),
            key=lambda x: x[1],
            reverse=True
        )[:5]  # Top 5
        
        top_apps_distractoras = sorted(
            apps_por_categoria["distractora"].items(),
            key=lambda x: x[1],
            reverse=True
        )[:5]  # Top 5
        
        # Preparar resultado
        reporte = {
            "tiempo_total": self.format_tiempo(tiempo_total),
            "tiempo_productivo": self.format_tiempo(tiempo_productivo),
            "tiempo_distractor": self.format_tiempo(tiempo_distractor),
            "porcentaje_productivo": round(porcentaje_productivo, 1),
            "porcentaje_distractor": round(porcentaje_distractor, 1),
            "porcentaje_neutral": round(porcentaje_neutral, 1),
            "top_apps_productivas": [
                {"nombre": nombre, "tiempo": self.format_tiempo(tiempo)}
                for nombre, tiempo in top_apps_productivas
            ],
            "top_apps_distractoras": [
                {"nombre": nombre, "tiempo": self.format_tiempo(tiempo)}
                for nombre, tiempo in top_apps_distractoras
            ]
        }
        
        return reporte

# Para pruebas directas del módulo
if __name__ == "__main__":
    monitor = MonitorApps()
    
    # Obtener procesos activos
    print("=== Procesos Activos ===")
    procesos = monitor.obtener_procesos_activos()
    for i, proc in enumerate(procesos[:10]):  # Mostrar top 10
        print(f"{i+1}. {proc['nombre']} (PID: {proc['pid']}, CPU: {proc['cpu_percent']}%, RAM: {proc['memoria_mb']} MB)")
    
    # Clasificar algunas apps de ejemplo
    apps_ejemplo = ["chrome.exe", "steam.exe", "word.exe", "notepad.exe", "explorer.exe"]
    print("\n=== Clasificación de Apps ===")
    for app in apps_ejemplo:
        categoria = monitor.obtener_clasificacion_app(app)
        print(f"{app}: {categoria}")
    
    # Simular datos de uso para reporte
    print("\n=== Reporte de Ejemplo ===")
    ahora = datetime.now()
    datos_uso = [
        {"nombre_app": "chrome.exe", "fecha_hora_inicio": ahora - timedelta(hours=2), "fecha_hora_fin": ahora - timedelta(hours=1)},
        {"nombre_app": "word.exe", "fecha_hora_inicio": ahora - timedelta(hours=4), "fecha_hora_fin": ahora - timedelta(hours=2)},
        {"nombre_app": "steam.exe", "fecha_hora_inicio": ahora - timedelta(minutes=45), "fecha_hora_fin": ahora - timedelta(minutes=15)},
        {"nombre_app": "excel.exe", "fecha_hora_inicio": ahora - timedelta(minutes=90), "fecha_hora_fin": ahora - timedelta(minutes=45)},
    ]
    
    reporte = monitor.generar_reporte_uso(datos_uso)
    if reporte:
        print(f"Tiempo total: {reporte['tiempo_total']}")
        print(f"Tiempo productivo: {reporte['tiempo_productivo']} ({reporte['porcentaje_productivo']}%)")
        print(f"Tiempo distractor: {reporte['tiempo_distractor']} ({reporte['porcentaje_distractor']}%)")
        
        print("\nTop Apps Productivas:")
        for app in reporte['top_apps_productivas']:
            print(f"- {app['nombre']}: {app['tiempo']}")
        
        print("\nTop Apps Distractoras:")
        for app in reporte['top_apps_distractoras']:
            print(f"- {app['nombre']}: {app['tiempo']}")