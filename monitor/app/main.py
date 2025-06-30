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
from datetime import datetime, timedelta
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
        self.config = {}
        self.base_url = "http://localhost/simpro-lite/api/v1"
        self.login_url = f"{self.base_url}/autenticar.php"
        self.config_url = f"{self.base_url}/api_config.php"
        self.activity_url = f"{self.base_url}/actividad.php"
        self.estado_url = f"{self.base_url}/estado_jornada.php"
        self.running = False
        self.monitoring_active = False
        self.current_activity = None
        self.session_id = str(uuid.uuid4())
        self.token = None
        self.user_data = None
        self.setup_db()
        self.create_ui()
        self.auto_login()

    def setup_db(self):
        db_dir = os.path.join(os.path.dirname(
            os.path.abspath(__file__)), "data")
        os.makedirs(db_dir, exist_ok=True)
        self.db_path = os.path.join(db_dir, "activity.db")

        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()

        cursor.execute("PRAGMA table_info(activities)")
        columns = [row[1] for row in cursor.fetchall()]
        if not columns:
            cursor.execute('''
            CREATE TABLE activities (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                activity_id TEXT UNIQUE,
                timestamp TEXT,
                duration INTEGER DEFAULT 0,
                app TEXT,
                title TEXT,
                session_id TEXT,
                category TEXT DEFAULT 'neutral',
                synced INTEGER DEFAULT 0
            )
            ''')
        else:
            if 'synced' not in columns:
                cursor.execute(
                    'ALTER TABLE activities ADD COLUMN synced INTEGER DEFAULT 0')
            if 'category' not in columns:
                cursor.execute(
                    'ALTER TABLE activities ADD COLUMN category TEXT DEFAULT "neutral"')

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
        self.root = tk.Tk()
        self.root.title("SIMPRO Monitor Lite")
        self.root.geometry("900x500")
        self.root.resizable(True, True)

        main_frame = ttk.Frame(self.root, padding="10")
        main_frame.pack(fill=tk.BOTH, expand=True)

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

        control_frame = ttk.Frame(main_frame)
        control_frame.pack(fill=tk.X, pady=10)

        self.sync_button = ttk.Button(
            control_frame, text="Sincronizar Datos", command=self.sync_data, state=tk.DISABLED)
        self.sync_button.pack(side=tk.LEFT, padx=5)

        self.finalize_button = ttk.Button(
            control_frame, text="Finalizar Sesión", command=self.finalize_session, state=tk.DISABLED)
        self.finalize_button.pack(side=tk.LEFT, padx=5)

        table_frame = ttk.LabelFrame(
            main_frame, text="Actividades Recientes", padding="5")
        table_frame.pack(fill=tk.BOTH, expand=True, pady=5)

        columns = ('fecha', 'app', 'title', 'duration', 'category', 'synced')
        self.tree = ttk.Treeview(
            table_frame, columns=columns, show='headings', height=10)

        self.tree.heading('fecha', text='Fecha')
        self.tree.heading('app', text='Aplicación')
        self.tree.heading('title', text='Título')
        self.tree.heading('duration', text='Duración')
        self.tree.heading('category', text='Categoría')
        self.tree.heading('synced', text='Sincronizado')

        self.tree.column('fecha', width=150)  # Aumentado el ancho para fecha
        self.tree.column('app', width=120)
        self.tree.column('title', width=200)
        self.tree.column('duration', width=80)
        self.tree.column('category', width=100)
        self.tree.column('synced', width=80)

        scrollbar = ttk.Scrollbar(
            table_frame, orient=tk.VERTICAL, command=self.tree.yview)
        self.tree.configure(yscrollcommand=scrollbar.set)

        self.tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        scrollbar.pack(side=tk.RIGHT, fill=tk.Y)

        self.status_var = tk.StringVar(
            value="Listo - Inicie sesión para comenzar")
        status_bar = ttk.Label(
            self.root, textvariable=self.status_var, relief=tk.SUNKEN, anchor=tk.W)
        status_bar.pack(side=tk.BOTTOM, fill=tk.X)

        self.root.protocol("WM_DELETE_WINDOW", self.on_closing)
        self.load_recent_activities()

    def has_valid_config(self):
        """Verifica si la configuración es válida"""
        if not self.config:
            return False

        required_keys = required_keys = [
            'apps_productivas', 'apps_distractoras', 'intervalo']
        for key in required_keys:
            if key not in self.config:
                return False

        return True

    def load_config_from_server(self):
        """Cargar configuración desde el servidor con mejor manejo de errores"""
        if not self.token:
            raise Exception(
                "No hay token disponible para cargar configuración")

        max_retries = 3
        retry_delay = 5  # segundos
        last_error = None

        for attempt in range(1, max_retries + 1):
            try:
                print(
                    f"\n🔧 Intento {attempt} de cargar configuración desde {self.config_url}")
                print(
                    f"🔑 Token usado (primeros 50 chars): {self.token[:50]}...")

                headers = {
                    'Authorization': f'Bearer {self.token}',
                    'Accept': 'application/json'
                }
                print(f"📨 Headers enviados: {headers}")

                response = requests.get(
                    self.config_url,
                    headers=headers,
                    timeout=10
                )

                print(
                    f"📡 Respuesta del servidor - Código: {response.status_code}")
                print(f"📄 Contenido de respuesta: {response.text[:200]}...")

                if response.status_code == 401:
                    error_msg = "Token inválido o expirado"
                    if attempt == max_retries:
                        raise Exception(error_msg)
                    print(f"⚠️ {error_msg} - Reintentando...")
                    time.sleep(retry_delay)
                    continue

                if response.status_code != 200:
                    raise Exception(
                        f"Error HTTP {response.status_code} al cargar configuración")

                data = response.json()
                if not isinstance(data, dict):
                    raise Exception(
                        "Respuesta del servidor no es un JSON válido")

                if not data.get('success'):
                    error_msg = data.get(
                        'error', 'Error desconocido del servidor')
                    raise Exception(
                        f"Error en respuesta del servidor: {error_msg}")

                if 'config' not in data:
                    raise Exception(
                        "No se encontró configuración en la respuesta")

                self.config = data['config']

                if not self.has_valid_config():
                    raise Exception(
                        "Configuración recibida no es válida o está incompleta")

                print("✅ Configuración cargada exitosamente desde el servidor")
                print(
                    f"📊 Apps productivas: {len(self.config.get('apps_productivas', []))}")
                print(
                    f"📊 Apps distractoras: {len(self.config.get('apps_distractoras', []))}")
                return True

            except requests.exceptions.RequestException as e:
                last_error = f"Error de conexión: {str(e)}"
                print(f"⚠️ {last_error} - Reintentando...")
                if attempt == max_retries:
                    break
                time.sleep(retry_delay)
            except Exception as e:
                last_error = str(e)
                print(f"⚠️ {last_error} - Reintentando...")
                if attempt == max_retries:
                    break
                time.sleep(retry_delay)

        raise Exception(
            f"No se pudo cargar la configuración después de {max_retries} intentos. Último error: {last_error}")

    def categorize_app(self, app_info):
        """Categorizar aplicación con mejor manejo de errores"""
        if not self.has_valid_config():
            print("⚠️ No hay configuración válida disponible - Usando categoría neutral")
            return 'neutral'

        app_name = app_info['app'].lower()
        window_title = app_info['title'].lower()

        print(
            f"\n🔍 Categorizando aplicación: {app_name} - Título: {window_title}")

        try:
            # Obtener listas desde configuración
            apps_productivas = [app.lower()
                                for app in self.config.get('apps_productivas', [])]
            apps_distractoras = [app.lower()
                                 for app in self.config.get('apps_distractoras', [])]

            # Buscar coincidencias en nombre de aplicación
            for app_pattern in apps_productivas:
                if app_pattern in app_name or app_name in app_pattern:
                    print(f"  ✅ Coincidencia productiva (app): {app_pattern}")
                    return 'productiva'

            for app_pattern in apps_distractoras:
                if app_pattern in app_name or app_name in app_pattern:
                    print(f"  ❌ Coincidencia distractora (app): {app_pattern}")
                    return 'distractora'

            # Buscar coincidencias en título de ventana
            for app_pattern in apps_productivas:
                if app_pattern in window_title:
                    print(
                        f"  ✅ Coincidencia productiva (título): {app_pattern}")
                    return 'productiva'

            for app_pattern in apps_distractoras:
                if app_pattern in window_title:
                    print(
                        f"  ❌ Coincidencia distractora (título): {app_pattern}")
                    return 'distractora'

            print("  🔄 Sin coincidencias, categoría neutral")
            return 'neutral'

        except Exception as e:
            print(f"⚠️ Error al categorizar aplicación: {e}")
            return 'neutral'

    def get_category_color(self, category):
        """Obtener color según categoría"""
        colors = {
            'productiva': 'green',
            'distractora': 'red',
            'neutral': 'blue'
        }
        return colors.get(category.lower(), 'black')

    def auto_login(self):
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
                print(f"Auto-login exitoso para usuario: {result[0]}")

        except Exception as e:
            print(f"Error en auto_login: {e}")

    def login(self):
        username = self.username_entry.get().strip()
        password = self.password_entry.get().strip()

        if not username or not password:
            messagebox.showerror(
                "Error", "Por favor ingrese usuario y contraseña")
            return

        try:
            print(f"Intentando login en: {self.login_url}")

            response = requests.post(
                self.login_url,
                json={'usuario': username, 'password': password},
                timeout=10
            )

            print(f"Login response status: {response.status_code}")
            print(f"Login response: {response.text}")

            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    self.token = data.get('token')
                    self.user_data = data.get('usuario')

                    if not self.user_data:
                        raise Exception(
                            "Datos de usuario no recibidos del servidor")

                    print(f"🔍 Token completo: {self.token}")
                    print(f"🔍 Datos usuario: {self.user_data}")

                    self.save_credentials()
                    self.login_success()
                    self.start_work_status_monitor()

                    nombre = self.user_data.get('nombre_completo') or username
                    messagebox.showinfo("Éxito", f"Conectado como {nombre}")
                else:
                    error_msg = data.get('error', 'Error de autenticación')
                    messagebox.showerror("Error", error_msg)
            else:
                messagebox.showerror(
                    "Error", f"Error de conexión: {response.status_code}")

        except requests.exceptions.RequestException as e:
            print(f"Error de conexión en login: {e}")
            messagebox.showerror("Error", f"Error de conexión: {str(e)}")
        except Exception as e:
            print(f"Error inesperado en login: {e}")
            messagebox.showerror("Error", f"Error inesperado: {str(e)}")

    def save_credentials(self):
        """Guardar credenciales con manejo de errores"""
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            cursor.execute('DELETE FROM saved_credentials')

            expires_at = int(time.time()) + (12 * 3600)  # 12 horas por defecto
            if self.has_valid_config():
                expires_at = int(time.time()) + \
                    (self.config.get('token_expiration_hours', 12) * 3600)

            cursor.execute('''
                INSERT INTO saved_credentials (username, token, user_data, expires_at)
                VALUES (?, ?, ?, ?)
            ''', (self.username_entry.get(), self.token, json.dumps(self.user_data), expires_at))

            conn.commit()
            conn.close()
        except Exception as e:
            print(f"Error guardando credenciales: {e}")

    def login_success(self):
        """Manejar inicio de sesión exitoso con mejor manejo de errores"""
        try:
            # Intentar cargar configuración
            try:
                if not self.load_config_from_server():
                    raise Exception(
                        "No se pudo cargar la configuración inicial")
            except Exception as e:
                print(f"⚠️ Error cargando configuración: {e}")
                messagebox.showwarning(
                    "Advertencia",
                    f"Inicio de sesión exitoso, pero no se pudo cargar configuración: {str(e)}\n"
                    "Algunas funciones pueden no estar disponibles."
                )
                # No hacemos logout aquí, permitimos continuar con sesión pero sin configuración
                self.config = {}

            # Actualizar UI
            nombre = self.user_data.get(
                'nombre') if self.user_data else self.username_entry.get()
            self.status_label.config(text=f"Conectado: {nombre}")
            self.login_button.config(state=tk.DISABLED)
            self.logout_button.config(state=tk.NORMAL)
            self.sync_button.config(state=tk.NORMAL)
            self.finalize_button.config(state=tk.NORMAL)
            self.username_entry.config(state=tk.DISABLED)
            self.password_entry.config(state=tk.DISABLED)
            self.status_var.set("Conectado - Listo para monitorear")

            # Iniciar temporizador para refrescar configuración
            self.start_config_refresh_timer()

        except Exception as e:
            print(f"Error crítico en login_success: {e}")
            messagebox.showerror(
                "Error", f"No se pudo completar el inicio de sesión: {str(e)}")
            # No hacemos logout automático aquí, dejamos que el usuario decida

    def start_config_refresh_timer(self):
        """Recargar configuración periódicamente con manejo de errores"""
        def refresh_config():
            retry_count = 0
            while self.token:
                try:
                    time.sleep(300)  # Cada 5 minutos
                    if not self.load_config_from_server():
                        retry_count += 1
                        if retry_count > 3:
                            print(
                                "ADVERTENCIA: No se puede cargar configuración después de 3 intentos")
                            retry_count = 0
                            # Esperar 10 minutos antes de reintentar
                            time.sleep(600)
                except Exception as e:
                    print(f"Error al refrescar configuración: {e}")
                    self.status_var.set(
                        f"Error al actualizar configuración: {str(e)}")

        threading.Thread(target=refresh_config, daemon=True).start()

    def logout(self):
        if self.monitoring_active:
            self.stop_monitoring()

        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            cursor.execute('DELETE FROM saved_credentials')
            conn.commit()
            conn.close()
            print("Credenciales eliminadas del almacenamiento local")
        except Exception as e:
            print(f"Error eliminando credenciales: {e}")

        self.token = None
        self.user_data = None
        self.config = {}

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
        if not self.token:
            return
        try:
            response = requests.get(
                self.estado_url,
                headers={'Authorization': f'Bearer {self.token}'},
                timeout=10
            )

            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    estado = data.get('diagnostico', {}).get(
                        'estado_actual', {}).get('calculado', 'sin_iniciar')

                    if estado == 'trabajando':
                        self.work_status_label.config(
                            text="🟢 TRABAJANDO", foreground="green")
                        if not self.monitoring_active:
                            self.start_monitoring()
                            self.status_var.set(
                                "Monitoreando actividad - Jornada activa")

                    elif estado == 'break':
                        self.work_status_label.config(
                            text="🟡 EN BREAK", foreground="orange")
                        if self.monitoring_active:
                            self.stop_monitoring()
                            self.status_var.set("Monitoreo pausado - En break")

                    else:  # sin_iniciar, finalizada
                        self.work_status_label.config(
                            text="🔴 JORNADA NO ACTIVA", foreground="red")
                        if self.monitoring_active:
                            self.stop_monitoring()
                            self.status_var.set(
                                "Monitoreo detenido - Jornada no iniciada")

        except Exception as e:
            print(f"Error verificando estado: {e}")

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

    def format_duration(self, seconds):
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

    def format_datetime(self, timestamp):
        try:
            # Intentar parsear formato ISO
            try:
                dt = datetime.fromisoformat(timestamp.replace('Z', '+00:00'))
                return dt.strftime('%d/%m/%Y %H:%M')
            except ValueError:
                pass

            # Intentar otros formatos comunes
            formats = [
                '%Y-%m-%d %H:%M:%S',
                '%Y-%m-%dT%H:%M:%S',
                '%d/%m/%Y %H:%M:%S'
            ]

            for fmt in formats:
                try:
                    dt = datetime.strptime(timestamp, fmt)
                    return dt.strftime('%d/%m/%Y %H:%M')
                except ValueError:
                    continue

            # Fallback: usar los primeros 16 caracteres
            return timestamp[:16]
        except Exception as e:
            print(f"Error formateando fecha {timestamp}: {e}")
            return timestamp[:16]  # Fallback para formato no reconocido

    def record_activity(self, app_info):
        now = datetime.now()
        current_key = f"{app_info['app']}|{app_info['title']}"

        if (self.current_activity and self.current_activity['key'] == current_key):
            interval = self.config.get(
                'intervalo_monitor', 10) if self.has_valid_config() else 10
            self.current_activity['duration'] += interval
            self.update_current_activity_in_db()
        else:
            if self.current_activity:
                self.finalize_current_activity()

            activity_id = str(uuid.uuid4())
            try:
                category = self.categorize_app(app_info)
            except Exception as e:
                print(f"Error categorizando aplicación: {e}")
                category = 'neutral'

            self.current_activity = {
                'key': current_key,
                'activity_id': activity_id,
                'app': app_info["app"],
                'title': app_info["title"],
                'timestamp': now.isoformat(),
                'duration': self.config.get('intervalo_monitor', 10) if self.has_valid_config() else 10,
                'category': category
            }
            self.save_activity_to_db(self.current_activity)

    def save_activity_to_db(self, activity):
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            cursor.execute('''
            INSERT INTO activities (activity_id, timestamp, duration, app, title, session_id, category, synced)
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
        if not self.current_activity:
            return

        min_duration = self.config.get(
            'duracion_minima_actividad', 5) if self.has_valid_config() else 5
        if self.current_activity["duration"] < min_duration:
            return  # No registrar actividades muy cortas

        category_color = self.get_category_color(
            self.current_activity["category"])

        self.tree.insert('', 0, values=(
            self.format_datetime(self.current_activity["timestamp"]),
            self.current_activity["app"],
            self.current_activity["title"][:40] +
            ('...' if len(self.current_activity["title"]) > 40 else ''),
            self.format_duration(self.current_activity["duration"]),
            self.current_activity["category"],
            "No"
        ), tags=(category_color,))

        self.tree.tag_configure('green', foreground='green')
        self.tree.tag_configure('red', foreground='red')
        self.tree.tag_configure('blue', foreground='blue')

        items = self.tree.get_children()
        if len(items) > 50:
            for item in items[50:]:
                self.tree.delete(item)

    def load_recent_activities(self):
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            cursor.execute('''
                SELECT timestamp, app, title, duration, category, synced
                FROM activities
                ORDER BY id DESC
                LIMIT 30
            ''')

            activities = cursor.fetchall()
            conn.close()

            for activity in activities:
                category_color = self.get_category_color(activity[4])
                self.tree.insert('', tk.END, values=(
                    self.format_datetime(activity[0]),
                    activity[1],
                    activity[2][:40] +
                    ('...' if len(activity[2]) > 40 else ''),
                    self.format_duration(activity[3]),
                    activity[4],
                    "Sí" if activity[5] else "No"
                ), tags=(category_color,))

            self.tree.tag_configure('green', foreground='green')
            self.tree.tag_configure('red', foreground='red')
            self.tree.tag_configure('blue', foreground='blue')

        except Exception as e:
            print(f"Error cargando actividades: {e}")

    def validate_activity_data(self, activity):
        """Validar datos antes de sincronizar"""
        required_fields = ['app', 'title', 'timestamp', 'duration']

        for field_idx, field_name in enumerate([4, 5, 2, 3], 0):
            if not activity[field_idx] or str(activity[field_idx]).strip() == '':
                return False, f"Campo {field_name} vacío"

        if int(activity[3]) <= 0:
            return False, "Duración inválida"

        return True, "OK"

    def sync_data(self):
        if not self.token:
            messagebox.showerror("Error", "No hay sesión activa")
            return

        try:
            print(f"Iniciando sincronización con token: {self.token[:50]}...")

            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()

            cursor.execute('''
                SELECT * FROM activities
                WHERE synced = 0
                AND app IS NOT NULL
                AND app != ''
                AND timestamp IS NOT NULL
            ''')

            activities = cursor.fetchall()

            if not activities:
                messagebox.showinfo(
                    "Información", "No hay datos pendientes de sincronización")
                return

            print(f"URL para sincronización: {self.activity_url}")

            synced_count = 0
            failed_count = 0

            for activity in activities:
                try:
                    valid, reason = self.validate_activity_data(activity)
                    if not valid:
                        failed_count += 1
                        continue

                    if len(activity) < 8:
                        failed_count += 1
                        continue

                    app_name = str(activity[4]).strip() if activity[4] else ''
                    titulo_ventana = str(
                        activity[5]).strip() if activity[5] else ''
                    timestamp = activity[2]
                    duracion = int(activity[3])
                    categoria_raw = activity[7] if len(
                        activity) > 7 else 'neutral'

                    if not app_name:
                        failed_count += 1
                        continue

                    if duracion <= 0:
                        failed_count += 1
                        continue

                    if isinstance(categoria_raw, (int, float)):
                        category_map = {0: 'neutral',
                                        1: 'productiva', 2: 'distractora'}
                        categoria = category_map.get(
                            int(categoria_raw), 'neutral')
                    else:
                        categoria = str(categoria_raw).lower().strip()
                        if categoria not in ['neutral', 'productiva', 'distractora']:
                            categoria = 'neutral'

                    try:
                        if timestamp:
                            dt = None
                            try:
                                dt = datetime.fromisoformat(
                                    timestamp.replace('Z', '+00:00'))
                            except:
                                pass

                            if not dt:
                                try:
                                    dt = datetime.strptime(
                                        timestamp, '%Y-%m-%dT%H:%M:%S')
                                except:
                                    pass

                            if not dt:
                                try:
                                    dt = datetime.strptime(
                                        timestamp[:19], '%Y-%m-%dT%H:%M:%S')
                                except:
                                    pass

                            if not dt:
                                dt = datetime.now()

                            fecha_formatted = dt.strftime('%Y-%m-%d %H:%M:%S')
                        else:
                            fecha_formatted = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

                    except Exception as date_error:
                        fecha_formatted = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

                    app_name_clean = app_name.replace('\x00', '').strip()[:100]
                    titulo_clean = titulo_ventana.replace(
                        '\x00', '').strip()[:255]

                    payload = {
                        'nombre_app': app_name_clean,
                        'titulo_ventana': titulo_clean,
                        'fecha_hora_inicio': fecha_formatted,
                        'tiempo_segundos': duracion,
                        'categoria': categoria
                    }

                    if not payload['nombre_app']:
                        failed_count += 1
                        continue

                    headers = {
                        'Authorization': f'Bearer {self.token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }

                    response = requests.post(
                        self.activity_url,
                        json=payload,
                        headers=headers,
                        timeout=15
                    )

                    if response.status_code == 200:
                        try:
                            data = response.json()
                            if data.get('success') or data.get('status') == 'success':
                                cursor.execute(
                                    'UPDATE activities SET synced = 1 WHERE id = ?',
                                    (activity[0],))
                                synced_count += 1
                            else:
                                failed_count += 1
                        except json.JSONDecodeError:
                            failed_count += 1

                    elif response.status_code == 401:
                        print("Token inválido o expirado")
                        messagebox.showwarning(
                            "Advertencia", "Sesión expirada. Por favor, inicie sesión nuevamente.")
                        self.logout()
                        break

                    elif response.status_code == 400:
                        failed_count += 1
                        try:
                            error_data = response.json()
                            if 'requerido' in error_data.get('error', '').lower() or 'inválido' in error_data.get('error', '').lower():
                                cursor.execute(
                                    'UPDATE activities SET synced = -1 WHERE id = ?',
                                    (activity[0],))
                        except:
                            pass

                    elif response.status_code == 500:
                        failed_count += 1
                    else:
                        failed_count += 1

                except Exception as e:
                    failed_count += 1
                    continue

            conn.commit()
            conn.close()

            self.tree.delete(*self.tree.get_children())
            self.load_recent_activities()

            total_activities = len(activities)
            if synced_count > 0:
                message = f"Sincronización completada:\n"
                message += f"   • Exitosas: {synced_count}/{total_activities}\n"
                if failed_count > 0:
                    message += f"   • Fallidas: {failed_count}\n"
                    message += f"\nRevise los logs de la consola para detalles"
                messagebox.showinfo("Sincronización Exitosa", message)
            else:
                message = f"Sincronización fallida:\n"
                message += f"   • Total intentadas: {total_activities}\n"
                message += f"   • Exitosas: 0\n"
                message += f"   • Fallidas: {failed_count}\n\n"
                message += f"Posibles causas:\n"
                message += f"   • Error 500: Problema en servidor PHP/BD\n"
                message += f"   • Error 400: Datos inválidos\n"
                message += f"   • Error 401: Token expirado\n\n"
                message += f"Revisar logs de servidor PHP para más detalles"
                messagebox.showwarning("Error de Sincronización", message)

        except Exception as e:
            print(f"Error general en sincronización: {e}")
            messagebox.showerror(
                "Error", f"Error crítico en sincronización: {str(e)}")

    def cleanup_old_activities(self):
        """Eliminar actividades antiguas ya sincronizadas"""
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()

            # Eliminar registros sincronizados más antiguos que 30 días
            cutoff_date = (datetime.now() - timedelta(days=30)).isoformat()
            cursor.execute('''
                DELETE FROM activities 
                WHERE synced = 1 AND timestamp < ?
            ''', (cutoff_date,))

            deleted = cursor.rowcount
            conn.commit()
            conn.close()

            if deleted > 0:
                print(f"Limpieza: {deleted} registros antiguos eliminados")

        except Exception as e:
            print(f"Error en limpieza: {e}")

    def finalize_session(self):
        if self.current_activity:
            self.finalize_current_activity()

        if self.monitoring_active:
            self.stop_monitoring()

        messagebox.showinfo(
            "Sesión Finalizada", "Todos los datos han sido guardados localmente.\nSincronice cuando tenga conexión a internet.")
        self.status_var.set("Sesión finalizada - Datos guardados localmente")

    def start_monitoring(self):
        if self.monitoring_active:
            return

        self.monitoring_active = True
        self.running = True

        def monitor_loop():
            while self.running and self.monitoring_active:
                try:
                    app_info = self.get_active_window_info()
                    if app_info:
                        try:
                            category = self.categorize_app(app_info)
                            self.root.after(0, lambda: self.current_app_label.config(
                                text=f"App: {app_info['app']} [{category}]"))
                            self.root.after(
                                0, lambda: self.record_activity(app_info))
                        except Exception as e:
                            print(f"Error categorizando aplicación: {e}")
                            self.root.after(0, lambda: self.current_app_label.config(
                                text=f"App: {app_info['app']} [error]"))

                    interval = self.config.get(
                        'intervalo_monitor', 10) if self.has_valid_config() else 10
                    time.sleep(interval)
                except Exception as e:
                    print(f"Error en monitoreo: {e}")
                    time.sleep(5)

        self.monitor_thread = threading.Thread(
            target=monitor_loop, daemon=True)
        self.monitor_thread.start()

    def stop_monitoring(self):
        self.monitoring_active = False
        self.running = False
        self.current_app_label.config(text="App: Monitoreo pausado")

        if self.current_activity:
            self.finalize_current_activity()

    def on_closing(self):
        if self.monitoring_active:
            self.stop_monitoring()

        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            cursor.execute('SELECT COUNT(*) FROM activities WHERE synced = 0')
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
