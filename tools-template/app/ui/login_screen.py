"""
Login Screen — first screen shown when no valid license/session exists.
Flow: Email + Password → API login → Save token → License key input → Activate device
"""

import asyncio
from PySide6.QtCore import Qt, QThread, Signal
from PySide6.QtGui import QFont
from PySide6.QtWidgets import (
    QWidget, QVBoxLayout, QHBoxLayout, QLabel,
    QLineEdit, QPushButton, QFrame, QStackedWidget,
    QMessageBox, QProgressBar,
)
from loguru import logger
from app.api.client import APIClient, APIError
from app.api.license import LicenseService
from app.core.config import settings


class LoginWorker(QThread):
    success = Signal(str, str)   # token, email
    error   = Signal(str)

    def __init__(self, email: str, password: str):
        super().__init__()
        self.email    = email
        self.password = password

    def run(self):
        async def _do():
            async with APIClient() as client:
                return await client.post("/auth/login", json={
                    "email": self.email, "password": self.password
                })
        try:
            result = asyncio.run(_do())
            self.success.emit(result["token"], self.email)
        except APIError as e:
            self.error.emit(str(e))
        except Exception as e:
            self.error.emit(f"Unexpected error: {e}")


class ActivationWorker(QThread):
    success = Signal(dict)
    error   = Signal(str)

    def __init__(self, token: str, license_key: str):
        super().__init__()
        self.token       = token
        self.license_key = license_key

    def run(self):
        async def _do():
            async with APIClient(token=self.token) as client:
                svc = LicenseService(client)
                return await svc.activate(self.license_key)
        try:
            result = asyncio.run(_do())
            self.success.emit(result)
        except APIError as e:
            self.error.emit(str(e))
        except Exception as e:
            self.error.emit(f"Unexpected error: {e}")


class LoginScreen(QWidget):
    """Emits `activated` when the user successfully logs in and activates their device."""

    activated = Signal(str, str)  # token, license_key

    def __init__(self, parent=None):
        super().__init__(parent)
        self._token = ""
        self._setup_ui()

    def _setup_ui(self):
        self.setStyleSheet("""
            QWidget { background: #0f172a; color: #e2e8f0; font-family: 'Inter', 'Segoe UI', sans-serif; }
            QLabel  { color: #cbd5e1; }
            QLineEdit {
                background: #1e293b; border: 1.5px solid #334155; border-radius: 8px;
                padding: 10px 14px; color: #f1f5f9; font-size: 14px;
            }
            QLineEdit:focus { border-color: #6366f1; }
            QPushButton {
                background: #6366f1; color: white; border: none; border-radius: 8px;
                padding: 11px 20px; font-size: 14px; font-weight: 600;
            }
            QPushButton:hover   { background: #5558e8; }
            QPushButton:pressed { background: #4f51d8; }
            QPushButton:disabled { background: #334155; color: #64748b; }
        """)

        outer = QVBoxLayout(self)
        outer.setAlignment(Qt.AlignCenter)

        card = QFrame()
        card.setFixedWidth(420)
        card.setStyleSheet("QFrame { background: #1e293b; border-radius: 16px; }")
        layout = QVBoxLayout(card)
        layout.setContentsMargins(40, 40, 40, 40)
        layout.setSpacing(20)

        # Logo / App name
        title = QLabel(settings.APP_NAME)
        title.setFont(QFont("Inter", 22, QFont.Bold))
        title.setStyleSheet("color: #f8fafc; font-size: 22px; font-weight: 700;")
        title.setAlignment(Qt.AlignCenter)

        subtitle = QLabel(f"v{settings.APP_VERSION} — Sign in to continue")
        subtitle.setStyleSheet("color: #64748b; font-size: 13px;")
        subtitle.setAlignment(Qt.AlignCenter)

        # Stack: login form / activation form
        self._stack = QStackedWidget()

        # ── Login Page ──────────────────────────────────────────────────
        login_page = QWidget()
        lp = QVBoxLayout(login_page)
        lp.setSpacing(12)

        self._email    = QLineEdit(); self._email.setPlaceholderText("Email address")
        self._password = QLineEdit(); self._password.setPlaceholderText("Password")
        self._password.setEchoMode(QLineEdit.Password)

        self._login_btn = QPushButton("Sign In")
        self._login_btn.setCursor(Qt.PointingHandCursor)
        self._login_btn.clicked.connect(self._do_login)

        self._login_status = QLabel("")
        self._login_status.setWordWrap(True)
        self._login_status.setStyleSheet("color: #f87171; font-size: 12px;")

        lp.addWidget(QLabel("Email")); lp.addWidget(self._email)
        lp.addWidget(QLabel("Password")); lp.addWidget(self._password)
        lp.addWidget(self._login_btn)
        lp.addWidget(self._login_status)

        # ── Activation Page ─────────────────────────────────────────────
        act_page = QWidget()
        ap = QVBoxLayout(act_page)
        ap.setSpacing(12)

        act_label = QLabel("Enter your License Key")
        act_label.setStyleSheet("color: #94a3b8; font-size: 13px;")

        self._license_input = QLineEdit()
        self._license_input.setPlaceholderText("xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx")
        self._license_input.setFont(QFont("Courier New", 12))

        self._activate_btn = QPushButton("Activate Device")
        self._activate_btn.setCursor(Qt.PointingHandCursor)
        self._activate_btn.clicked.connect(self._do_activate)

        self._act_status = QLabel("")
        self._act_status.setWordWrap(True)
        self._act_status.setStyleSheet("color: #f87171; font-size: 12px;")

        ap.addWidget(act_label)
        ap.addWidget(self._license_input)
        ap.addWidget(self._activate_btn)
        ap.addWidget(self._act_status)

        self._stack.addWidget(login_page)   # index 0
        self._stack.addWidget(act_page)     # index 1

        layout.addWidget(title)
        layout.addWidget(subtitle)
        layout.addSpacing(8)
        layout.addWidget(self._stack)

        outer.addWidget(card)
        self.setLayout(outer)

    def _do_login(self):
        email    = self._email.text().strip()
        password = self._password.text()
        if not email or not password:
            self._login_status.setText("Please enter your email and password.")
            return

        self._login_btn.setText("Signing in...")
        self._login_btn.setEnabled(False)
        self._login_status.setText("")

        self._worker = LoginWorker(email, password)
        self._worker.success.connect(self._on_login_success)
        self._worker.error.connect(self._on_login_error)
        self._worker.start()

    def _on_login_success(self, token: str, email: str):
        logger.info(f"Login successful: {email}")
        self._token = token
        self._login_btn.setText("Sign In")
        self._login_btn.setEnabled(True)
        # Check if license key is already saved
        if settings.LICENSE_KEY:
            self._license_input.setText(settings.LICENSE_KEY)
        self._stack.setCurrentIndex(1)

    def _on_login_error(self, message: str):
        logger.warning(f"Login failed: {message}")
        self._login_status.setText(message)
        self._login_btn.setText("Sign In")
        self._login_btn.setEnabled(True)

    def _do_activate(self):
        key = self._license_input.text().strip()
        if not key:
            self._act_status.setText("Please enter your license key.")
            return

        self._activate_btn.setText("Activating...")
        self._activate_btn.setEnabled(False)
        self._act_status.setText("")

        self._act_worker = ActivationWorker(self._token, key)
        self._act_worker.success.connect(lambda r: self._on_activated(key))
        self._act_worker.error.connect(self._on_activation_error)
        self._act_worker.start()

    def _on_activated(self, license_key: str):
        logger.success(f"Device activated with license: {license_key[:8]}...")
        self.activated.emit(self._token, license_key)

    def _on_activation_error(self, message: str):
        logger.warning(f"Activation failed: {message}")
        self._act_status.setText(message)
        self._activate_btn.setText("Activate Device")
        self._activate_btn.setEnabled(True)
