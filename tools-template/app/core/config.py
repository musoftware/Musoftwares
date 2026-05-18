"""
iSAAS Tool Template — Application Configuration
=================================================
All settings are read from:
1. Environment variables (highest priority)
2. .env file in the app root
3. Default values below

Never hardcode API credentials — use .env only.
"""

from pydantic_settings import BaseSettings, SettingsConfigDict
from pydantic import Field


class Settings(BaseSettings):
    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        extra="ignore",
    )

    # ─── Identity ────────────────────────────────────────────────────────────
    APP_NAME: str = Field(default="iSAAS Tool", description="Tool display name")
    APP_VERSION: str = Field(default="1.0.0", description="Current app version")
    TOOL_SLUG: str = Field(default="my-tool", description="Tool slug on the marketplace")

    # ─── Backend API ──────────────────────────────────────────────────────────
    API_BASE_URL: str = Field(
        default="https://isass.app/api/tools",
        description="iSAAS backend API base URL",
    )
    API_TIMEOUT: int = Field(default=15, description="HTTP request timeout in seconds")
    API_RETRY_ATTEMPTS: int = Field(default=3, description="Number of retry attempts")

    # ─── License / Auth ───────────────────────────────────────────────────────
    LICENSE_KEY: str = Field(default="", description="User's license key (saved after first activation)")
    API_TOKEN: str = Field(default="", description="Sanctum token after login")

    # ─── Update System ────────────────────────────────────────────────────────
    AUTO_UPDATE_CHECK: bool = Field(default=True, description="Check for updates on startup")
    UPDATE_CHECK_INTERVAL_HOURS: int = Field(default=6, description="Hours between update checks")

    # ─── Offline Grace ────────────────────────────────────────────────────────
    OFFLINE_GRACE_DAYS: int = Field(default=3, description="Days the app works without internet")

    # ─── Logging ─────────────────────────────────────────────────────────────
    LOG_LEVEL: str = Field(default="INFO", description="Log level: DEBUG|INFO|WARNING|ERROR")
    LOG_FILE: str = Field(default="logs/app.log", description="Log file path")

    # ─── Database ─────────────────────────────────────────────────────────────
    DB_PATH: str = Field(default="data/local.db", description="SQLite database file path")


# Singleton — import this everywhere
settings = Settings()
