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
        """Cargar configuración local por defecto"""
        config_path = os.path.join(os.path.dirname(
            os.path.abspath(__file__)), "config", "config.json")
        try:
            with open(config_path, 'r') as f:
                config = json.load(f)
            return config
        except (FileNotFoundError, json.JSONDecodeError):
            return {
                "api_url": "http://localhost/simpro-lite/api/v1",
                "login_url": "http://localhost/simpro-lite/api/v1/autenticar.php",
                "activity_url": "http://localhost/simpro-lite/api/v1/actividad.php",
                "config_url": "http://localhost/simpro-lite/api/v1/api_config.php",
                "intervalo": 10,
                "duracion_minima_actividad": 5,
                "apps_productivas": [
                    "chrome.exe", "firefox.exe", "edge.exe", "code.exe", "vscode.exe",
                    "word.exe", "excel.exe", "powerpoint.exe", "outlook.exe", "teams.exe",
                    "zoom.exe", "slack.exe", "notepad.exe", "sublime_text.exe", "pycharm64.exe",
                    "atom.exe", "idea64.exe", "eclipse.exe", "netbeans.exe", "photoshop.exe",
                    "illustrator.exe", "indesign.exe", "blender.exe", "unity.exe"
                ],
                "apps_distractoras": [
                    "steam.exe", "epicgameslauncher.exe", "discord.exe", "spotify.exe",
                    "netflix.exe", "vlc.exe", "tiktok.exe", "facebook.exe", "twitter.exe",
                    "instagram.exe", "whatsapp.exe", "telegram.exe", "skype.exe",
                    "youtube.exe", "twitch.exe", "origin.exe", "uplay.exe", "battlenet.exe"
                ]
            }

    def load_server_config(self):
        """Obtener configuración desde el servidor - FIXED"""
        if not self.token:
            print("No hay token para obtener configuración del servidor")
            return False

        try:
            config_url = self.config.get(
                'config_url', f"{self.config['api_url']}/api_config.php")

            print(f"🔧 Obteniendo configuración desde: {config_url}")

            response = requests.get(
                config_url,
                headers={'Authorization': f'Bearer {self.token}'},
                timeout=10
            )

            print(f"🔧 Status de configuración: {response.status_code}")
            print(f"🔧 Respuesta configuración: {response.text}")

            if response.status_code == 200:
                # Verificar si la respuesta es JSON válido
                response_text = response.text.strip()
                if not response_text:
                    print("⚠️ Respuesta vacía del servidor de configuración")
                    return False

                try:
                    data = response.json()
                except json.JSONDecodeError as e:
                    print(f"⚠️ Error parseando JSON de configuración: {e}")
                    print(f"⚠️ Respuesta recibida: {response_text}")
                    return False

                if data.get('success'):
                    server_config = data.get('config', {})

                    # Actualizar configuración local con la del servidor
                    self.config.update({
                        'intervalo': server_config.get('intervalo', self.config.get('intervalo', 10)),
                        'duracion_minima_actividad': server_config.get('duracion_minima_actividad', 5),
                        'apps_productivas': server_config.get('apps_productivas', self.config.get('apps_productivas', [])),
                        'apps_distractoras': server_config.get('apps_distractoras', self.config.get('apps_distractoras', []))
                    })

                    print(f"✅ Configuración actualizada desde servidor:")
                    print(f"   - Intervalo: {self.config['intervalo']}s")
                    print(
                        f"   - Duración mínima: {self.config['duracion_minima_actividad']}s")
                    print(
                        f"   - Apps productivas: {len(self.config['apps_productivas'])}")
                    print(
                        f"   - Apps distractoras: {len(self.config['apps_distractoras'])}")

                    return True
                else:
                    print(f"❌ Error en respuesta de configuración: {data}")
                    return False
            elif response.status_code == 404:
                print(
                    "⚠️ Endpoint de configuración no encontrado, usando configuración local")
                return False
            else:
                print(
                    f"❌ Error HTTP obteniendo configuración: {response.status_code}")
                return False

        except requests.exceptions.RequestException as e:
            print(f"⚠️ Error de conexión obteniendo configuración: {e}")
            return False
        except Exception as e:
            print(f"❌ Error inesperado obteniendo configuración: {e}")
            return False

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
        self.root.geometry("800x500")
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

        self.test_button = ttk.Button(
            control_frame, text="Probar API", command=self.test_single_activity, state=tk.DISABLED)
        self.test_button.pack(side=tk.LEFT, padx=5)

        table_frame = ttk.LabelFrame(
            main_frame, text="Actividades Recientes", padding="5")
        table_frame.pack(fill=tk.BOTH, expand=True, pady=5)

        columns = ('app', 'title', 'duration', 'category', 'synced')
        self.tree = ttk.Treeview(
            table_frame, columns=columns, show='headings', height=10)

        self.tree.heading('app', text='Aplicación')
        self.tree.heading('title', text='Título')
        self.tree.heading('duration', text='Duración')
        self.tree.heading('category', text='Categoría')
        self.tree.heading('synced', text='Sincronizado')

        self.tree.column('app', width=120)
        self.tree.column('title', width=250)
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

    def verify_server_setup(self):
        """Verificar que el servidor tenga las tablas necesarias"""
        if not self.token:
            return False

        try:
            base_url = self.config.get(
                'api_url', 'http://localhost/simpro-lite/api/v1')
            verify_url = f"{base_url}/verificar_tabla.php"

            response = requests.get(
                verify_url,
                headers={'Authorization': f'Bearer {self.token}'},
                timeout=10
            )

            if response.status_code == 200:
                data = response.json()
                return data.get('tabla_existe', False)
            return False
        except:
            return False

    def login(self):
        username = self.username_entry.get().strip()
        password = self.password_entry.get().strip()

        if not username or not password:
            messagebox.showerror(
                "Error", "Por favor ingrese usuario y contraseña")
            return

        try:
            login_url = self.config.get(
                'login_url', f"{self.config['api_url']}/autenticar.php")
            print(f"Intentando login en: {login_url}")

            response = requests.post(
                login_url,
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
                    print(f"Token recibido: {self.token[:50]}...")
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
            print(f"Error de conexión en login: {e}")
            messagebox.showerror("Error", f"Error de conexión: {str(e)}")

    def save_credentials(self):
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            cursor.execute('DELETE FROM saved_credentials')

            expires_at = int(time.time()) + (7 * 24 * 3600)
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
            print("Credenciales guardadas exitosamente")
        except Exception as e:
            print(f"Error guardando credenciales: {e}")

    def login_success(self):
        """Método actualizado que incluye carga de configuración del servidor"""
        self.status_label.config(
            text=f"Conectado: {self.user_data.get('nombre')}")
        self.login_button.config(state=tk.DISABLED)
        self.logout_button.config(state=tk.NORMAL)
        self.sync_button.config(state=tk.NORMAL)
        self.finalize_button.config(state=tk.NORMAL)
        self.test_button.config(state=tk.NORMAL)

        self.username_entry.config(state=tk.DISABLED)
        self.password_entry.config(state=tk.DISABLED)

        # 🆕 NUEVO: Cargar configuración del servidor después del login
        if self.load_server_config():
            self.status_var.set("Conectado - Configuración sincronizada")
        else:
            self.status_var.set("Conectado - Usando configuración local")

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

        self.status_label.config(text="Estado: Desconectado")
        self.work_status_label.config(
            text="Jornada: No iniciada", foreground="red")
        self.login_button.config(state=tk.NORMAL)
        self.logout_button.config(state=tk.DISABLED)
        self.sync_button.config(state=tk.DISABLED)
        self.finalize_button.config(state=tk.DISABLED)
        self.test_button.config(state=tk.DISABLED)

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
        """FIXED - Leer correctamente el estado desde diagnostico.estado_actual.calculado"""
        if not self.token:
            return

        try:
            base_url = self.config.get(
                'api_url', 'http://localhost/simpro-lite/api/v1')
            if base_url.endswith('/actividad.php'):
                base_url = base_url.replace('/actividad.php', '')

            estado_url = f"{base_url}/estado_jornada.php"
            # print(f"Verificando estado de jornada en: {estado_url}")

            response = requests.get(
                estado_url,
                headers={'Authorization': f'Bearer {self.token}'},
                timeout=10
            )

            # print(f"Estado jornada response: {response.status_code} - {response.text}")

            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    # 🔧 FIX: Leer el estado desde diagnostico.estado_actual.calculado
                    diagnostico = data.get('diagnostico', {})
                    estado_actual = diagnostico.get('estado_actual', {})
                    estado = estado_actual.get('calculado', 'sin_iniciar')

                    print(f"Estado actual de jornada: {estado}")

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
                else:
                    print(f"Error en respuesta estado_jornada: {data}")
            else:
                print(f"Error HTTP en estado_jornada: {response.status_code}")

        except Exception as e:
            print(f"Error verificando estado de jornada: {e}")

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

    def classify_app(self, app_name):
        if not app_name:
            return 'neutral'

        app_lower = app_name.lower()

        for prod_app in self.config.get('apps_productivas', []):
            if prod_app.lower() in app_lower or app_lower in prod_app.lower():
                return 'productiva'

        for dist_app in self.config.get('apps_distractoras', []):
            if dist_app.lower() in app_lower or app_lower in dist_app.lower():
                return 'distractora'

        return 'neutral'

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

    def record_activity(self, app_info):
        """Método actualizado que usa duracion_minima_actividad del servidor"""
        now = datetime.now()
        current_key = f"{app_info['app']}|{app_info['title']}"

        if (self.current_activity and self.current_activity['key'] == current_key):
            self.current_activity['duration'] += self.config.get(
                "intervalo", 10)
            self.update_current_activity_in_db()
        else:
            if self.current_activity:
                self.finalize_current_activity()

            activity_id = str(uuid.uuid4())
            category = self.classify_app(app_info["app"])

            self.current_activity = {
                'key': current_key,
                'activity_id': activity_id,
                'app': app_info["app"],
                'title': app_info["title"],
                'timestamp': now.isoformat(),
                'duration': self.config.get("intervalo", 10),
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
        """Método actualizado que usa duracion_minima_actividad del servidor"""
        if not self.current_activity:
            return

        # 🆕 Usar duracion_minima_actividad del servidor
        duracion_minima = self.config.get('duracion_minima_actividad', 5)

        if self.current_activity['duration'] < duracion_minima:
            return

        self.tree.insert('', 0, values=(
            self.current_activity["app"],
            self.current_activity["title"][:40] +
            ('...' if len(self.current_activity["title"]) > 40 else ''),
            self.format_duration(self.current_activity["duration"]),
            self.current_activity["category"],
            "No"
        ))

        items = self.tree.get_children()
        if len(items) > 50:
            for item in items[50:]:
                self.tree.delete(item)

    def load_recent_activities(self):
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            cursor.execute('''
                SELECT app, title, duration, category, synced 
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
        """Método actualizado que usa duracion_minima_actividad del servidor"""
        if not self.token:
            messagebox.showerror("Error", "No hay sesión activa")
            return

        # Verificar estructura del servidor
        if not self.verify_server_setup():
            messagebox.showerror(
                "Error de Servidor",
                "El servidor no tiene la tabla 'actividad_apps'.\n"
                "Contacte al administrador para configurar la base de datos."
            )
            return

        try:
            print(f"Iniciando sincronización con token: {self.token[:50]}...")

            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()

            # 🆕 USAR duracion_minima_actividad del servidor
            duracion_minima = self.config.get('duracion_minima_actividad', 5)

            cursor.execute('''
                SELECT * FROM activities 
                WHERE synced = 0 
                AND duration >= ? 
                AND app IS NOT NULL 
                AND app != ''
                AND timestamp IS NOT NULL
            ''', (duracion_minima,))

            activities = cursor.fetchall()

            if not activities:
                messagebox.showinfo(
                    "Información", "No hay datos pendientes de sincronización")
                return

            # URL correcta para actividades
            base_url = self.config.get(
                'api_url', 'http://localhost/simpro-lite/api/v1')
            if base_url.endswith('/actividad.php'):
                activity_url = base_url
            else:
                activity_url = f"{base_url}/actividad.php"

            print(f"URL para sincronización: {activity_url}")

            synced_count = 0
            failed_count = 0

            for activity in activities:
                try:
                    # VALIDACIÓN EXHAUSTIVA DE DATOS

                    # Estructura: id, activity_id, timestamp, duration, app, title, session_id, category, synced
                    if len(activity) < 8:
                        print(
                            f"✗ Actividad {activity[0]} tiene estructura incompleta")
                        failed_count += 1
                        continue

                    app_name = str(activity[4]).strip() if activity[4] else ''
                    titulo_ventana = str(
                        activity[5]).strip() if activity[5] else ''
                    timestamp = activity[2]
                    duracion = int(activity[3])
                    categoria_raw = activity[7] if len(
                        activity) > 7 else 'neutral'

                    # Validar app_name no esté vacío
                    if not app_name:
                        print(
                            f"✗ Actividad {activity[0]} tiene nombre de app vacío")
                        failed_count += 1
                        continue

                    # Validar duración
                    if duracion <= 0:
                        print(
                            f"✗ Actividad {activity[0]} tiene duración inválida: {duracion}")
                        failed_count += 1
                        continue

                    # Convertir y validar categoría
                    if isinstance(categoria_raw, (int, float)):
                        category_map = {0: 'neutral',
                                        1: 'productiva', 2: 'distractora'}
                        categoria = category_map.get(
                            int(categoria_raw), 'neutral')
                    else:
                        categoria = str(categoria_raw).lower().strip()
                        if categoria not in ['neutral', 'productiva', 'distractora']:
                            categoria = 'neutral'

                    # VALIDACIÓN Y CONVERSIÓN DE FECHA
                    try:
                        if timestamp:
                            # Intentar parsear diferentes formatos de fecha
                            dt = None

                            # Formato ISO con microsegundos
                            try:
                                dt = datetime.fromisoformat(
                                    timestamp.replace('Z', '+00:00'))
                            except:
                                pass

                            # Formato ISO básico
                            if not dt:
                                try:
                                    dt = datetime.strptime(
                                        timestamp, '%Y-%m-%dT%H:%M:%S')
                                except:
                                    pass

                            # Formato con microsegundos
                            if not dt:
                                try:
                                    dt = datetime.strptime(
                                        timestamp[:19], '%Y-%m-%dT%H:%M:%S')
                                except:
                                    pass

                            # Si no se pudo parsear, usar fecha actual
                            if not dt:
                                print(
                                    f"⚠️ Fecha inválida para actividad {activity[0]}: {timestamp}, usando fecha actual")
                                dt = datetime.now()

                            fecha_formatted = dt.strftime('%Y-%m-%d %H:%M:%S')
                        else:
                            print(
                                f"⚠️ Timestamp vacío para actividad {activity[0]}, usando fecha actual")
                            fecha_formatted = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

                    except Exception as date_error:
                        print(
                            f"⚠️ Error procesando fecha para actividad {activity[0]}: {date_error}")
                        fecha_formatted = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

                    # Limpiar y truncar datos
                    app_name_clean = app_name.replace('\x00', '').strip()[:100]
                    titulo_clean = titulo_ventana.replace(
                        '\x00', '').strip()[:255]

                    # Crear payload final - formato corregido
                    payload = {
                        'nombre_app': app_name_clean,
                        'titulo_ventana': titulo_clean,
                        'fecha_hora_inicio': fecha_formatted,
                        'tiempo_segundos': duracion,
                        'categoria': categoria
                    }

                    # Validación final del payload
                    if not payload['nombre_app']:
                        print(
                            f"✗ Actividad {activity[0]} - nombre_app está vacío después de limpieza")
                        failed_count += 1
                        continue

                    headers = {
                        'Authorization': f'Bearer {self.token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }

                    print(f"\n📤 Enviando actividad ID {activity[0]}:")
                    print(f"  App: '{payload['nombre_app']}'")
                    print(f"  Título: '{payload['titulo_ventana']}'")
                    print(f"  Duración: {payload['tiempo_segundos']}s")
                    print(f"  Categoría: {payload['categoria']}")
                    print(f"  Fecha: {payload['fecha_hora_inicio']}")

                    # Enviar request
                    response = requests.post(
                        activity_url,
                        json=payload,
                        headers=headers,
                        timeout=15
                    )

                    print(f"📥 Status: {response.status_code}")
                    print(f"📥 Response: {response.text}")

                    if response.status_code == 200:
                        try:
                            data = response.json()
                            if data.get('success') or data.get('status') == 'success':
                                cursor.execute(
                                    'UPDATE activities SET synced = 1 WHERE id = ?',
                                    (activity[0],)
                                )
                                synced_count += 1
                                print(
                                    f"✅ Actividad {activity[0]} sincronizada exitosamente")
                            else:
                                failed_count += 1
                                error_msg = data.get(
                                    'error', 'Error desconocido')
                                print(
                                    f"❌ Error en respuesta para actividad {activity[0]}: {error_msg}")

                        except json.JSONDecodeError:
                            failed_count += 1
                            print(
                                f"❌ Respuesta no JSON para actividad {activity[0]}: {response.text}")

                    elif response.status_code == 401:
                        print("🔒 Token inválido o expirado")
                        messagebox.showerror(
                            "Error", "Sesión expirada. Por favor, inicie sesión nuevamente."
                        )
                        self.logout()
                        break

                    elif response.status_code == 400:
                        failed_count += 1
                        try:
                            error_data = response.json()
                            error_detail = error_data.get('error', '')
                            print(
                                f"❌ Error 400 para actividad {activity[0]}: {error_detail}")

                            # Si es error de validación, marcar como problemática para no reintentar
                            if 'requerido' in error_detail.lower() or 'inválido' in error_detail.lower():
                                print(
                                    f"  → Marcando actividad {activity[0]} como problemática")
                                cursor.execute(
                                    'UPDATE activities SET synced = -1 WHERE id = ?',
                                    (activity[0],)
                                )
                        except:
                            print(
                                f"❌ Error 400 para actividad {activity[0]}: {response.text}")

                    elif response.status_code == 500:
                        failed_count += 1
                        print(
                            f"❌ Error 500 del servidor para actividad {activity[0]}")
                        try:
                            error_data = response.json()
                            error_detail = error_data.get('error', '')
                            print(f"  → Detalle: {error_detail}")

                            # Si es error específico de BD, intentar diagnóstico
                            if 'base de datos' in error_detail.lower():
                                print(f"  → Posible problema en servidor con:")
                                print(f"     - Conexión a BD")
                                print(f"     - Estructura de tabla actividad_apps")
                                print(f"     - Permisos de usuario BD")
                                print(f"     - Tipos de datos")
                        except:
                            print(f"  → Respuesta: {response.text}")

                    else:
                        failed_count += 1
                        print(
                            f"❌ Error HTTP {response.status_code} para actividad {activity[0]}: {response.text}")

                except Exception as e:
                    failed_count += 1
                    print(f"❌ Error procesando actividad {activity[0]}: {e}")
                    import traceback
                    print(f"   Stack trace: {traceback.format_exc()}")
                    continue

            # Commit cambios
            conn.commit()
            conn.close()

            # Recargar la tabla
            self.tree.delete(*self.tree.get_children())
            self.load_recent_activities()

            # Mensaje final
            total_activities = len(activities)
            if synced_count > 0:
                message = f"✅ Sincronización completada:\n"
                message += f"   • Exitosas: {synced_count}/{total_activities}\n"
                if failed_count > 0:
                    message += f"   • Fallidas: {failed_count}\n"
                    message += f"\n💡 Revise los logs de la consola para detalles"
                messagebox.showinfo("Sincronización Exitosa", message)
            else:
                message = f"❌ Sincronización fallida:\n"
                message += f"   • Total intentadas: {total_activities}\n"
                message += f"   • Exitosas: 0\n"
                message += f"   • Fallidas: {failed_count}\n\n"
                message += f"🔍 Posibles causas:\n"
                message += f"   • Error 500: Problema en servidor PHP/BD\n"
                message += f"   • Error 400: Datos inválidos\n"
                message += f"   • Error 401: Token expirado\n\n"
                message += f"📋 Revisar logs de servidor PHP para más detalles"
                messagebox.showwarning("Error de Sincronización", message)

        except Exception as e:
            print(f"💥 Error general en sincronización: {e}")
            import traceback
            print(f"Stack trace completo: {traceback.format_exc()}")
            messagebox.showerror(
                "Error", f"Error crítico en sincronización: {str(e)}")

    def test_single_activity(self):
        """Función para probar el envío de una sola actividad de prueba"""
        if not self.token:
            print("No hay token para prueba")
            return

        try:
            base_url = self.config.get(
                'api_url', 'http://localhost/simpro-lite/api/v1')
            activity_url = f"{base_url}/actividad.php" if not base_url.endswith(
                '/actividad.php') else base_url

            # Actividad de prueba simple
            test_payload = {
                'nombre_app': 'TestApp.exe',
                'titulo_ventana': 'Ventana de Prueba',
                'fecha_hora_inicio': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                'tiempo_segundos': 10,
                'categoria': 'neutral'
            }

            headers = {
                'Authorization': f'Bearer {self.token}',
                'Content-Type': 'application/json'
            }

            print(f"🧪 Enviando actividad de prueba a: {activity_url}")
            print(f"🧪 Payload: {json.dumps(test_payload, indent=2)}")

            response = requests.post(
                activity_url, json=test_payload, headers=headers, timeout=10)

            print(f"🧪 Respuesta - Status: {response.status_code}")
            print(f"🧪 Respuesta - Texto: {response.text}")

            if response.status_code == 200:
                print("✅ Prueba exitosa - La API funciona correctamente")
                messagebox.showinfo(
                    "Prueba API", "✅ Prueba exitosa\nLa API funciona correctamente")
            else:
                print("❌ Prueba fallida - Hay problemas con la API")
                messagebox.showerror(
                    "Prueba API", f"❌ Prueba fallida\nStatus: {response.status_code}\nError: {response.text}")

        except Exception as e:
            print(f"💥 Error en prueba: {e}")
            messagebox.showerror(
                "Error en Prueba", f"Error al probar API: {str(e)}")

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
                        category = self.classify_app(app_info['app'])
                        self.root.after(0, lambda: self.current_app_label.config(
                            text=f"App: {app_info['app']} [{category}]"))
                        self.root.after(
                            0, lambda: self.record_activity(app_info))
                    time.sleep(self.config.get("intervalo", 10))
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
