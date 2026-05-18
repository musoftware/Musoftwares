"""
Main Window — PySide6 shell for every iSAAS desktop tool.
Provides sidebar navigation, central content area, status bar,
and update notification banner. Tool-specific modules plug into
the `modules/` directory and register themselves as sidebar items.
"""

from PySide6.QtCore import Qt, QTimer
from PySide6.QtGui import QFont, QIcon
from PySide6.QtWidgets import (
    QMainWindow, QWidget, QHBoxLayout, QVBoxLayout,
    QLabel, QPushButton, QFrame, QStackedWidget,
    QStatusBar,
)
from loguru import logger
from app.core.config import settings


class SidebarButton(QPushButton):
    def __init__(self, label: str, active: bool = False):
        super().__init__(label)
        self.setCheckable(True)
        self.setChecked(active)
        self.setCursor(Qt.PointingHandCursor)
        self.setFixedHeight(40)
        self.setStyleSheet("""
            QPushButton {
                background: transparent; color: #94a3b8; border: none;
                text-align: left; padding: 0 16px; border-radius: 8px;
                font-size: 13px; font-weight: 500;
            }
            QPushButton:hover   { background: #1e293b; color: #e2e8f0; }
            QPushButton:checked { background: #1e293b; color: #f1f5f9; font-weight: 600; }
        """)


class MainWindow(QMainWindow):
    def __init__(self, token: str, license_key: str, parent=None):
        super().__init__(parent)
        self.token       = token
        self.license_key = license_key
        self._pages: dict[str, QWidget] = {}
        self._sidebar_buttons: list[SidebarButton] = []
        self._setup_ui()
        self._setup_heartbeat()

    def _setup_ui(self):
        self.setWindowTitle(f"{settings.APP_NAME} — v{settings.APP_VERSION}")
        self.resize(1100, 700)
        self.setMinimumSize(800, 520)

        self.setStyleSheet("""
            QMainWindow, QWidget { background: #0f172a; color: #e2e8f0; font-family: 'Inter', 'Segoe UI', sans-serif; }
            QStatusBar { background: #0f172a; color: #475569; font-size: 11px; border-top: 1px solid #1e293b; }
        """)

        central = QWidget()
        root    = QHBoxLayout(central)
        root.setContentsMargins(0, 0, 0, 0)
        root.setSpacing(0)
        self.setCentralWidget(central)

        # ── Sidebar ────────────────────────────────────────────────────
        sidebar = QFrame()
        sidebar.setFixedWidth(220)
        sidebar.setStyleSheet("QFrame { background: #0a1628; border-right: 1px solid #1e293b; }")
        sidebar_layout = QVBoxLayout(sidebar)
        sidebar_layout.setContentsMargins(12, 20, 12, 20)
        sidebar_layout.setSpacing(4)

        # App name
        app_title = QLabel(settings.APP_NAME)
        app_title.setFont(QFont("Inter", 14, QFont.Bold))
        app_title.setStyleSheet("color: #f1f5f9; padding: 0 8px 16px 8px; font-size: 14px; font-weight: 700;")
        sidebar_layout.addWidget(app_title)

        self._sidebar_layout = sidebar_layout
        sidebar_layout.addStretch()

        # Version at bottom
        ver_label = QLabel(f"v{settings.APP_VERSION}")
        ver_label.setStyleSheet("color: #334155; font-size: 11px; padding: 8px;")
        sidebar_layout.addWidget(ver_label)

        # ── Content Area ───────────────────────────────────────────────
        self._stack = QStackedWidget()
        self._stack.setStyleSheet("QStackedWidget { background: #0f172a; }")

        root.addWidget(sidebar)
        root.addWidget(self._stack)

        # ── Status Bar ─────────────────────────────────────────────────
        status = QStatusBar()
        self.setStatusBar(status)
        status.showMessage(f"Connected · {settings.APP_NAME} v{settings.APP_VERSION}")

        logger.info("Main window initialized")

    def register_page(self, page_id: str, label: str, widget: QWidget, is_first: bool = False):
        """
        Register a tool page in the sidebar and content stack.
        Call this from each module's setup routine.
        """
        self._pages[page_id] = widget
        self._stack.addWidget(widget)

        btn = SidebarButton(label, active=is_first)
        btn.clicked.connect(lambda checked, pid=page_id: self._navigate(pid))
        # Insert before the stretch
        insert_pos = self._sidebar_layout.count() - 2  # before stretch + version
        self._sidebar_layout.insertWidget(insert_pos, btn)
        self._sidebar_buttons.append(btn)

        if is_first:
            self._stack.setCurrentWidget(widget)

    def _navigate(self, page_id: str):
        widget = self._pages.get(page_id)
        if widget:
            self._stack.setCurrentWidget(widget)
        for btn in self._sidebar_buttons:
            btn.setChecked(btn.text() == self._get_label_for(page_id))

    def _get_label_for(self, page_id: str) -> str:
        # helper — maps page_id back to label
        return page_id.replace("-", " ").title()

    def show_update_banner(self, latest_version: str, changelog: str):
        """Show a dismissible top banner when an update is available."""
        banner = QFrame(self.centralWidget())
        banner.setStyleSheet("""
            QFrame { background: #1e3a5f; border-bottom: 1px solid #2563eb; }
            QLabel { color: #bfdbfe; font-size: 12px; }
            QPushButton { background: #2563eb; color: white; border-radius: 6px; padding: 4px 12px; font-size: 12px; }
        """)
        layout = QHBoxLayout(banner)
        layout.setContentsMargins(16, 8, 16, 8)

        msg = QLabel(f"✨ Update available: v{latest_version}  —  {changelog[:80]}{'...' if len(changelog) > 80 else ''}")
        update_btn  = QPushButton("Update Now")
        dismiss_btn = QPushButton("Later")
        dismiss_btn.setStyleSheet("background: transparent; color: #64748b; border: none;")

        update_btn.clicked.connect(lambda: self._trigger_update(latest_version))
        dismiss_btn.clicked.connect(banner.hide)

        layout.addWidget(msg)
        layout.addStretch()
        layout.addWidget(update_btn)
        layout.addWidget(dismiss_btn)

        # Insert at top of central widget
        main_layout = self.centralWidget().layout()
        main_layout.insertWidget(0, banner)

    def _trigger_update(self, version: str):
        logger.info(f"User initiated update to v{version}")
        # Implemented in updater module — emits signal to worker
        self.statusBar().showMessage(f"Downloading update v{version}...")

    def _setup_heartbeat(self):
        """Ping the server every 30 minutes to keep the license alive."""
        self._heartbeat_timer = QTimer(self)
        self._heartbeat_timer.setInterval(30 * 60 * 1000)  # 30 min
        self._heartbeat_timer.timeout.connect(self._do_heartbeat)
        self._heartbeat_timer.start()

    def _do_heartbeat(self):
        import asyncio
        from app.api.client import APIClient
        from app.api.license import LicenseService

        async def _ping():
            async with APIClient(token=self.token) as client:
                svc = LicenseService(client)
                alive = await svc.heartbeat(self.license_key)
                if not alive:
                    logger.warning("Heartbeat returned dead — license may have been revoked")

        try:
            asyncio.run(_ping())
        except Exception as e:
            logger.debug(f"Heartbeat error (offline?): {e}")
