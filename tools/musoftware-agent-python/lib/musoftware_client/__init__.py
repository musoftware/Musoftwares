"""
musoftware_client — Shared Python HTTP client library
Used by all Python agent plugins to talk to the platform API.

Usage in a plugin worker:
    from lib.musoftware_client import MusoftwareClient
    client = MusoftwareClient(token=os.environ["MUSOFTWARE_TOKEN"])
    ok = client.heartbeat(license_key, fingerprint)
"""
from .client import MusoftwareClient
from .models import LoginResult, LicenseActivation, LicenseCheck, UpdateInfo
from .exceptions import APIError, AuthenticationError, LicenseError, DeviceCapacityError, NetworkError

__all__ = [
    "MusoftwareClient",
    "LoginResult", "LicenseActivation", "LicenseCheck", "UpdateInfo",
    "APIError", "AuthenticationError", "LicenseError", "DeviceCapacityError", "NetworkError",
]
