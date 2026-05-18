"""
Auto-Updater — checks for new versions and downloads them.
Mirrors the Discord/VSCode update flow:
1. Background check on startup
2. Notify user if update available
3. Download in background with progress
4. Prompt restart
"""

import asyncio
import os
import subprocess
import sys
import tempfile
from pathlib import Path

import httpx
from loguru import logger
from app.api.client import APIClient, APIError
from app.core.config import settings


class UpdateInfo:
    def __init__(self, data: dict):
        self.update_available: bool = data.get("update_available", False)
        self.latest_version: str    = data.get("latest_version", settings.APP_VERSION)
        self.current_version: str   = data.get("current_version", settings.APP_VERSION)
        self.changelog: str         = data.get("changelog", "")
        self.file_size: str         = data.get("file_size", "")
        self.checksum: str          = data.get("checksum", "")
        self.download_url: str      = data.get("download_url", "")
        self.released_at: str       = data.get("released_at", "")


class UpdaterService:
    def __init__(self, client: APIClient):
        self.client = client

    async def check_for_update(self) -> UpdateInfo:
        """
        Ask the server if a newer version exists.
        Returns UpdateInfo — caller decides whether to notify the user.
        """
        try:
            data = await self.client.get(
                f"/{settings.TOOL_SLUG}/update-check",
                params={"current_version": settings.APP_VERSION}
            )
            info = UpdateInfo(data)
            if info.update_available:
                logger.info(f"Update available: {info.current_version} → {info.latest_version}")
            else:
                logger.debug(f"Already on latest: {settings.APP_VERSION}")
            return info
        except APIError as e:
            logger.warning(f"Update check failed: {e}")
            return UpdateInfo({"update_available": False})

    async def download_update(
        self,
        update_info: UpdateInfo,
        progress_callback=None,
    ) -> Path | None:
        """
        Download the installer to a temp file.
        progress_callback(downloaded_bytes, total_bytes) — optional.
        Returns the path to the downloaded file.
        """
        if not update_info.download_url:
            logger.error("No download URL in update info")
            return None

        suffix = ".exe" if sys.platform == "win32" else ".zip"
        tmp_file = Path(tempfile.mktemp(suffix=suffix))

        logger.info(f"Downloading update to {tmp_file}")

        try:
            async with httpx.AsyncClient(follow_redirects=True, timeout=300) as http:
                async with http.stream("GET", update_info.download_url) as response:
                    response.raise_for_status()
                    total = int(response.headers.get("content-length", 0))
                    downloaded = 0

                    with open(tmp_file, "wb") as f:
                        async for chunk in response.aiter_bytes(chunk_size=65536):
                            f.write(chunk)
                            downloaded += len(chunk)
                            if progress_callback:
                                progress_callback(downloaded, total)

            logger.success(f"Download complete: {tmp_file} ({downloaded} bytes)")
            return tmp_file

        except Exception as e:
            logger.error(f"Download failed: {e}")
            if tmp_file.exists():
                tmp_file.unlink()
            return None

    def apply_update_and_restart(self, installer_path: Path) -> None:
        """
        Launch the installer and exit the current process.
        On Windows: run the .exe installer silently.
        On macOS/Linux: open the .zip and restart.
        """
        logger.info(f"Applying update from {installer_path}")
        try:
            if sys.platform == "win32":
                subprocess.Popen([str(installer_path), "/SILENT"], shell=False)
            else:
                subprocess.Popen(["open", str(installer_path)])
        except Exception as e:
            logger.error(f"Failed to launch installer: {e}")
            return

        logger.info("Update launched — exiting current instance")
        sys.exit(0)
