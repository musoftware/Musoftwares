# -*- mode: python ; coding: utf-8 -*-
"""
PyInstaller build spec for iSAAS desktop tools.
Build: pyinstaller build.spec
Output: dist/ToolName.exe (Windows) or dist/ToolName.app (macOS)
"""

import sys
from pathlib import Path

APP_NAME    = "iSAAS Tool"       # ← Change per tool
APP_VERSION = "1.0.0"            # ← Bump on release
ICON        = "assets/icon.ico"  # ← Tool icon

block_cipher = None

a = Analysis(
    ["main.py"],
    pathex=[str(Path(".").resolve())],
    binaries=[],
    datas=[
        ("assets/",     "assets/"),
        (".env.example", "."),
    ],
    hiddenimports=[
        "pydantic",
        "pydantic_settings",
        "loguru",
        "httpx",
        "aiosqlite",
        "PySide6.QtCore",
        "PySide6.QtWidgets",
        "PySide6.QtGui",
    ],
    hookspath=[],
    hooksconfig={},
    runtime_hooks=[],
    excludes=["tkinter", "matplotlib", "numpy", "pandas"],
    win_no_prefer_redirects=False,
    win_private_assemblies=False,
    cipher=block_cipher,
    noarchive=False,
)

pyz = PYZ(a.pure, a.zipped_data, cipher=block_cipher)

exe = EXE(
    pyz,
    a.scripts,
    a.binaries,
    a.zipfiles,
    a.datas,
    [],
    name=APP_NAME.replace(" ", "_"),
    debug=False,
    bootloader_ignore_signals=False,
    strip=False,
    upx=True,
    upx_exclude=[],
    runtime_tmpdir=None,
    console=False,             # No console window for GUI app
    disable_windowed_traceback=False,
    argv_emulation=False,
    target_arch=None,
    codesign_identity=None,
    entitlements_file=None,
    icon=ICON if Path(ICON).exists() else None,
    version_file=None,
)
