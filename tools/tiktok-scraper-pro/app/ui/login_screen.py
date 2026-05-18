"""
Login / License Activation Screen
"""
import asyncio
import hashlib
import platform
import uuid

from PySide6.QtWidgets import (
    QWidget, QVBoxLayout, QHBoxLayout, QLabel, QLineEdit,
    QPushButton, QFrame, QStackedWidget,
)
from PySide6.QtCore import Qt, QThread, Signal
from PySide6.QtGui import QFont

from app.core.config import settings

LOGIN_STYLE = """
QWidget { background-color: #0B0F19; color: #CBD5E1; font-family: 'Inter', 'Segoe UI', sans-serif; }
QFrame#card {
    background: rgba(17, 24, 39, 0.9);
    border: 1px solid #1E293B;
    border-radius: 20px;
    padding: 40px;
}
QLineEdit {
    background: #0F172A;
    border: 1px solid #1E293B;
    border-radius: 10px;
    padding: 12px 16px;
    color: #E2E8F0;
    font-size: 14px;
}
QLineEdit:focus { border: 1px solid #38BDF8; }
QPushButton#login_btn {
    background: qlineargradient(x1:0,y1:0,x2:1,y2:0, stop:0 #0EA5E9, stop:1 #6366F1);
    color: white;
    border: none;
    border-radius: 10px;
    padding: 14px;
    font-weight: 700;
    font-size: 14px;
}
QPushButton#login_btn:hover {
    background: qlineargradient(x1:0,y1:0,x2:1,y2:0, stop:0 #38BDF8, stop:1 #818CF8);
}
QPushButton#login_btn:disabled { background: #1E293B; color: #475569; }
"""


def _get_hardware_id() -> str:
    try:
        node = uuid.getnode()
        cpu = platform.processor() or platform.machine()
        raw = f"{node}-{cpu}"
        return hashlib.sha256(raw.encode()).hexdigest()
    except Exception:
        return hashlib.sha256(b"fallback-device").hexdigest()


class AuthWorker(QThread):
    success = Signal(dict)   # user info dict
    error   = Signal(str)

    def __init__(self, email: str, password: str, license_key: str, parent=None):
        super().__init__(parent)
        self._email       = email
        self._password    = password
        self._license_key = license_key

    def run(self):
        loop = asyncio.new_event_loop()
        asyncio.set_event_loop(loop)
        try:
            result = loop.run_until_complete(self._authenticate())
            self.success.emit(result)
        except Exception as e:
            self.error.emit(str(e))
        finally:
            loop.close()

    async def _authenticate(self) -> dict:
        import sys, os
        sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", "..", "..")))
        try:
            from isass_client import ISASSClient, LicenseError, AuthenticationError
            async with ISASSClient(settings.API_BASE_URL, tool_slug=settings.TOOL_SLUG, app_version=settings.APP_VERSION) as client:
                login_result = await client.login(self._email, self._password)
                client.set_token(login_result.token)

                hw_id = _get_hardware_id()
                device_name = platform.node() or "PC"
                os_name = platform.system() + " " + platform.release()

                await client.activate_license(self._license_key, hw_id, device_name, os_name)

                # Persist token + key
                settings.API_TOKEN   = login_result.token
                settings.LICENSE_KEY = self._license_key

                return {"name": login_result.name, "email": login_result.email}
        except ImportError:
            # Offline / development mode — allow bypass with .env token
            if settings.API_TOKEN and settings.LICENSE_KEY:
                return {"name": "Developer", "email": "dev@local"}
            raise RuntimeError("isass_client not installed and no local token found.")


class LoginScreen(QWidget):
    login_success = Signal(dict)

    def __init__(self, parent=None):
        super().__init__(parent)
        self.setStyleSheet(LOGIN_STYLE)
        self._worker = None
        self._build_ui()

    def _build_ui(self):
        outer = QVBoxLayout(self)
        outer.setAlignment(Qt.AlignmentFlag.AlignCenter)

        card = QFrame()
        card.setObjectName("card")
        card.setFixedWidth(440)
        layout = QVBoxLayout(card)
        layout.setSpacing(20)

        # Header
        icon_lbl = QLabel("🎵")
        icon_lbl.setAlignment(Qt.AlignmentFlag.AlignCenter)
        icon_lbl.setStyleSheet("font-size: 48px; padding: 8px 0;")
        layout.addWidget(icon_lbl)

        title = QLabel(settings.APP_NAME)
        title.setAlignment(Qt.AlignmentFlag.AlignCenter)
        title.setStyleSheet("color: #F8FAFC; font-size: 22px; font-weight: 700;")
        layout.addWidget(title)

        sub = QLabel(f"v{settings.APP_VERSION}  ·  Sign in with your iSAAS account")
        sub.setAlignment(Qt.AlignmentFlag.AlignCenter)
        sub.setStyleSheet("color: #64748B; font-size: 12px;")
        layout.addWidget(sub)

        # Form
        self._email_input = QLineEdit()
        self._email_input.setPlaceholderText("Email address")
        layout.addWidget(self._email_input)

        self._pass_input = QLineEdit()
        self._pass_input.setPlaceholderText("Password")
        self._pass_input.setEchoMode(QLineEdit.EchoMode.Password)
        layout.addWidget(self._pass_input)

        self._key_input = QLineEdit()
        self._key_input.setPlaceholderText("License key  (e.g. xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)")
        self._key_input.returnPressed.connect(self._do_login)
        layout.addWidget(self._key_input)

        self._error_lbl = QLabel("")
        self._error_lbl.setStyleSheet("color: #F87171; font-size: 12px; text-align: center;")
        self._error_lbl.setAlignment(Qt.AlignmentFlag.AlignCenter)
        self._error_lbl.setVisible(False)
        layout.addWidget(self._error_lbl)

        self._login_btn = QPushButton("Activate & Sign In")
        self._login_btn.setObjectName("login_btn")
        self._login_btn.clicked.connect(self._do_login)
        layout.addWidget(self._login_btn)

        sub2 = QLabel("Don't have a license? <a style='color:#38BDF8;' href='https://isass.app/tools/tiktok-scraper-pro'>Subscribe at isass.app</a>")
        sub2.setOpenExternalLinks(True)
        sub2.setAlignment(Qt.AlignmentFlag.AlignCenter)
        sub2.setStyleSheet("color: #64748B; font-size: 11px;")
        layout.addWidget(sub2)

        outer.addWidget(card)

    def _do_login(self):
        email = self._email_input.text().strip()
        password = self._pass_input.text()
        key = self._key_input.text().strip()
        if not email or not password or not key:
            self._show_error("Please fill in all fields.")
            return

        self._login_btn.setEnabled(False)
        self._login_btn.setText("⏳  Activating…")
        self._error_lbl.setVisible(False)

        self._worker = AuthWorker(email, password, key)
        self._worker.success.connect(self._on_success)
        self._worker.error.connect(self._on_error)
        self._worker.start()

    def _on_success(self, user_info: dict):
        self._login_btn.setEnabled(True)
        self._login_btn.setText("Activate & Sign In")
        self.login_success.emit(user_info)

    def _on_error(self, msg: str):
        self._login_btn.setEnabled(True)
        self._login_btn.setText("Activate & Sign In")
        self._show_error(msg)

    def _show_error(self, msg: str):
        self._error_lbl.setText(f"⚠  {msg}")
        self._error_lbl.setVisible(True)
