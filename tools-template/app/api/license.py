"""
License API — device activation, validation, and heartbeat.
Called on every app launch and periodically during use.
"""

from loguru import logger
from app.api.client import APIClient, APIError
from app.auth.device_id import get_hardware_fingerprint, get_device_name, get_os_name
from app.core.config import settings


class LicenseService:
    def __init__(self, client: APIClient):
        self.client = client

    async def activate(self, license_key: str) -> dict:
        """
        Register this machine against the license key.
        Returns: { success, device_id, expires_at, max_devices }
        """
        fingerprint = get_hardware_fingerprint()
        logger.info(f"Activating device — fingerprint: {fingerprint[:16]}...")
        return await self.client.post("/license/activate", json={
            "license_key":          license_key,
            "hardware_fingerprint": fingerprint,
            "device_name":          get_device_name(),
            "os":                   get_os_name(),
            "app_version":          settings.APP_VERSION,
        })

    async def check(self, license_key: str) -> dict:
        """
        Fast launch check — confirms the device is still valid.
        Returns: { valid, expires_at, grace_days }
        """
        fingerprint = get_hardware_fingerprint()
        try:
            result = await self.client.post("/license/check", json={
                "license_key":          license_key,
                "hardware_fingerprint": fingerprint,
                "app_version":          settings.APP_VERSION,
            })
            logger.debug(f"License check: valid={result.get('valid')}")
            return result
        except APIError as e:
            logger.warning(f"License check failed: {e}")
            raise

    async def heartbeat(self, license_key: str) -> bool:
        """
        Background heartbeat — called every 30 minutes.
        Returns True if still alive, False if license revoked.
        """
        fingerprint = get_hardware_fingerprint()
        try:
            result = await self.client.post("/license/heartbeat", json={
                "license_key":          license_key,
                "hardware_fingerprint": fingerprint,
                "app_version":          settings.APP_VERSION,
            })
            return result.get("alive", False)
        except APIError:
            logger.debug("Heartbeat failed — offline or network issue")
            return True  # Allow grace period
