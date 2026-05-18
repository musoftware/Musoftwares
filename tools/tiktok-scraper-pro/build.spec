# -*- mode: python ; coding: utf-8 -*-
# PyInstaller build spec for TikTok Scraper Pro
# Build: pyinstaller build.spec

import sys
from PyInstaller.utils.hooks import collect_all, collect_data_files

block_cipher = None

# Collect playwright binaries and data
playwright_datas, playwright_binaries, playwright_hiddenimports = collect_all('playwright')
pyside6_datas = collect_data_files('PySide6')

a = Analysis(
    ['main.py'],
    pathex=['.'],
    binaries=playwright_binaries,
    datas=[
        ('assets', 'assets'),
        ('.env.example', '.'),
        *playwright_datas,
        *pyside6_datas,
    ],
    hiddenimports=[
        'playwright', 'playwright.async_api',
        'PySide6.QtWidgets', 'PySide6.QtCore', 'PySide6.QtGui',
        'openpyxl', 'loguru', 'httpx', 'pydantic', 'pydantic_settings',
        'app.ui.pages.profile_page', 'app.ui.pages.hashtag_page',
        'app.ui.pages.keyword_page', 'app.ui.pages.settings_page',
        *playwright_hiddenimports,
    ],
    hookspath=[],
    noarchive=False,
    optimize=0,
)

pyz = PYZ(a.pure, a.zipped_data, cipher=block_cipher)

exe = EXE(
    pyz,
    a.scripts,
    a.binaries,
    a.datas,
    [],
    name='TikTokScraperPro',
    debug=False,
    bootloader_ignore_signals=False,
    strip=False,
    upx=True,
    console=False,
    icon='assets/icon.ico',
    onefile=True,
)
