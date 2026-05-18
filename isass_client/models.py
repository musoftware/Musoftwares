"""
Typed response models for all isass_client API calls.
Built with dataclasses for zero-dependency simplicity.
"""

from dataclasses import dataclass, field
from typing import Optional


@dataclass
class LoginResult:
    """Result of a successful ISASSClient.login() call."""
    token: str
    user_id: int
    name: str
    email: str


@dataclass
class LicenseActivation:
    """Result of a successful ISASSClient.activate_license() call."""
    success: bool
    device_id: int
    expires_at: Optional[str]
    max_devices: int
    active_devices: int
    grace_days: int = 3

    @classmethod
    def from_dict(cls, data: dict) -> "LicenseActivation":
        return cls(
            success        = data.get("success", False),
            device_id      = data.get("device_id", 0),
            expires_at     = data.get("expires_at"),
            max_devices    = data.get("max_devices", 1),
            active_devices = data.get("active_devices", 1),
            grace_days     = data.get("grace_days", 3),
        )


@dataclass
class LicenseCheck:
    """Result of ISASSClient.check_license()."""
    valid: bool
    expires_at: Optional[str]
    grace_days: int = 3
    days_remaining: Optional[int] = None

    @classmethod
    def from_dict(cls, data: dict) -> "LicenseCheck":
        return cls(
            valid          = data.get("valid", False),
            expires_at     = data.get("expires_at"),
            grace_days     = data.get("grace_days", 3),
            days_remaining = data.get("days_remaining"),
        )


@dataclass
class UpdateInfo:
    """Result of ISASSClient.check_for_update()."""
    update_available: bool
    current_version: str
    latest_version: str
    changelog: str = ""
    download_url: str = ""
    file_size: str = ""
    checksum: str = ""
    released_at: str = ""

    @classmethod
    def from_dict(cls, data: dict) -> "UpdateInfo":
        return cls(
            update_available = data.get("update_available", False),
            current_version  = data.get("current_version", ""),
            latest_version   = data.get("latest_version", ""),
            changelog        = data.get("changelog", ""),
            download_url     = data.get("download_url", ""),
            file_size        = data.get("file_size", ""),
            checksum         = data.get("checksum", ""),
            released_at      = data.get("released_at", ""),
        )


@dataclass
class ToolInfo:
    """Minimal tool metadata returned by the API."""
    slug: str
    title: str
    current_version: str
    category: str = ""
    is_featured: bool = False
    download_count: int = 0

    @classmethod
    def from_dict(cls, data: dict) -> "ToolInfo":
        return cls(
            slug           = data.get("slug", ""),
            title          = data.get("title", ""),
            current_version= data.get("current_version", "0.0.0"),
            category       = data.get("category", ""),
            is_featured    = data.get("is_featured", False),
            download_count = data.get("download_count", 0),
        )
