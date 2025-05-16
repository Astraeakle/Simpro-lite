# File: monitor/app/main.py
import os
import sys
import json
import time
import psutil
import threading
import tkinter as tk
from datetime import datetime
import uuid
import sqlite3
from tkinter import ttk, messagebox

try:
    import win32gui
    import win32process
except ImportError:
    import subprocess
    subprocess.check_call([sys.executable, "-m", "pip", "install", "pywin32"])
    import win32gui
    import win32process


class ProductivityMonitor:
    def __init__(self):
        self.config = self.load_config()
        self.running = False
        self.activities = []
        self.last_activity = None
        self.session_id = str(uuid.uuid4())
        self.setup_db()
        self.create_ui()

    def load_config(self):
        config_path = os.path.join(os.path.dirname(
            os.path.abspath(__file__)), "config", "config.json")
        try:
            with open(config_path, 'r') as f:
                config = json.load(f)
            return config
        except (FileNotFoundError, json.JSONDecodeError):
            # Config por defecto si hay error
            return {"intervalo": 5, "max_reintentos": 3}

    def setup_db(self):
        db_dir = os.path.join(os.path.dirname(
            os.path.abspath(__file__)), "data")
        os.makedirs(db_dir, exist_ok=True)
        self.db_path = os.path.join(db_dir, "activity.db")

        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS activities (
            id INTEGER PRIMARY KEY,
            activity_id TEXT,
            timestamp TEXT,
            duration INTEGER,
            app TEXT,
            title TEXT,
            session_id TEXT
        )
        ''')
        conn.commit()
        conn.close()

    def create_ui(self):
        self.root = tk.Tk()
        self.root.title("Monitor de Productividad")
        self.root.geometry("700x500")
        self.root.resizable(True, True)

        # Frame principal
        main_frame = ttk.Frame(self.root, padding="10")
        main_frame.pack(fill=tk.BOTH, expand=True)

        # Status frame
        status_frame = ttk.LabelFrame(
            main_frame, text="Estado Actual", padding="5")
        status_frame.pack(fill=tk.X, pady=5)

        self.current_app_label = ttk.Label(status_frame, text="App: N/A")
        self.current_app_label.pack(side=tk.LEFT, padx=5)

        self.current_title_label = ttk.Label(status_frame, text="Título: N/A")
        self.current_title_label.pack(side=tk.LEFT, padx=5)

        # Control frame
        control_frame = ttk.Frame(main_frame)
        control_frame.pack(fill=tk.X, pady=5)

        self.start_button = ttk.Button(
            control_frame, text="Iniciar", command=self.start_monitoring)
        self.start_button.pack(side=tk.LEFT, padx=5)

        self.stop_button = ttk.Button(
            control_frame, text="Detener", command=self.stop_monitoring, state=tk.DISABLED)
        self.stop_button.pack(side=tk.LEFT, padx=5)

        self.save_button = ttk.Button(
            control_frame, text="Guardar Datos", command=self.save_data)
        self.save_button.pack(side=tk.LEFT, padx=5)

        # Tabla de actividades
        table_frame = ttk.LabelFrame(
            main_frame, text="Actividades Recientes", padding="5")
        table_frame.pack(fill=tk.BOTH, expand=True, pady=5)

        columns = ('id', 'app', 'title', 'duration')
        self.tree = ttk.Treeview(table_frame, columns=columns, show='headings')

        self.tree.heading('id', text='ID')
        self.tree.heading('app', text='Aplicación')
        self.tree.heading('title', text='Título')
        self.tree.heading('duration', text='Duración (s)')

        self.tree.column('id', width=50)
        self.tree.column('app', width=150)
        self.tree.column('title', width=350)
        self.tree.column('duration', width=100)

        scrollbar = ttk.Scrollbar(
            table_frame, orient=tk.VERTICAL, command=self.tree.yview)
        self.tree.configure(yscrollcommand=scrollbar.set)

        self.tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        scrollbar.pack(side=tk.RIGHT, fill=tk.Y)

        # Status bar
        self.status_var = tk.StringVar(value="Listo")
        status_bar = ttk.Label(
            self.root, textvariable=self.status_var, relief=tk.SUNKEN, anchor=tk.W)
        status_bar.pack(side=tk.BOTTOM, fill=tk.X)

        # Configurar cierre
        self.root.protocol("WM_DELETE_WINDOW", self.on_closing)

    def get_active_window_info(self):
        try:
            hwnd = win32gui.GetForegroundWindow()
            window_title = win32gui.GetWindowText(hwnd)
            _, pid = win32process.GetWindowThreadProcessId(hwnd)

            try:
                process = psutil.Process(pid)
                app_name = process.name()
            except:
                app_name = "unknown.exe"

            return {"app": app_name, "title": window_title}
        except:
            return {"app": "error.exe", "title": ""}

    def update_ui(self, app_info):
        if app_info:
            self.current_app_label.config(text=f"App: {app_info['app']}")
            truncated_title = app_info['title'][:50] + \
                ('...' if len(app_info['title']) > 50 else '')
            self.current_title_label.config(text=f"Título: {truncated_title}")

    def record_activity(self, app_info):
        now = datetime.now()
        activity_id = len(self.activities) + 1
        timestamp = now.isoformat()

        # Calcular duración si hay actividad previa
        duration = 0
        if self.last_activity:
            last_time = datetime.fromisoformat(self.last_activity["timestamp"])
            duration = int((now - last_time).total_seconds())
            self.last_activity["duration"] = duration

        # Crear nueva actividad
        activity = {
            "id": activity_id,
            "timestamp": timestamp,
            "duration": 0,
            "data": app_info
        }

        # Guardar actividad actual como última
        self.last_activity = activity
        self.activities.append(activity)

        # Añadir a la vista si la duración es > 0
        if duration > 0:
            prev_activity = self.activities[-2] if len(
                self.activities) > 1 else None
            if prev_activity:
                self.tree.insert('', 0, values=(
                    prev_activity["id"],
                    prev_activity["data"]["app"],
                    prev_activity["data"]["title"],
                    prev_activity["duration"]
                ))

                # Mantener solo las últimas 100 entradas
                if len(self.tree.get_children()) > 100:
                    last_item = self.tree.get_children()[-1]
                    self.tree.delete(last_item)

    def _monitor_loop(self):
        while self.running:
            try:
                app_info = self.get_active_window_info()
                if app_info:
                    self.root.after(0, self.update_ui, app_info)
                    self.root.after(0, self.record_activity, app_info)
                time.sleep(self.config.get("intervalo", 5))
            except Exception as e:
                self.status_var.set(f"Error: {str(e)}")
                time.sleep(self.config.get("intervalo", 5))

    def start_monitoring(self):
        self.running = True
        self.monitor_thread = threading.Thread(target=self._monitor_loop)
        self.monitor_thread.daemon = True
        self.monitor_thread.start()

        self.start_button.config(state=tk.DISABLED)
        self.stop_button.config(state=tk.NORMAL)
        self.status_var.set("Monitoreando...")

    def stop_monitoring(self):
        self.running = False
        if hasattr(self, 'monitor_thread') and self.monitor_thread.is_alive():
            self.monitor_thread.join(timeout=1.0)

        self.start_button.config(state=tk.NORMAL)
        self.stop_button.config(state=tk.DISABLED)
        self.status_var.set("Detenido")

        # Calcular duración final para última actividad
        if self.last_activity:
            now = datetime.now()
            last_time = datetime.fromisoformat(self.last_activity["timestamp"])
            self.last_activity["duration"] = int(
                (now - last_time).total_seconds())

            # Actualizar UI con última actividad
            self.tree.insert('', 0, values=(
                self.last_activity["id"],
                self.last_activity["data"]["app"],
                self.last_activity["data"]["title"],
                self.last_activity["duration"]
            ))

    def save_data(self):
        try:
            # Guardar en la base de datos
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()

            for activity in self.activities:
                cursor.execute('''
                INSERT INTO activities (activity_id, timestamp, duration, app, title, session_id)
                VALUES (?, ?, ?, ?, ?, ?)
                ''', (
                    str(activity["id"]),
                    activity["timestamp"],
                    activity["duration"],
                    activity["data"]["app"],
                    activity["data"]["title"],
                    self.session_id
                ))

            conn.commit()
            conn.close()

            # También guardar como JSON
            save_dir = os.path.join(os.path.dirname(
                os.path.abspath(__file__)), "data")
            os.makedirs(save_dir, exist_ok=True)

            json_path = os.path.join(
                save_dir, f"activity_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json")
            with open(json_path, 'w') as f:
                json.dump(self.activities, f, indent=2)

            self.status_var.set(f"Datos guardados en: {json_path}")
            messagebox.showinfo(
                "Datos Guardados", f"Se han guardado {len(self.activities)} registros de actividad.")

            # Reiniciar actividades después de guardar
            self.activities = []
            self.last_activity = None

        except Exception as e:
            self.status_var.set(f"Error al guardar datos: {str(e)}")
            messagebox.showerror(
                "Error", f"No se pudieron guardar los datos: {str(e)}")

    def on_closing(self):
        if messagebox.askokcancel("Salir", "¿Desea guardar los datos antes de salir?"):
            self.save_data()
        self.stop_monitoring()
        self.root.destroy()
        sys.exit(0)


def main():
    monitor = ProductivityMonitor()
    monitor.root.mainloop()


if __name__ == "__main__":
    main()
