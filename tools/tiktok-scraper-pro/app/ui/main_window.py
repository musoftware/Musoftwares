"""
TikTok Scraper Pro — Main Window
Dark glass UI with sidebar navigation and tabbed scraper pages.
"""
import os
from PySide6.QtWidgets import (
    QMainWindow, QWidget, QHBoxLayout, QVBoxLayout,
    QPushButton, QLabel, QStackedWidget, QFrame, QSizePolicy,
    QStatusBar, QProgressBar,
)
from PySide6.QtCore import Qt, QSize, QPropertyAnimation, QEasingCurve, QTimer
from PySide6.QtGui import QIcon, QFont, QColor, QPalette

from app.core.config import settings
from app.ui.pages.profile_page import ProfilePage
from app.ui.pages.hashtag_page import HashtagPage
from app.ui.pages.keyword_page import KeywordPage
from app.ui.pages.settings_page import SettingsPage


NAV_ITEMS = [
    ("profile",  "👤",  "Profile Scraper"),
    ("hashtag",  "#",   "Hashtag Feed"),
    ("keyword",  "🔍",  "Keyword Search"),
    ("settings", "⚙",  "Settings"),
]

DARK_STYLE = """
QMainWindow, QWidget#root {
    background-color: #0B0F19;
    color: #CBD5E1;
    font-family: 'Inter', 'Segoe UI', sans-serif;
}

/* ── Sidebar ──────────────────────────────────────── */
QWidget#sidebar {
    background-color: #111827;
    border-right: 1px solid #1E293B;
}

QPushButton#nav_btn {
    background: transparent;
    color: #94A3B8;
    border: none;
    border-radius: 10px;
    padding: 10px 16px;
    text-align: left;
    font-size: 13px;
}
QPushButton#nav_btn:hover {
    background: rgba(56, 189, 248, 0.08);
    color: #E2E8F0;
}
QPushButton#nav_btn[active="true"] {
    background: rgba(56, 189, 248, 0.15);
    color: #38BDF8;
    font-weight: 600;
    border-left: 3px solid #38BDF8;
}

/* ── Top Bar ──────────────────────────────────────── */
QWidget#topbar {
    background-color: rgba(17, 24, 39, 0.95);
    border-bottom: 1px solid #1E293B;
}

/* ── Content ──────────────────────────────────────── */
QWidget#content {
    background-color: #0B0F19;
}

/* ── Status Bar ───────────────────────────────────── */
QStatusBar {
    background-color: #111827;
    color: #64748B;
    font-size: 11px;
    border-top: 1px solid #1E293B;
}

QProgressBar {
    background: #1E293B;
    border: none;
    border-radius: 4px;
    height: 4px;
    text-align: center;
}
QProgressBar::chunk {
    background: qlineargradient(x1:0,y1:0,x2:1,y2:0, stop:0 #38BDF8, stop:1 #818CF8);
    border-radius: 4px;
}
"""


class MainWindow(QMainWindow):
    def __init__(self, user_info: dict):
        super().__init__()
        self._user = user_info
        self._active_nav = "profile"
        self._worker = None
        self._setup_window()
        self._build_ui()
        self._switch_page("profile")

    def _setup_window(self):
        self.setWindowTitle(f"{settings.APP_NAME} v{settings.APP_VERSION}")
        self.setMinimumSize(1100, 700)
        self.resize(1280, 780)
        self.setStyleSheet(DARK_STYLE)
        try:
            icon_path = os.path.join(os.path.dirname(__file__), "..", "..", "assets", "icon.ico")
            if os.path.exists(icon_path):
                self.setWindowIcon(QIcon(icon_path))
        except Exception:
            pass

    def _build_ui(self):
        root = QWidget()
        root.setObjectName("root")
        self.setCentralWidget(root)

        main_layout = QVBoxLayout(root)
        main_layout.setContentsMargins(0, 0, 0, 0)
        main_layout.setSpacing(0)

        # Top bar
        topbar = self._build_topbar()
        main_layout.addWidget(topbar)

        # Body (sidebar + content)
        body = QWidget()
        body_layout = QHBoxLayout(body)
        body_layout.setContentsMargins(0, 0, 0, 0)
        body_layout.setSpacing(0)

        sidebar = self._build_sidebar()
        body_layout.addWidget(sidebar)

        content_area = self._build_content()
        body_layout.addWidget(content_area, 1)

        main_layout.addWidget(body, 1)

        # Status bar
        self._status_bar = QStatusBar()
        self._progress = QProgressBar()
        self._progress.setMaximumWidth(180)
        self._progress.setValue(0)
        self._progress.setVisible(False)
        self._status_bar.addPermanentWidget(self._progress)
        self.setStatusBar(self._status_bar)
        self._status_bar.showMessage("Ready")

    def _build_topbar(self) -> QWidget:
        bar = QWidget()
        bar.setObjectName("topbar")
        bar.setFixedHeight(56)
        layout = QHBoxLayout(bar)
        layout.setContentsMargins(20, 0, 20, 0)

        # Logo + title
        title_lbl = QLabel(f"<b>TikTok Scraper Pro</b>")
        title_lbl.setStyleSheet("color: #F8FAFC; font-size: 16px; font-weight: 700;")

        version_lbl = QLabel(f"v{settings.APP_VERSION}")
        version_lbl.setStyleSheet(
            "color: #38BDF8; font-size: 11px; font-family: 'Fira Code', monospace;"
            "background: rgba(56,189,248,0.1); padding: 2px 8px; border-radius: 999px;"
        )

        layout.addWidget(title_lbl)
        layout.addWidget(version_lbl)
        layout.addStretch()

        # User info
        user_lbl = QLabel(f"👤  {self._user.get('name', 'User')}")
        user_lbl.setStyleSheet("color: #94A3B8; font-size: 12px;")
        layout.addWidget(user_lbl)

        # License status
        lic_badge = QLabel("● Licensed")
        lic_badge.setStyleSheet(
            "color: #4ADE80; font-size: 11px; font-weight: 600;"
            "background: rgba(74,222,128,0.1); padding: 2px 10px; border-radius: 999px;"
        )
        layout.addWidget(lic_badge)
        return bar

    def _build_sidebar(self) -> QWidget:
        sidebar = QWidget()
        sidebar.setObjectName("sidebar")
        sidebar.setFixedWidth(200)
        layout = QVBoxLayout(sidebar)
        layout.setContentsMargins(12, 20, 12, 20)
        layout.setSpacing(4)

        # App icon
        icon_lbl = QLabel("🎵")
        icon_lbl.setAlignment(Qt.AlignmentFlag.AlignCenter)
        icon_lbl.setStyleSheet("font-size: 32px; padding: 8px 0 20px;")
        layout.addWidget(icon_lbl)

        self._nav_buttons: dict[str, QPushButton] = {}
        for key, icon, label in NAV_ITEMS:
            btn = QPushButton(f"  {icon}  {label}")
            btn.setObjectName("nav_btn")
            btn.setFixedHeight(42)
            btn.setCursor(Qt.CursorShape.PointingHandCursor)
            btn.clicked.connect(lambda checked=False, k=key: self._switch_page(k))
            self._nav_buttons[key] = btn
            layout.addWidget(btn)

        layout.addStretch()

        # License info
        lic_info = QLabel(f"License active\n{settings.LICENSE_KEY[:12]}…")
        lic_info.setStyleSheet("color: #475569; font-size: 10px; padding: 8px 4px;")
        lic_info.setAlignment(Qt.AlignmentFlag.AlignCenter)
        layout.addWidget(lic_info)
        return sidebar

    def _build_content(self) -> QWidget:
        content = QWidget()
        content.setObjectName("content")
        layout = QVBoxLayout(content)
        layout.setContentsMargins(0, 0, 0, 0)
        layout.setSpacing(0)

        self._stack = QStackedWidget()
        self._pages: dict[str, QWidget] = {
            "profile":  ProfilePage(self),
            "hashtag":  HashtagPage(self),
            "keyword":  KeywordPage(self),
            "settings": SettingsPage(self),
        }
        for page in self._pages.values():
            self._stack.addWidget(page)

        layout.addWidget(self._stack)
        return content

    def _switch_page(self, key: str):
        self._active_nav = key
        for k, btn in self._nav_buttons.items():
            btn.setProperty("active", k == key)
            btn.style().unpolish(btn)
            btn.style().polish(btn)

        page = self._pages.get(key)
        if page:
            self._stack.setCurrentWidget(page)

    # ── Called by pages ──────────────────────────────────────────────────────

    def set_status(self, msg: str):
        self._status_bar.showMessage(msg)

    def set_progress(self, current: int, total: int):
        if total > 0:
            self._progress.setVisible(True)
            self._progress.setMaximum(total)
            self._progress.setValue(current)
        else:
            self._progress.setVisible(False)

    def hide_progress(self):
        self._progress.setVisible(False)
        self._progress.setValue(0)
