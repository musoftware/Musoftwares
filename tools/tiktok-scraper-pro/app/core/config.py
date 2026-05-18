"""
TikTok Scraper Pro — Configuration
Tool slug must match the marketplace entry exactly.
"""
from pydantic_settings import BaseSettings, SettingsConfigDict
from pydantic import Field


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", env_file_encoding="utf-8", extra="ignore")

    # ── Identity ────────────────────────────────────────────────────────────
    APP_NAME: str    = "TikTok Scraper Pro"
    APP_VERSION: str = "2.1.4"
    TOOL_SLUG: str   = "tiktok-scraper-pro"

    # ── iSAAS API ───────────────────────────────────────────────────────────
    API_BASE_URL: str = "https://isass.app/api/tools"
    API_TIMEOUT: int  = 15
    API_RETRY_ATTEMPTS: int = 3

    # ── Session ─────────────────────────────────────────────────────────────
    API_TOKEN: str   = ""
    LICENSE_KEY: str = ""

    # ── Scraper ─────────────────────────────────────────────────────────────
    MAX_RESULTS: int        = 50
    REQUEST_DELAY_MS: int   = 800     # ms between requests
    MAX_RETRIES: int        = 3
    HEADLESS: bool          = True    # run playwright headless
    PROXY_URL: str          = ""      # optional: http://user:pass@host:port

    # ── Offline Grace ───────────────────────────────────────────────────────
    OFFLINE_GRACE_DAYS: int = 3

    # ── Logging ─────────────────────────────────────────────────────────────
    LOG_LEVEL: str  = "INFO"
    LOG_FILE: str   = "logs/scraper.log"


settings = Settings()
