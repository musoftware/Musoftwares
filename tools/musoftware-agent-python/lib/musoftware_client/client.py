"""
MusoftwareClient — Async HTTP client for all Python agent plugins.
Renamed from ISASSClient for brand consistency.

Usage:
    import asyncio
    from lib.musoftware_client import MusoftwareClient

    async def main():
        async with MusoftwareClient(token="...", tool_slug="snapchat") as c:
            ok = await c.heartbeat(license_key, fingerprint)
"""

import asyncio
from typing import Callable, Optional
import httpx

from .exceptions import (
    APIError, AuthenticationError, DeviceCapacityError,
    LicenseError, NetworkError,
)
from .models import LoginResult, LicenseActivation, LicenseCheck, UpdateInfo


class MusoftwareClient:
    """
    Async HTTP client for the Musoftware platform API.
    Used by all Python agent plugins for auth, license, heartbeat, updates.
    """

    BASE_PATH = "/api/tools"

    def __init__(
        self,
        platform_url: str = "https://musoftwares.com",
        token: str = "",
        tool_slug: str = "",
        app_version: str = "1.0.0",
        timeout: int = 15,
    ):
        self._base = platform_url.rstrip("/") + self.BASE_PATH
        self._token = token
        self._tool_slug = tool_slug
        self._app_version = app_version
        self._timeout = timeout
        self._client: Optional[httpx.AsyncClient] = None

    async def __aenter__(self) -> "MusoftwareClient":
        self._client = httpx.AsyncClient(
            base_url=self._base,
            timeout=self._timeout,
            follow_redirects=True,
            headers=self._headers(),
        )
        return self

    async def __aexit__(self, *_):
        if self._client:
            await self._client.aclose()

    def set_token(self, token: str):
        self._token = token
        if self._client:
            self._client.headers["Authorization"] = f"Bearer {token}"

    def _headers(self) -> dict:
        h = {
            "Accept":        "application/json",
            "Content-Type":  "application/json",
            "X-App-Version": self._app_version,
            "X-Tool-Slug":   self._tool_slug,
            "X-Agent-Type":  "python",
        }
        if self._token:
            h["Authorization"] = f"Bearer {self._token}"
        return h

    # ── Authentication ────────────────────────────────────────────────────────

    async def login(self, email: str, password: str) -> LoginResult:
        data = await self._post("/auth/login", {"email": email, "password": password})
        return LoginResult(
            token=data["token"],
            user_id=data.get("user", {}).get("id", 0),
            name=data.get("user", {}).get("name", ""),
            email=data.get("user", {}).get("email", email),
        )

    async def logout(self):
        await self._post("/auth/logout", {})

    async def me(self) -> dict:
        return await self._get("/auth/me")

    # ── License ───────────────────────────────────────────────────────────────

    async def activate_license(
        self,
        license_key: str,
        hardware_fingerprint: str,
        device_name: str,
        os: str,
    ) -> LicenseActivation:
        data = await self._post("/license/activate", {
            "license_key":          license_key,
            "hardware_fingerprint": hardware_fingerprint,
            "device_name":          device_name,
            "os":                   os,
            "app_version":          self._app_version,
        })
        return LicenseActivation.from_dict(data)

    async def check_license(self, license_key: str, hardware_fingerprint: str) -> LicenseCheck:
        data = await self._post("/license/check", {
            "license_key":          license_key,
            "hardware_fingerprint": hardware_fingerprint,
            "app_version":          self._app_version,
        })
        return LicenseCheck.from_dict(data)

    async def heartbeat(self, license_key: str, hardware_fingerprint: str) -> bool:
        """Returns True if license alive. Swallows network errors (offline grace)."""
        try:
            data = await self._post("/license/heartbeat", {
                "license_key":          license_key,
                "hardware_fingerprint": hardware_fingerprint,
                "app_version":          self._app_version,
            })
            return bool(data.get("alive", True))
        except NetworkError:
            return True  # offline grace period

    # ── Updates ───────────────────────────────────────────────────────────────

    async def check_for_update(self, current_version: str) -> UpdateInfo:
        data = await self._get(
            f"/{self._tool_slug}/update-check",
            params={"current_version": current_version},
        )
        return UpdateInfo.from_dict(data)

    async def get_releases(self) -> list:
        data = await self._get(f"/{self._tool_slug}/releases")
        return data.get("releases", [])

    # ── Agent plugin sync (called by agent itself) ────────────────────────────

    async def get_agent_plugins(self, agent_type: str = "python") -> list:
        """Returns list of subscribed plugins for this agent type."""
        data = await self._get("/agent/plugins", params={"agent": agent_type})
        return data.get("plugins", [])

    # ── Internal helpers ──────────────────────────────────────────────────────

    async def _get(self, path: str, params: dict = None) -> dict:
        return await self._request("GET", path, params=params)

    async def _post(self, path: str, json: dict) -> dict:
        return await self._request("POST", path, json=json)

    async def _request(self, method: str, path: str, params=None, json=None) -> dict:
        assert self._client, "Use `async with MusoftwareClient(...) as c:`"
        try:
            resp = await self._client.request(method, path, params=params, json=json)
        except (httpx.ConnectError, httpx.TimeoutException) as e:
            raise NetworkError(f"Network error: {e}") from e

        if resp.status_code == 401:
            raise AuthenticationError("Invalid or expired token.", 401)
        if resp.status_code == 403:
            body = self._parse(resp)
            raise LicenseError(body.get("message", "Forbidden"), 403, body)
        if not resp.is_success:
            body = self._parse(resp)
            raise APIError(body.get("message", resp.text or "API error"), resp.status_code, body)

        return self._parse(resp)

    def _parse(self, resp: httpx.Response) -> dict:
        ct = resp.headers.get("content-type", "")
        if "application/json" in ct:
            try: return resp.json()
            except Exception: pass
        return {"message": resp.text}
