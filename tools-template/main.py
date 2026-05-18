#!/usr/bin/env python3
"""
iSAAS Desktop Tool — Entry Point
==================================
Boot sequence:
1. Load configuration (.env)
2. Initialize logger
3. Create QApplication
4. Check existing token/license in local storage
5. If valid → go straight to MainWindow
6. If not   → show LoginScreen
7. After login/activation → show MainWindow + background update check
"""

import sys
import asyncio
from pathlib import Path

from PySide6.QtWidgets import QApplication, QStackedWidget
from PySide6.QtGui import QFont, QIcon
from PySide6.QtCore import Qt

# Initialize logger before anything else
import app.core.logger  # noqa: F401 — side-effect import sets up loguru

from loguru import logger
from app.core.config import settings
from app.ui.login_screen import LoginScreen
from app.ui.main_window import MainWindow

# ── Optional: tool-specific dashboard page ───────────────────────────────────
# from app.modules.dashboard import DashboardPage


def _check_existing_session() -> tuple[str, str] | None:
    """
    Check if a valid token + license key are already stored.
    Returns (token, license_key) if found, None otherwise.
    Quick offline check — full validation happens in background.
    """
    token = settings.API_TOKEN
    key   = settings.LICENSE_KEY
    if token and key:
        logger.info("Found existing session — skipping login screen")
        return token, key
    return None


def build_main_window(token: str, license_key: str) -> MainWindow:
    """Construct and populate the main window with tool pages."""
    window = MainWindow(token=token, license_key=license_key)

    # Register pages — add your tool modules here:
    # window.register_page("dashboard", "Dashboard", DashboardPage(), is_first=True)
    # window.register_page("settings", "Settings", SettingsPage())
    # window.register_page("logs", "Logs", LogsPage())

    # Default placeholder (remove when you add real pages)
    from PySide6.QtWidgets import QLabel
    from PySide6.QtCore import Qt as QtAlignEnum
    placeholder = QLabel(f"✅  {settings.APP_NAME} is running\n\nAdd your tool pages in main.py → build_main_window()")
    placeholder.setAlignment(QtAlignEnum.AlignCenter)
    placeholder.setStyleSheet("color: #64748b; font-size: 16px;")
    window.register_page("home", "Home", placeholder, is_first=True)

    return window


def check_for_updates_in_background(token: str):
    """Fire-and-forget update check — runs after window is shown."""
    import threading

    def _check():
        async def _async_check():
            from app.api.client import APIClient
            from app.api.updater import UpdaterService
            try:
                async with APIClient(token=token) as client:
                    svc    = UpdaterService(client)
                    info   = await svc.check_for_update()
                    return info
            except Exception as e:
                logger.debug(f"Background update check error: {e}")
                return None

        result = asyncio.run(_async_check())
        return result

    threading.Thread(target=_check, daemon=True).start()


def main():
    logger.info(f"Starting {settings.APP_NAME} v{settings.APP_VERSION}")

    app = QApplication(sys.argv)
    app.setApplicationName(settings.APP_NAME)
    app.setApplicationVersion(settings.APP_VERSION)
    app.setAttribute(Qt.AA_UseHighDpiPixmaps)

    # Set global font
    font = QFont("Inter", 10)
    app.setFont(font)

    # ── Check existing session ────────────────────────────────────────────────
    session = _check_existing_session()
    main_window: MainWindow | None = None

    if session:
        token, license_key = session
        main_window = build_main_window(token, license_key)
        main_window.show()
        check_for_updates_in_background(token)
    else:
        # ── Show Login Screen ────────────────────────────────────────────────
        login = LoginScreen()
        login.setWindowTitle(f"{settings.APP_NAME} — Sign In")
        login.resize(500, 420)

        def on_activated(token: str, license_key: str):
            nonlocal main_window
            # Save credentials to .env / local storage
            _persist_session(token, license_key)
            login.hide()
            main_window = build_main_window(token, license_key)
            main_window.show()
            check_for_updates_in_background(token)

        login.activated.connect(on_activated)
        login.show()

    sys.exit(app.exec())


def _persist_session(token: str, license_key: str):
    """Write token and license key to .env for future launches."""
    env_file = Path(".env")
    lines = env_file.read_text().splitlines() if env_file.exists() else []
    updated = {}
    for line in lines:
        if "=" in line and not line.startswith("#"):
            k, _, v = line.partition("=")
            updated[k.strip()] = v.strip()
    updated["API_TOKEN"]   = token
    updated["LICENSE_KEY"] = license_key
    env_file.write_text("\n".join(f"{k}={v}" for k, v in updated.items()) + "\n")
    logger.debug("Session persisted to .env")


if __name__ == "__main__":
    main()
