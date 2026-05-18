# iSAAS Python Desktop Tool Template

The **master template** for all downloadable tools in the iSAAS marketplace.
Clone this repository to build a new tool in minutes.

## Stack

| Layer | Library |
|-------|---------|
| Desktop UI | PySide6 6.7+ |
| HTTP / API | httpx (async) |
| Config | pydantic-settings |
| Logging | loguru |
| Local DB | aiosqlite (SQLite) |
| Packaging | PyInstaller |

## Quick Start

```bash
# 1. Clone this template
git clone https://github.com/your-org/isass-tool-template.git my-new-tool
cd my-new-tool

# 2. Install dependencies
pip install -r requirements.txt

# 3. Configure
cp .env.example .env
# Edit .env: set APP_NAME, TOOL_SLUG, API_BASE_URL

# 4. Run
python main.py

# 5. Build executable
pyinstaller build.spec
# Output: dist/My_Tool.exe
```

## Architecture

```
tools-template/
├── app/
│   ├── core/
│   │   ├── config.py      ← pydantic Settings (reads .env)
│   │   └── logger.py      ← loguru setup
│   ├── api/
│   │   ├── client.py      ← async httpx API client
│   │   ├── license.py     ← activate / check / heartbeat
│   │   └── updater.py     ← version check + download + restart
│   ├── auth/
│   │   └── device_id.py   ← hardware fingerprint (CPU + MAC)
│   ├── ui/
│   │   ├── login_screen.py ← login + license activation
│   │   └── main_window.py  ← sidebar shell + update banner
│   ├── workers/
│   │   └── base_worker.py  ← QThread base with signals
│   └── modules/           ← add your tool pages here
│       └── (your_module.py)
└── main.py                ← entry point
```

## Adding a New Tool Page

1. Create `app/modules/your_feature.py` — a `QWidget` subclass.
2. Register it in `main.py → build_main_window()`:
   ```python
   from app.modules.your_feature import YourFeaturePage
   window.register_page("feature", "Feature Name", YourFeaturePage(), is_first=True)
   ```

## License Flow

```
User launches app
    ↓
Existing token + key in .env?
    ↓ Yes → MainWindow (background license check)
    ↓ No  → LoginScreen
                ↓ Login → save token
                ↓ Enter license key → activate device
                ↓ Success → MainWindow
```

## Building for Distribution

```bash
pyinstaller build.spec
```

Edit `build.spec` to set `APP_NAME`, `APP_VERSION`, and `ICON` before building.
The output is a **single .exe** (Windows) or **.app** (macOS) with no external dependencies.
Upload the built file to the iSAAS admin panel under the tool's version management.
