"""Hashtag feed scraper page."""
import os
from PySide6.QtWidgets import (
    QWidget, QVBoxLayout, QHBoxLayout, QLabel, QLineEdit,
    QPushButton, QTableWidget, QTableWidgetItem, QHeaderView,
    QGroupBox, QComboBox, QSpinBox, QAbstractItemView, QFileDialog,
)
from app.workers.scraper_worker import HashtagWorker
from app.exports.exporter import export
from app.ui.pages.profile_page import PAGE_STYLE


class HashtagPage(QWidget):
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

        header = QLabel("#  Hashtag Feed Scraper")
        header.setStyleSheet("color: #F8FAFC; font-size: 20px; font-weight: 700;")
        layout.addWidget(header)

        sub = QLabel("Scrape the latest videos from any TikTok hashtag feed.")
        sub.setStyleSheet("color: #64748B; font-size: 13px;")
        layout.addWidget(sub)

        controls = QGroupBox("Hashtag Parameters")
        ctrl_layout = QHBoxLayout(controls)
        ctrl_layout.setSpacing(12)

        self._hashtag_input = QLineEdit()
        self._hashtag_input.setPlaceholderText("#hashtag  (e.g. fyp, viral, coding)")
        self._hashtag_input.returnPressed.connect(self._start_scrape)
        ctrl_layout.addWidget(self._hashtag_input, 2)

        max_lbl = QLabel("Max videos:")
        max_lbl.setStyleSheet("color:#94A3B8; font-size:12px;")
        ctrl_layout.addWidget(max_lbl)

        self._max_spin = QSpinBox()
        self._max_spin.setRange(6, 100)
        self._max_spin.setValue(30)
        ctrl_layout.addWidget(self._max_spin)

        self._scrape_btn = QPushButton("⚡  Scrape Hashtag")
        self._scrape_btn.setObjectName("action_btn")
        self._scrape_btn.clicked.connect(self._start_scrape)
        ctrl_layout.addWidget(self._scrape_btn)
        layout.addWidget(controls)

        toolbar = QHBoxLayout()
        self._result_count = QLabel("")
        self._result_count.setStyleSheet("color:#38BDF8; font-size:12px;")
        toolbar.addWidget(QLabel("Results"))
        toolbar.addWidget(self._result_count)
        toolbar.addStretch()

        self._export_fmt = QComboBox()
        self._export_fmt.addItems(["CSV", "JSON", "Excel"])
        self._export_fmt.setFixedWidth(90)
        toolbar.addWidget(self._export_fmt)
        export_btn = QPushButton("⬇  Export")
        export_btn.setObjectName("export_btn")
        export_btn.clicked.connect(self._export)
        toolbar.addWidget(export_btn)
        layout.addLayout(toolbar)

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
        hashtag = self._hashtag_input.text().strip().lstrip("#")
        if not hashtag:
            self._main.set_status("Please enter a hashtag")
            return
        if self._worker and self._worker.isRunning():
            self._worker.stop()
        self._scrape_btn.setEnabled(False)
        self._scrape_btn.setText("⏳  Scraping…")
        self._results = []
        self._table.setRowCount(0)

        self._worker = HashtagWorker(hashtag, max_count=self._max_spin.value())
        self._worker.result.connect(self._on_result)
        self._worker.progress.connect(lambda c, t: self._main.set_progress(c, t))
        self._worker.status_msg.connect(self._main.set_status)
        self._worker.error.connect(lambda e: self._main.set_status(f"Error: {e}"))
        self._worker.finished.connect(self._on_done)
        self._worker.start()

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
            self._table.setItem(i, 8, QTableWidgetItem(str(row.get("hashtags", ""))[:60]))
        self._result_count.setText(f"({len(rows)} videos)")

    def _on_done(self):
        self._scrape_btn.setEnabled(True)
        self._scrape_btn.setText("⚡  Scrape Hashtag")
        self._main.hide_progress()
        self._main.set_status(f"Done — {len(self._results)} videos")

    def _export(self):
        if not self._results:
            return
        folder = QFileDialog.getExistingDirectory(self, "Select Export Folder", os.path.expanduser("~"))
        if not folder:
            return
        path = export(self._results, folder, self._export_fmt.currentText().lower(), prefix="tiktok_hashtag")
        self._main.set_status(f"Exported → {path}")
