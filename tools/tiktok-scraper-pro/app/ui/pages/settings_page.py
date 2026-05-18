"""Settings page — proxy, delay, headless mode, license info."""
from PySide6.QtWidgets import (
    QWidget, QVBoxLayout, QHBoxLayout, QLabel, QLineEdit,
    QPushButton, QGroupBox, QCheckBox, QSpinBox, QFormLayout,
)
from PySide6.QtCore import Qt
from app.core.config import settings
from app.ui.pages.profile_page import PAGE_STYLE


class SettingsPage(QWidget):
    def __init__(self, main_win, parent=None):
        super().__init__(parent)
        self._main = main_win
        self.setStyleSheet(PAGE_STYLE)
        self._build_ui()

    def _build_ui(self):
        layout = QVBoxLayout(self)
        layout.setContentsMargins(24, 20, 24, 20)
        layout.setSpacing(20)

        header = QLabel("⚙  Settings")
        header.setStyleSheet("color: #F8FAFC; font-size: 20px; font-weight: 700;")
        layout.addWidget(header)

        # ── Scraper Settings ─────────────────────────────────────────────────
        scraper_box = QGroupBox("Scraper Behaviour")
        scraper_form = QFormLayout(scraper_box)
        scraper_form.setSpacing(12)

        self._headless_cb = QCheckBox("Run browser headless (recommended)")
        self._headless_cb.setChecked(settings.HEADLESS)
        self._headless_cb.setStyleSheet("color: #CBD5E1; font-size: 13px;")
        scraper_form.addRow("", self._headless_cb)

        self._delay_spin = QSpinBox()
        self._delay_spin.setRange(200, 5000)
        self._delay_spin.setValue(settings.REQUEST_DELAY_MS)
        self._delay_spin.setSuffix(" ms")
        scraper_form.addRow(QLabel("  Request delay:"), self._delay_spin)

        self._max_results_spin = QSpinBox()
        self._max_results_spin.setRange(10, 500)
        self._max_results_spin.setValue(settings.MAX_RESULTS)
        scraper_form.addRow(QLabel("  Default max results:"), self._max_results_spin)

        layout.addWidget(scraper_box)

        # ── Proxy ────────────────────────────────────────────────────────────
        proxy_box = QGroupBox("Proxy Configuration  (optional)")
        proxy_form = QFormLayout(proxy_box)
        proxy_form.setSpacing(12)
        self._proxy_input = QLineEdit()
        self._proxy_input.setPlaceholderText("http://user:pass@host:port")
        self._proxy_input.setText(settings.PROXY_URL)
        proxy_form.addRow(QLabel("  Proxy URL:"), self._proxy_input)
        layout.addWidget(proxy_box)

        # ── License Info ─────────────────────────────────────────────────────
        lic_box = QGroupBox("License & Account")
        lic_layout = QVBoxLayout(lic_box)

        key_lbl = QLabel(f"License Key: <b style='color:#38BDF8; font-family:monospace;'>{settings.LICENSE_KEY or 'Not activated'}</b>")
        key_lbl.setStyleSheet("color:#CBD5E1; font-size:12px;")
        key_lbl.setTextFormat(Qt.TextFormat.RichText)
        lic_layout.addWidget(key_lbl)

        api_lbl = QLabel(f"API Endpoint: <span style='color:#64748B;'>{settings.API_BASE_URL}</span>")
        api_lbl.setStyleSheet("color:#CBD5E1; font-size:12px;")
        api_lbl.setTextFormat(Qt.TextFormat.RichText)
        lic_layout.addWidget(api_lbl)

        layout.addWidget(lic_box)

        # Save button
        save_btn = QPushButton("💾  Save Settings")
        save_btn.setObjectName("action_btn")
        save_btn.setFixedWidth(200)
        save_btn.clicked.connect(self._save)
        layout.addWidget(save_btn, alignment=Qt.AlignmentFlag.AlignLeft)

        layout.addStretch()

    def _save(self):
        settings.HEADLESS = self._headless_cb.isChecked()
        settings.REQUEST_DELAY_MS = self._delay_spin.value()
        settings.MAX_RESULTS = self._max_results_spin.value()
        settings.PROXY_URL = self._proxy_input.text().strip()

        # Persist to .env
        env_lines = []
        try:
            with open(".env", "r") as f:
                env_lines = f.readlines()
        except FileNotFoundError:
            pass

        updates = {
            "HEADLESS": str(settings.HEADLESS).lower(),
            "REQUEST_DELAY_MS": str(settings.REQUEST_DELAY_MS),
            "MAX_RESULTS": str(settings.MAX_RESULTS),
            "PROXY_URL": settings.PROXY_URL,
        }
        written = set()
        new_lines = []
        for line in env_lines:
            key = line.split("=")[0].strip()
            if key in updates:
                new_lines.append(f"{key}={updates[key]}\n")
                written.add(key)
            else:
                new_lines.append(line)
        for key, val in updates.items():
            if key not in written:
                new_lines.append(f"{key}={val}\n")
        with open(".env", "w") as f:
            f.writelines(new_lines)

        self._main.set_status("Settings saved")
