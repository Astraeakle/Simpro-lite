#!/usr/bin/env python3
# File: monitor/app/main.py
"""
SIMPRO Lite - Advanced Productivity Monitor

Monitoreo detallado de aplicaciones en primer plano con registro y depuración visual
"""

import os
import sys
import json
import time
import psutil
import logging
import threading
import tkinter as tk
from datetime import datetime
from logging.handlers import RotatingFileHandler
import win32gui
import win32process


class ProductivityMonitor:
    def __init__(self, config_path='../config/config.json'):
        """
        Inicializar el monitor con configuración y logging
        """
        # Configurar logging
        self.setup_logging()

        # Cargar configuración
        self.config_path = os.path.abspath(
            os.path.join(os.path.dirname(__file__), config_path))
        self.config = self.load_config()

        # Estado del monitor
        self.running = False
        self.current_session = {
            'start_time': None,
            'current_app': None,
            'last_app': None
        }

        # Cola de errores
        self.error_queue = []

        # Crear ventana de errores y depuración
        self.create_debug_window()

    def setup_logging(self):
        """Configurar logging detallado"""
        log_dir = os.path.abspath(os.path.join(
            os.path.dirname(__file__), '..', 'logs'))
        os.makedirs(log_dir, exist_ok=True)
        log_path = os.path.join(log_dir, 'monitor_debug.log')

        # Configurar logger
        self.logger = logging.getLogger('ProductivityMonitor')
        self.logger.setLevel(logging.DEBUG)

        # Manejador de archivo rotativo
        file_handler = RotatingFileHandler(
            log_path,
            maxBytes=10*1024*1024,  # 10 MB
            backupCount=5
        )
        file_handler.setLevel(logging.DEBUG)

        # Manejador de consola
        console_handler = logging.StreamHandler(sys.stdout)
        console_handler.setLevel(logging.DEBUG)

        # Formato
        formatter = logging.Formatter(
            '%(asctime)s - %(name)s - %(levelname)s - %(message)s'
        )
        file_handler.setFormatter(formatter)
        console_handler.setFormatter(formatter)

        # Añadir manejadores
        self.logger.addHandler(file_handler)
        self.logger.addHandler(console_handler)

    def create_debug_window(self):
        """
        Crear ventana de Tkinter para depuración y seguimiento
        """
        self.root = tk.Tk()
        self.root.title("SIMPRO Monitor - Seguimiento de Aplicaciones")
        self.root.geometry("800x600")

        # Marco principal
        main_frame = tk.Frame(self.root)
        main_frame.pack(padx=10, pady=10, fill=tk.BOTH, expand=True)

        # Títulos
        tk.Label(main_frame, text="Aplicación Actual",
                 font=('Arial', 12, 'bold')).pack()
        self.current_app_label = tk.Label(
            main_frame, text="N/A", font=('Arial', 10))
        self.current_app_label.pack()

        # Lista de aplicaciones detectadas
        tk.Label(main_frame, text="Historial de Aplicaciones",
                 font=('Arial', 12, 'bold')).pack()
        self.apps_listbox = tk.Listbox(
            main_frame,
            width=100,
            height=20,
            bg='white',
            fg='black'
        )
        self.apps_listbox.pack(padx=10, pady=10, fill=tk.BOTH, expand=True)

        # Botones
        button_frame = tk.Frame(main_frame)
        button_frame.pack(pady=5)

        clear_btn = tk.Button(
            button_frame,
            text="Limpiar Historial",
            command=self.clear_app_history
        )
        clear_btn.pack(side=tk.LEFT, padx=5)

        restart_btn = tk.Button(
            button_frame,
            text="Reiniciar Monitoreo",
            command=self.restart_monitoring
        )
        restart_btn.pack(side=tk.LEFT, padx=5)

    def get_active_window_info(self):
        """
        Obtener información detallada de la ventana activa
        """
        try:
            # Obtener el handle de la ventana en primer plano
            hwnd = win32gui.GetForegroundWindow()

            # Obtener el título de la ventana
            window_title = win32gui.GetWindowText(hwnd)

            # Obtener el PID del proceso
            _, pid = win32process.GetWindowThreadProcessId(hwnd)

            # Obtener información del proceso
            try:
                process = psutil.Process(pid)
                app_name = process.name()
                exe_path = process.exe()
            except (psutil.NoSuchProcess, psutil.AccessDenied, psutil.ZombieProcess):
                app_name = "Desconocido"
                exe_path = "N/A"

            # Información detallada
            app_info = {
                'nombre': app_name,
                'titulo_ventana': window_title,
                'pid': pid,
                'ruta_ejecutable': exe_path,
                'timestamp': datetime.now().isoformat()
            }

            return app_info

        except Exception as e:
            self.log_error(f"Error al obtener información de ventana: {e}")
            return None

    def log_error(self, error_message, critical=False):
        """
        Registrar errores y mostrarlos en la ventana de depuración
        """
        if critical:
            self.logger.critical(error_message)
        else:
            self.logger.error(error_message)

        # Añadir a la cola de errores
        self.error_queue.append(f"{datetime.now()}: {error_message}")

    def update_ui(self, app_info):
        """
        Actualizar la interfaz de usuario con información de la aplicación
        """
        if app_info:
            # Actualizar etiqueta de aplicación actual
            current_app_text = f"{app_info['nombre']} - {app_info['titulo_ventana']}"
            self.current_app_label.config(text=current_app_text)

            # Añadir a la lista de aplicaciones
            app_entry = f"{app_info['timestamp']} | {current_app_text}"
            self.apps_listbox.insert(tk.END, app_entry)

            # Mantener solo las últimas 100 entradas
            if self.apps_listbox.size() > 100:
                self.apps_listbox.delete(0)

    def clear_app_history(self):
        """
        Limpiar el historial de aplicaciones
        """
        self.apps_listbox.delete(0, tk.END)
        self.current_app_label.config(text="N/A")

    def start_monitoring(self):
        """
        Iniciar el proceso de monitoreo
        """
        self.running = True
        self.monitor_thread = threading.Thread(target=self._monitor_loop)
        self.monitor_thread.daemon = True
        self.monitor_thread.start()

        # Iniciar bucle de eventos de Tkinter
        self.root.protocol("WM_DELETE_WINDOW", self.stop_monitoring)
        self.root.mainloop()

    def _monitor_loop(self):
        """
        Bucle principal de monitoreo
        """
        while self.running:
            try:
                # Obtener información de la ventana actual
                current_app = self.get_active_window_info()

                if current_app:
                    # Registrar información
                    self.logger.debug(f"Aplicación detectada: {current_app}")

                    # Actualizar UI
                    self.root.after(0, self.update_ui, current_app)

                # Dormir por el intervalo configurado
                time.sleep(self.config.get('intervalo', 5))

            except Exception as e:
                self.log_error(f"Error en el bucle de monitoreo: {e}")
                time.sleep(self.config.get('intervalo', 5))

    def stop_monitoring(self):
        """
        Detener el monitoreo y cerrar la aplicación
        """
        self.running = False
        if hasattr(self, 'monitor_thread'):
            self.monitor_thread.join()
        self.root.quit()

    def restart_monitoring(self):
        """
        Reiniciar el proceso de monitoreo
        """
        self.stop_monitoring()
        self.start_monitoring()

    def load_config(self):
        """
        Cargar configuración con manejo de errores
        """
        try:
            with open(self.config_path, 'r') as f:
                config = json.load(f)

            # Configuraciones predeterminadas
            # Intervalo más corto para mejor seguimiento
            config.setdefault('intervalo', 5)
            config.setdefault('max_reintentos', 3)
            config.setdefault('log_level', 'DEBUG')

            return config
        except FileNotFoundError:
            self.log_error(
                "Archivo de configuración no encontrado", critical=True)
            return {
                'intervalo': 5,
                'max_reintentos': 3,
                'log_level': 'DEBUG'
            }
        except json.JSONDecodeError:
            self.log_error("JSON inválido en la configuración", critical=True)
            return {
                'intervalo': 5,
                'max_reintentos': 3,
                'log_level': 'DEBUG'
            }


def main():
    # Verificar dependencias
    try:
        import win32gui
        import win32process
    except ImportError:
        print("Instalando dependencias necesarias...")
        import subprocess
        subprocess.check_call(
            [sys.executable, "-m", "pip", "install", "pywin32"])

    monitor = ProductivityMonitor()
    monitor.start_monitoring()


if __name__ == "__main__":
    main()
