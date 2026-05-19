"""Typed response models for musoftware_client API calls."""

from dataclasses import dataclass
from typing import Optional


@dataclass
class LoginResult:
    token: str
    user_id: int
    name: str
    email: str


@dataclass
class LicenseActivation:
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
    valid: bool
    expires_at: Optional[str]
    grace_days: int = 3

    @classmethod
    def from_dict(cls, data: dict) -> "LicenseCheck":
        return cls(
            valid      = data.get("valid", False),
            expires_at = data.get("expires_at"),
            grace_days = data.get("grace_days", 3),
        )


@dataclass
class UpdateInfo:
    update_available: bool
    current_version: str
    latest_version: str
    changelog: str = ""
    download_url: str = ""

    @classmethod
    def from_dict(cls, data: dict) -> "UpdateInfo":
        return cls(
            update_available = data.get("update_available", False),
            current_version  = data.get("current_version", ""),
            latest_version   = data.get("latest_version", ""),
            changelog        = data.get("changelog", ""),
            download_url     = data.get("download_url", ""),
        )
