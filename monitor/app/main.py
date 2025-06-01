# File: monitor/app/main.py
import os
import sys
import json
import time
import psutil
import threading
import tkinter as tk
import requests
import sqlite3
import uuid
from datetime import datetime
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
        self.monitoring_active = False
        self.current_activity = None
        self.session_id = str(uuid.uuid4())
        self.token = None
        self.user_data = None
        self.activity_start_time = None
        self.setup_db()
        self.create_ui()
        self.auto_login()

    def load_config(self):
        """Cargar configuración desde archivo JSON"""
        config_path = os.path.join(os.path.dirname(
            os.path.abspath(__file__)), "config", "config.json")
        try:
            with open(config_path, 'r') as f:
                config = json.load(f)
            return config
        except (FileNotFoundError, json.JSONDecodeError):
            return {
                "api_url": "http://localhost/simpro-lite/api/v1",
                "intervalo": 1,  # Cada segundo para captura continua
                "apps_productivas": [
                    "chrome.exe", "firefox.exe", "edge.exe", "code.exe", "vscode.exe",
                    "word.exe", "excel.exe", "powerpoint.exe", "outlook.exe", "teams.exe",
                    "zoom.exe", "slack.exe", "notepad.exe", "sublime_text.exe", "pycharm64.exe"
                ],
                "apps_distractoras": [
                    "steam.exe", "discord.exe", "spotify.exe", "netflix.exe",
                    "tiktok.exe", "facebook.exe", "twitter.exe", "instagram.exe"
                ]
            }

    def setup_db(self):
        """Configurar base de datos local con estructura corregida"""
        db_dir = os.path.join(os.path.dirname(
            os.path.abspath(__file__)), "data")
        os.makedirs(db_dir, exist_ok=True)
        self.db_path = os.path.join(db_dir, "activity.db")

        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()

        # Tabla de actividades con estructura simplificada
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS activities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            activity_id TEXT UNIQUE,
            timestamp TEXT,
            duration INTEGER DEFAULT 0,
            app TEXT,
            title TEXT,
            session_id TEXT,
            category TEXT,
            sync_status INTEGER DEFAULT 0
        )
        ''')

        # Tabla de credenciales guardadas
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS saved_credentials (
            id INTEGER PRIMARY KEY,
            username TEXT,
            token TEXT,
            user_data TEXT,
            expires_at INTEGER
        )
        ''')

        conn.commit()
        conn.close()

    def create_ui(self):
        """Crear interfaz de usuario simplificada"""
        self.root = tk.Tk()
        self.root.title("SIMPRO Monitor Lite")
        self.root.geometry("800x500")
        self.root.resizable(True, True)

        # Frame principal
        main_frame = ttk.Frame(self.root, padding="10")
        main_frame.pack(fill=tk.BOTH, expand=True)

        # Frame de login
        self.login_frame = ttk.LabelFrame(
            main_frame, text="Iniciar Sesión", padding="10")
        self.login_frame.pack(fill=tk.X, pady=5)

        ttk.Label(self.login_frame, text="Usuario:").grid(
            row=0, column=0, sticky=tk.W, padx=5)
        self.username_entry = ttk.Entry(self.login_frame, width=15)
        self.username_entry.grid(row=0, column=1, padx=5)

        ttk.Label(self.login_frame, text="Contraseña:").grid(
            row=0, column=2, sticky=tk.W, padx=5)
        self.password_entry = ttk.Entry(self.login_frame, show="*", width=15)
        self.password_entry.grid(row=0, column=3, padx=5)

        self.login_button = ttk.Button(
            self.login_frame, text="Conectar", command=self.login)
        self.login_button.grid(row=0, column=4, padx=5)

        self.logout_button = ttk.Button(
            self.login_frame, text="Desconectar", command=self.logout, state=tk.DISABLED)
        self.logout_button.grid(row=0, column=5, padx=5)

        # Frame de estado
        status_frame = ttk.LabelFrame(
            main_frame, text="Estado Actual", padding="10")
        status_frame.pack(fill=tk.X, pady=5)

        self.status_label = ttk.Label(
            status_frame, text="Estado: Desconectado", font=("Arial", 10, "bold"))
        self.status_label.pack(side=tk.LEFT, padx=10)

        self.work_status_label = ttk.Label(status_frame, text="Jornada: No iniciada", font=(
            "Arial", 10, "bold"), foreground="red")
        self.work_status_label.pack(side=tk.LEFT, padx=20)

        self.current_app_label = ttk.Label(
            status_frame, text="App Actual: N/A")
        self.current_app_label.pack(side=tk.LEFT, padx=20)

        # Frame de control
        control_frame = ttk.Frame(main_frame)
        control_frame.pack(fill=tk.X, pady=10)

        self.sync_button = ttk.Button(
            control_frame, text="Sincronizar Datos", command=self.sync_data, state=tk.DISABLED)
        self.sync_button.pack(side=tk.LEFT, padx=5)

        self.finalize_button = ttk.Button(
            control_frame, text="Finalizar Sesión", command=self.finalize_session, state=tk.DISABLED)
        self.finalize_button.pack(side=tk.LEFT, padx=5)

        # Tabla de actividades recientes
        table_frame = ttk.LabelFrame(
            main_frame, text="Actividades Recientes", padding="5")
        table_frame.pack(fill=tk.BOTH, expand=True, pady=5)

        columns = ('app', 'title', 'duration', 'category', 'sync_status')
        self.tree = ttk.Treeview(
            table_frame, columns=columns, show='headings', height=10)

        self.tree.heading('app', text='Aplicación')
        self.tree.heading('title', text='Título')
        self.tree.heading('duration', text='Duración')
        self.tree.heading('category', text='Categoría')
        self.tree.heading('sync_status', text='Sincronizado')

        self.tree.column('app', width=120)
        self.tree.column('title', width=250)
        self.tree.column('duration', width=80)
        self.tree.column('category', width=100)
        self.tree.column('sync_status', width=80)

        scrollbar = ttk.Scrollbar(
            table_frame, orient=tk.VERTICAL, command=self.tree.yview)
        self.tree.configure(yscrollcommand=scrollbar.set)

        self.tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        scrollbar.pack(side=tk.RIGHT, fill=tk.Y)

        # Status bar
        self.status_var = tk.StringVar(
            value="Listo - Inicie sesión para comenzar")
        status_bar = ttk.Label(
            self.root, textvariable=self.status_var, relief=tk.SUNKEN, anchor=tk.W)
        status_bar.pack(side=tk.BOTTOM, fill=tk.X)

        self.root.protocol("WM_DELETE_WINDOW", self.on_closing)
        self.load_recent_activities()

    def auto_login(self):
        """Intentar login automático si hay credenciales guardadas"""
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            cursor.execute('''
                SELECT username, token, user_data, expires_at 
                FROM saved_credentials 
                WHERE expires_at > ?
            ''', (int(time.time()),))

            result = cursor.fetchone()
            conn.close()

            if result:
                self.token = result[1]
                self.user_data = json.loads(result[2])
                self.username_entry.insert(0, result[0])
                self.login_success()
                self.status_var.set("Sesión restaurada automáticamente")
                self.start_work_status_monitor()

        except Exception as e:
            print(f"Error en auto_login: {e}")

    def login(self):
        """Autenticar usuario"""
        username = self.username_entry.get().strip()
        password = self.password_entry.get().strip()

        if not username or not password:
            messagebox.showerror(
                "Error", "Por favor ingrese usuario y contraseña")
            return

        try:
            response = requests.post(
                f"{self.config['api_url']}/autenticar.php",
                json={'usuario': username, 'password': password},
                timeout=10
            )

            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    self.token = data.get('token')
                    self.user_data = data.get('usuario')
                    self.save_credentials()
                    self.login_success()
                    self.start_work_status_monitor()
                    messagebox.showinfo(
                        "Éxito", f"Conectado como {self.user_data.get('nombre_completo', username)}")
                else:
                    messagebox.showerror("Error", data.get(
                        'error', 'Error de autenticación'))
            else:
                messagebox.showerror(
                    "Error", f"Error de conexión: {response.status_code}")

        except requests.exceptions.RequestException as e:
            messagebox.showerror("Error", f"Error de conexión: {str(e)}")

    def save_credentials(self):
        """Guardar credenciales para recordar sesión"""
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            cursor.execute('DELETE FROM saved_credentials')

            expires_at = int(time.time()) + (7 * 24 * 3600)  # 7 días
            cursor.execute('''
                INSERT INTO saved_credentials (username, token, user_data, expires_at)
                VALUES (?, ?, ?, ?)
            ''', (
                self.username_entry.get(),
                self.token,
                json.dumps(self.user_data),
                expires_at
            ))
            conn.commit()
            conn.close()
        except Exception as e:
            print(f"Error guardando credenciales: {e}")

    def login_success(self):
        """Acciones después de login exitoso"""
        self.status_label.config(
            text=f"Conectado: {self.user_data.get('nombre')}")
        self.login_button.config(state=tk.DISABLED)
        self.logout_button.config(state=tk.NORMAL)
        self.sync_button.config(state=tk.NORMAL)
        self.finalize_button.config(state=tk.NORMAL)

        self.username_entry.config(state=tk.DISABLED)
        self.password_entry.config(state=tk.DISABLED)

    def logout(self):
        """Cerrar sesión"""
        if self.monitoring_active:
            self.stop_monitoring()

        self.token = None
        self.user_data = None

        self.status_label.config(text="Estado: Desconectado")
        self.work_status_label.config(
            text="Jornada: No iniciada", foreground="red")
        self.login_button.config(state=tk.NORMAL)
        self.logout_button.config(state=tk.DISABLED)
        self.sync_button.config(state=tk.DISABLED)
        self.finalize_button.config(state=tk.DISABLED)

        self.username_entry.config(state=tk.NORMAL)
        self.password_entry.config(state=tk.NORMAL)
        self.password_entry.delete(0, tk.END)

        self.status_var.set("Desconectado")

    def start_work_status_monitor(self):
        """Iniciar monitoreo del estado de jornada"""
        def monitor_work_status():
            while self.token:
                try:
                    self.check_work_status()
                    time.sleep(15)
                except Exception as e:
                    print(f"Error en monitoreo de estado: {e}")
                    time.sleep(30)

        threading.Thread(target=monitor_work_status, daemon=True).start()

    def check_work_status(self):
        """Verificar estado de jornada desde la API"""
        if not self.token:
            return

        try:
            response = requests.get(
                f"{self.config['api_url']}/estado_jornada.php",
                headers={'Authorization': f'Bearer {self.token}'},
                timeout=10
            )

            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    estado = data.get('estado', 'sin_iniciar')

                    if estado == 'trabajando':
                        self.work_status_label.config(
                            text="🟢 JORNADA ACTIVA", foreground="green")
                        if not self.monitoring_active:
                            self.start_monitoring()
                            self.status_var.set(
                                "¡Jornada iniciada! - Monitoreando actividad...")
                    elif estado == 'break':
                        self.work_status_label.config(
                            text="🟡 EN BREAK", foreground="orange")
                        if self.monitoring_active:
                            self.stop_monitoring()
                            self.status_var.set("En break - Monitoreo pausado")
                    else:
                        self.work_status_label.config(
                            text="🔴 JORNADA FINALIZADA", foreground="red")
                        if self.monitoring_active:
                            self.stop_monitoring()
                            self.status_var.set(
                                "Jornada finalizada - Monitoreo detenido")

        except Exception as e:
            print(f"Error verificando estado de jornada: {e}")

    def get_active_window_info(self):
        """Obtener información de la ventana activa"""
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

    def classify_app(self, app_name):
        """Clasificar aplicación como productiva, distractora o neutral"""
        app_lower = app_name.lower()

        if any(prod_app.lower() in app_lower for prod_app in self.config.get('apps_productivas', [])):
            return 'productiva'
        elif any(dist_app.lower() in app_lower for dist_app in self.config.get('apps_distractoras', [])):
            return 'distractora'
        else:
            return 'neutral'

    def format_duration(self, seconds):
        """Formatear duración en formato legible"""
        if seconds < 60:
            return f"{seconds}s"
        elif seconds < 3600:
            minutes = seconds // 60
            secs = seconds % 60
            return f"{minutes}m {secs}s"
        else:
            hours = seconds // 3600
            minutes = (seconds % 3600) // 60
            return f"{hours}h {minutes}m"

    def record_activity(self, app_info):
        """Registrar actividad del usuario cada segundo"""
        now = datetime.now()
        current_key = f"{app_info['app']}|{app_info['title']}"

        # Si es la misma actividad, incrementar duración
        if (self.current_activity and
                self.current_activity['key'] == current_key):
            self.current_activity['duration'] += 1
            self.update_current_activity_in_db()
        else:
            # Finalizar actividad anterior si existe
            if self.current_activity:
                self.finalize_current_activity()

            # Crear nueva actividad
            activity_id = str(uuid.uuid4())
            category = self.classify_app(app_info["app"])

            self.current_activity = {
                'key': current_key,
                'activity_id': activity_id,
                'app': app_info["app"],
                'title': app_info["title"],
                'timestamp': now.isoformat(),
                'duration': 1,
                'category': category
            }
            self.save_activity_to_db(self.current_activity)

    def save_activity_to_db(self, activity):
        """Guardar nueva actividad en base de datos"""
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            cursor.execute('''
            INSERT INTO activities (activity_id, timestamp, duration, app, title, session_id, category, sync_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ''', (
                activity['activity_id'],
                activity['timestamp'],
                activity['duration'],
                activity['app'],
                activity['title'],
                self.session_id,
                activity['category'],
                0
            ))
            conn.commit()
            conn.close()
        except Exception as e:
            print(f"Error guardando actividad: {e}")

    def update_current_activity_in_db(self):
        """Actualizar duración de la actividad actual"""
        if not self.current_activity:
            return

        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            cursor.execute('''
            UPDATE activities 
            SET duration = ? 
            WHERE activity_id = ?
            ''', (
                self.current_activity['duration'],
                self.current_activity['activity_id']
            ))
            conn.commit()
            conn.close()
        except Exception as e:
            print(f"Error actualizando actividad: {e}")

    def finalize_current_activity(self):
        """Finalizar actividad actual y actualizar vista"""
        if not self.current_activity or self.current_activity['duration'] < 5:
            return

        # Actualizar vista
        self.tree.insert('', 0, values=(
            self.current_activity["app"],
            self.current_activity["title"][:40] +
            ('...' if len(self.current_activity["title"]) > 40 else ''),
            self.format_duration(self.current_activity["duration"]),
            self.current_activity["category"],
            "No"
        ))

        # Mantener solo las últimas 50 entradas en la vista
        items = self.tree.get_children()
        if len(items) > 50:
            for item in items[50:]:
                self.tree.delete(item)

    def load_recent_activities(self):
        """Cargar actividades recientes en la tabla"""
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            cursor.execute('''
                SELECT app, title, duration, category, sync_status 
                FROM activities 
                ORDER BY id DESC 
                LIMIT 30
            ''')

            activities = cursor.fetchall()
            conn.close()

            for activity in activities:
                self.tree.insert('', tk.END, values=(
                    activity[0],
                    activity[1][:40] +
                    ('...' if len(activity[1]) > 40 else ''),
                    self.format_duration(activity[2]),
                    activity[3],
                    "Sí" if activity[4] else "No"
                ))

        except Exception as e:
            print(f"Error cargando actividades: {e}")

    def sync_data(self):
        """Sincronizar datos con el servidor"""
        if not self.token:
            messagebox.showerror("Error", "No hay sesión activa")
            return

        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            cursor.execute('SELECT * FROM activities WHERE sync_status = 0')
            activities = cursor.fetchall()

            if not activities:
                messagebox.showinfo(
                    "Información", "No hay datos pendientes de sincronización")
                return

            synced_count = 0
            for activity in activities:
                try:
                    response = requests.post(
                        f"{self.config['api_url']}/actividad.php",
                        json={
                            'activity_id': activity[1],
                            'timestamp': activity[2],
                            'duration': activity[3],
                            'app': activity[4],
                            'title': activity[5],
                            'session_id': activity[6],
                            'category': activity[7]
                        },
                        headers={'Authorization': f'Bearer {self.token}'},
                        timeout=10
                    )

                    if response.status_code == 200 and response.json().get('success'):
                        cursor.execute(
                            'UPDATE activities SET sync_status = 1 WHERE id = ?', (activity[0],))
                        synced_count += 1

                except Exception as e:
                    print(f"Error sincronizando actividad {activity[0]}: {e}")
                    continue

            conn.commit()
            conn.close()

            # Refrescar vista
            self.tree.delete(*self.tree.get_children())
            self.load_recent_activities()

            messagebox.showinfo(
                "Sincronización", f"Se sincronizaron {synced_count} de {len(activities)} actividades")

        except Exception as e:
            messagebox.showerror("Error", f"Error en sincronización: {str(e)}")

    def finalize_session(self):
        """Finalizar sesión y guardar todos los datos localmente"""
        if self.current_activity:
            self.finalize_current_activity()

        if self.monitoring_active:
            self.stop_monitoring()

        messagebox.showinfo(
            "Sesión Finalizada", "Todos los datos han sido guardados localmente.\nSincronice cuando tenga conexión a internet.")
        self.status_var.set("Sesión finalizada - Datos guardados localmente")

    def start_monitoring(self):
        """Iniciar monitoreo de actividades"""
        if self.monitoring_active:
            return

        self.monitoring_active = True
        self.running = True

        def monitor_loop():
            while self.running and self.monitoring_active:
                try:
                    app_info = self.get_active_window_info()
                    if app_info:
                        self.root.after(0, lambda: self.current_app_label.config(
                            text=f"App: {app_info['app']}"))
                        self.root.after(
                            0, lambda: self.record_activity(app_info))
                    time.sleep(self.config.get("intervalo", 1))
                except Exception as e:
                    print(f"Error en monitoreo: {e}")
                    time.sleep(5)

        self.monitor_thread = threading.Thread(
            target=monitor_loop, daemon=True)
        self.monitor_thread.start()

    def stop_monitoring(self):
        """Detener monitoreo de actividades"""
        self.monitoring_active = False
        self.running = False
        self.current_app_label.config(text="App: Monitoreo pausado")

        if self.current_activity:
            self.finalize_current_activity()

    def on_closing(self):
        """Manejar cierre de aplicación"""
        if self.monitoring_active:
            self.stop_monitoring()

        # Preguntar si desea sincronizar datos pendientes
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            cursor.execute(
                'SELECT COUNT(*) FROM activities WHERE sync_status = 0')
            pending_count = cursor.fetchone()[0]
            conn.close()

            if pending_count > 0:
                if messagebox.askokcancel("Salir", f"Hay {pending_count} actividades sin sincronizar.\n¿Desea sincronizar antes de salir?"):
                    self.sync_data()
        except:
            pass

        self.root.destroy()


def main():
    monitor = ProductivityMonitor()
    monitor.root.mainloop()


if __name__ == "__main__":
    main()
