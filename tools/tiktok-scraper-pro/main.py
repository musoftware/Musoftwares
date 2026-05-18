#!/usr/bin/env python3
"""
TikTok Scraper Pro — Entry Point
"""
import sys
import os
import multiprocessing

# Required for Windows EXE packaging
multiprocessing.freeze_support()

# Ensure the tool root is on sys.path for imports
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from PySide6.QtWidgets import QApplication, QStackedWidget
from PySide6.QtCore import Qt
from PySide6.QtGui import QFont

from app.ui.login_screen import LoginScreen
from app.ui.main_window import MainWindow
from app.core.config import settings


def main():
    app = QApplication(sys.argv)
    app.setApplicationName(settings.APP_NAME)
    app.setApplicationVersion(settings.APP_VERSION)
    app.setOrganizationName("Musoftware")

    # Set default font
    font = QFont("Inter", 10)
    app.setFont(font)

    # High-DPI support
    app.setAttribute(Qt.ApplicationAttribute.AA_UseHighDpiPixmaps, True)

    stack = QStackedWidget()
    stack.setWindowTitle(f"{settings.APP_NAME} v{settings.APP_VERSION}")
    stack.resize(1100, 700)

    login = LoginScreen()
    stack.addWidget(login)

    def on_login_success(user_info: dict):
        main_win = MainWindow(user_info)
        stack.addWidget(main_win)
        stack.setCurrentWidget(main_win)
        stack.showMaximized()

    login.login_success.connect(on_login_success)
    stack.setCurrentWidget(login)
    stack.show()

    sys.exit(app.exec())


if __name__ == "__main__":
    main()
