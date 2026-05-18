"""
isass_client — iSAAS Desktop Tool API Library
=============================================
A standalone Python package that provides typed wrappers around
the iSAAS marketplace API. Import this into any desktop tool instead
of duplicating HTTP logic.

Usage:
    pip install isass-client   # or: pip install -e ./isass_client

    from isass_client import ISASSClient, LicenseError

    async with ISASSClient(api_url="https://isass.app/api/tools") as client:
        token = await client.login("user@example.com", "password")
        client.set_token(token)
        await client.activate_license("XXXX-XXXX-XXXX-XXXX", fingerprint, device_name, os)
"""

from isass_client.client import ISASSClient
from isass_client.exceptions import (
    ISASSError,
    APIError,
    AuthenticationError,
    LicenseError,
    DeviceCapacityError,
    NetworkError,
    UpdateError,
)
from isass_client.models import (
    LoginResult,
    LicenseActivation,
    LicenseCheck,
    UpdateInfo,
    ToolInfo,
)

__version__ = "1.0.0"
__all__ = [
    "ISASSClient",
    "ISASSError", "APIError", "AuthenticationError",
    "LicenseError", "DeviceCapacityError", "NetworkError", "UpdateError",
    "LoginResult", "LicenseActivation", "LicenseCheck", "UpdateInfo", "ToolInfo",
]
