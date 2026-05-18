"""
Profile Scraper Page — scrape a TikTok user profile + their video list.
"""
import os
from PySide6.QtWidgets import (
    QWidget, QVBoxLayout, QHBoxLayout, QLabel, QLineEdit,
    QPushButton, QTableWidget, QTableWidgetItem, QHeaderView,
    QGroupBox, QComboBox, QSpinBox, QFrame, QFileDialog,
    QSplitter, QAbstractItemView, QCheckBox,
)
from PySide6.QtCore import Qt, QSize
from PySide6.QtGui import QFont

from app.workers.scraper_worker import ProfileWorker
from app.exports.exporter import export

PAGE_STYLE = """
QWidget { background-color: #0B0F19; color: #CBD5E1; }
QGroupBox {
    border: 1px solid #1E293B;
    border-radius: 12px;
    margin-top: 16px;
    padding: 16px;
    color: #94A3B8;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}
QGroupBox::title {
    subcontrol-origin: margin;
    left: 12px;
    padding: 0 4px;
}
QLineEdit, QSpinBox, QComboBox {
    background: #111827;
    border: 1px solid #1E293B;
    border-radius: 8px;
    padding: 8px 12px;
    color: #E2E8F0;
    font-size: 13px;
}
QLineEdit:focus, QSpinBox:focus, QComboBox:focus {
    border: 1px solid #38BDF8;
}
QPushButton#action_btn {
    background: qlineargradient(x1:0,y1:0,x2:1,y2:0, stop:0 #0EA5E9, stop:1 #6366F1);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 24px;
    font-weight: 600;
    font-size: 13px;
}
QPushButton#action_btn:hover { background: qlineargradient(x1:0,y1:0,x2:1,y2:0, stop:0 #38BDF8, stop:1 #818CF8); }
QPushButton#action_btn:disabled { background: #1E293B; color: #475569; }
QPushButton#export_btn {
    background: #1E293B;
    color: #38BDF8;
    border: 1px solid #38BDF8;
    border-radius: 8px;
    padding: 8px 20px;
    font-weight: 600;
    font-size: 12px;
}
QPushButton#export_btn:hover { background: rgba(56,189,248,0.1); }
QTableWidget {
    background: #111827;
    border: 1px solid #1E293B;
    border-radius: 12px;
    gridline-color: #1E293B;
    color: #CBD5E1;
    font-size: 12px;
}
QTableWidget::item { padding: 6px 8px; }
QTableWidget::item:selected { background: rgba(56,189,248,0.15); color: #38BDF8; }
QHeaderView::section {
    background: #0F172A;
    color: #64748B;
    padding: 8px;
    border: none;
    border-bottom: 1px solid #1E293B;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
}
"""


class ProfileCard(QFrame):
    """Displays scraped profile info."""
    def __init__(self, parent=None):
        super().__init__(parent)
        self.setStyleSheet("""
            QFrame {
                background: #111827;
                border: 1px solid #1E293B;
                border-radius: 12px;
                padding: 16px;
            }
        """)
        self._layout = QVBoxLayout(self)
        self._lbl = QLabel("No profile loaded")
        self._lbl.setStyleSheet("color: #64748B; font-size: 12px;")
        self._lbl.setWordWrap(True)
        self._layout.addWidget(self._lbl)

    def update_profile(self, data: dict):
        v = data.get
        text = (
            f"<b style='color:#F8FAFC;font-size:15px;'>@{v('username', '')}</b>"
            f"  <span style='color:#64748B;font-size:11px;'>{v('nickname', '')}</span>"
            f"{'  ✓' if v('verified') else ''}<br><br>"
            f"<span style='color:#94A3B8;font-size:12px;'>{v('bio','') or 'No bio'}</span><br><br>"
            f"<table style='font-size:12px;'>"
            f"<tr>"
            f"<td style='padding-right:20px;'><b style='color:#38BDF8;'>{v('followers',0):,}</b><br>"
            f"<span style='color:#64748B;'>Followers</span></td>"
            f"<td style='padding-right:20px;'><b style='color:#38BDF8;'>{v('following',0):,}</b><br>"
            f"<span style='color:#64748B;'>Following</span></td>"
            f"<td style='padding-right:20px;'><b style='color:#38BDF8;'>{v('likes',0):,}</b><br>"
            f"<span style='color:#64748B;'>Total Likes</span></td>"
            f"<td><b style='color:#38BDF8;'>{v('videos',0):,}</b><br>"
            f"<span style='color:#64748B;'>Videos</span></td>"
            f"</tr></table>"
        )
        self._lbl.setText(text)


class ProfilePage(QWidget):
    def __init__(self, main_win, parent=None):
        super().__init__(parent)
        self._main = main_win
        self._worker = None
        self._results: list[dict] = []
        self.setStyleSheet(PAGE_STYLE)
        self._build_ui()

    def _build_ui(self):
        layout = QVBoxLayout(self)
        layout.setContentsMargins(24, 20, 24, 20)
        layout.setSpacing(16)

        # Page header
        header = QLabel("👤  Profile Scraper")
        header.setStyleSheet("color: #F8FAFC; font-size: 20px; font-weight: 700;")
        layout.addWidget(header)

        sub = QLabel("Enter a TikTok username to scrape profile info and video list.")
        sub.setStyleSheet("color: #64748B; font-size: 13px;")
        layout.addWidget(sub)

        # Controls
        controls = QGroupBox("Search Parameters")
        ctrl_layout = QHBoxLayout(controls)
        ctrl_layout.setSpacing(12)

        self._username_input = QLineEdit()
        self._username_input.setPlaceholderText("@username  (e.g. charlidamelio)")
        self._username_input.returnPressed.connect(self._start_scrape)
        ctrl_layout.addWidget(self._username_input, 2)

        max_lbl = QLabel("Max videos:")
        max_lbl.setStyleSheet("color:#94A3B8; font-size:12px;")
        ctrl_layout.addWidget(max_lbl)

        self._max_spin = QSpinBox()
        self._max_spin.setRange(6, 200)
        self._max_spin.setValue(30)
        self._max_spin.setSingleStep(6)
        ctrl_layout.addWidget(self._max_spin)

        self._scrape_btn = QPushButton("⚡  Scrape Profile")
        self._scrape_btn.setObjectName("action_btn")
        self._scrape_btn.clicked.connect(self._start_scrape)
        ctrl_layout.addWidget(self._scrape_btn)

        layout.addWidget(controls)

        # Profile card
        self._profile_card = ProfileCard()
        layout.addWidget(self._profile_card)

        # Results toolbar
        toolbar = QHBoxLayout()
        result_lbl_wrap = QLabel("Video Results")
        result_lbl_wrap.setStyleSheet("color:#94A3B8; font-size:12px; font-weight:600;")
        self._result_count = QLabel("")
        self._result_count.setStyleSheet("color:#38BDF8; font-size:12px;")
        toolbar.addWidget(result_lbl_wrap)
        toolbar.addWidget(self._result_count)
        toolbar.addStretch()

        # Export
        self._export_fmt = QComboBox()
        self._export_fmt.addItems(["CSV", "JSON", "Excel"])
        self._export_fmt.setFixedWidth(90)
        toolbar.addWidget(self._export_fmt)

        export_btn = QPushButton("⬇  Export")
        export_btn.setObjectName("export_btn")
        export_btn.clicked.connect(self._export)
        toolbar.addWidget(export_btn)
        layout.addLayout(toolbar)

        # Results table
        cols = ["Author", "Description", "Likes", "Comments", "Shares", "Plays", "Engagement%", "Duration", "Hashtags"]
        self._table = QTableWidget(0, len(cols))
        self._table.setHorizontalHeaderLabels(cols)
        self._table.horizontalHeader().setSectionResizeMode(1, QHeaderView.ResizeMode.Stretch)
        self._table.setSelectionBehavior(QAbstractItemView.SelectionBehavior.SelectRows)
        self._table.setEditTriggers(QAbstractItemView.EditTrigger.NoEditTriggers)
        self._table.setAlternatingRowColors(True)
        self._table.verticalHeader().setVisible(False)
        layout.addWidget(self._table, 1)

    def _start_scrape(self):
        username = self._username_input.text().strip().lstrip("@")
        if not username:
            self._main.set_status("Please enter a username")
            return
        if self._worker and self._worker.isRunning():
            self._worker.stop()
            self._worker.quit()

        self._scrape_btn.setEnabled(False)
        self._scrape_btn.setText("⏳  Scraping…")
        self._results = []
        self._table.setRowCount(0)

        self._worker = ProfileWorker(username, max_videos=self._max_spin.value())
        self._worker.profile_ready.connect(self._on_profile)
        self._worker.result.connect(self._on_result)
        self._worker.progress.connect(lambda c, t: self._main.set_progress(c, t))
        self._worker.status_msg.connect(self._main.set_status)
        self._worker.error.connect(self._on_error)
        self._worker.finished.connect(self._on_done)
        self._worker.start()

    def _on_profile(self, data: dict):
        self._profile_card.update_profile(data)

    def _on_result(self, rows: list):
        self._results = rows
        self._table.setRowCount(len(rows))
        for i, row in enumerate(rows):
            self._table.setItem(i, 0, QTableWidgetItem(str(row.get("author", ""))))
            self._table.setItem(i, 1, QTableWidgetItem(str(row.get("description", ""))[:80]))
            self._table.setItem(i, 2, QTableWidgetItem(f"{row.get('likes', 0):,}"))
            self._table.setItem(i, 3, QTableWidgetItem(f"{row.get('comments', 0):,}"))
            self._table.setItem(i, 4, QTableWidgetItem(f"{row.get('shares', 0):,}"))
            self._table.setItem(i, 5, QTableWidgetItem(f"{row.get('plays', 0):,}"))
            self._table.setItem(i, 6, QTableWidgetItem(f"{row.get('engagement_rate', 0)}%"))
            self._table.setItem(i, 7, QTableWidgetItem(f"{row.get('duration_sec', 0)}s"))
            self._table.setItem(i, 8, QTableWidgetItem(str(row.get("hashtags", ""))))
        self._result_count.setText(f"({len(rows)} videos)")

    def _on_error(self, msg: str):
        self._main.set_status(f"Error: {msg}")
        self._result_count.setText("(error)")

    def _on_done(self):
        self._scrape_btn.setEnabled(True)
        self._scrape_btn.setText("⚡  Scrape Profile")
        self._main.hide_progress()
        self._main.set_status(f"Done — {len(self._results)} videos collected")

    def _export(self):
        if not self._results:
            self._main.set_status("Nothing to export yet")
            return
        folder = QFileDialog.getExistingDirectory(self, "Select Export Folder", os.path.expanduser("~"))
        if not folder:
            return
        fmt = self._export_fmt.currentText().lower()
        path = export(self._results, folder, fmt, prefix="tiktok_profile")
        self._main.set_status(f"Exported to {path}")
