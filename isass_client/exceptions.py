"""
Exceptions raised by the isass_client library.
All exceptions inherit from ISASSError for easy catch-all handling.
"""


class ISASSError(Exception):
    """Base class for all isass_client errors."""


class APIError(ISASSError):
    """HTTP API returned an error response."""
    def __init__(self, message: str, status_code: int = 0, raw: dict | None = None):
        super().__init__(message)
        self.status_code = status_code
        self.raw = raw or {}


class AuthenticationError(APIError):
    """Invalid credentials or expired token (401)."""


class LicenseError(APIError):
    """License is invalid, suspended, or revoked (403)."""


class DeviceCapacityError(LicenseError):
    """The license has reached its maximum device limit."""
    def __init__(self, message: str, max_devices: int = 0, active_devices: int = 0):
        super().__init__(message, status_code=403)
        self.max_devices = max_devices
        self.active_devices = active_devices


class NetworkError(ISASSError):
    """Cannot reach the server (offline, DNS, timeout)."""


class UpdateError(ISASSError):
    """An error occurred while downloading or applying an update."""
