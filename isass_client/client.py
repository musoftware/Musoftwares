"""
ISASSClient — the main entry point for the isass_client library.
All methods are async. Use with `async with ISASSClient(...) as client:`.

Example:
    async with ISASSClient("https://isass.app/api/tools") as client:
        result = await client.login("user@example.com", "password")
        client.set_token(result.token)
        check = await client.check_license("LK-XXXX", fingerprint)
        if not check.valid:
            raise RuntimeError("License invalid")
"""

import asyncio
from typing import Callable, Optional

import httpx

from isass_client.exceptions import (
    APIError, AuthenticationError, DeviceCapacityError,
    LicenseError, NetworkError,
)
from isass_client.models import (
    LoginResult, LicenseActivation, LicenseCheck, UpdateInfo, ToolInfo,
)


class ISASSClient:
    """
    Async HTTP client for the iSAAS desktop API.

    Args:
        api_url:     Base URL e.g. https://isass.app/api/tools
        token:       Optional Bearer token (set after login)
        tool_slug:   Tool identifier (set in config)
        app_version: Current app version string
        timeout:     HTTP request timeout in seconds
    """

    def __init__(
        self,
        api_url: str,
        token: str = "",
        tool_slug: str = "",
        app_version: str = "1.0.0",
        timeout: int = 15,
    ):
        self._api_url    = api_url.rstrip("/")
        self._token      = token
        self._tool_slug  = tool_slug
        self._app_version = app_version
        self._timeout    = timeout
        self._client: httpx.AsyncClient | None = None

    # ─── Context Manager ─────────────────────────────────────────────────────

    async def __aenter__(self) -> "ISASSClient":
        self._client = httpx.AsyncClient(
            base_url=self._api_url,
            timeout=self._timeout,
            follow_redirects=True,
            headers=self._headers(),
        )
        return self

    async def __aexit__(self, *_):
        if self._client:
            await self._client.aclose()

    def set_token(self, token: str) -> None:
        """Update the Bearer token after a successful login."""
        self._token = token
        if self._client:
            self._client.headers["Authorization"] = f"Bearer {token}"

    def _headers(self) -> dict:
        h = {
            "Accept":          "application/json",
            "Content-Type":    "application/json",
            "X-App-Version":   self._app_version,
            "X-Tool-Slug":     self._tool_slug,
        }
        if self._token:
            h["Authorization"] = f"Bearer {self._token}"
        return h

    # ─── Authentication ───────────────────────────────────────────────────────

    async def login(self, email: str, password: str) -> LoginResult:
        """
        Exchange email + password for a Sanctum token.
        Returns a LoginResult with the token and user info.
        Raises AuthenticationError on bad credentials.
        """
        data = await self._post("/auth/login", {"email": email, "password": password})
        return LoginResult(
            token   = data["token"],
            user_id = data.get("user", {}).get("id", 0),
            name    = data.get("user", {}).get("name", ""),
            email   = data.get("user", {}).get("email", email),
        )

    async def logout(self) -> None:
        """Revoke the current Sanctum token."""
        await self._post("/auth/logout", {})

    async def me(self) -> dict:
        """Return the authenticated user's profile."""
        return await self._get("/auth/me")

    # ─── License ──────────────────────────────────────────────────────────────

    async def activate_license(
        self,
        license_key: str,
        hardware_fingerprint: str,
        device_name: str,
        os: str,
    ) -> LicenseActivation:
        """
        Register this machine against a license key.
        Raises DeviceCapacityError if all slots are filled.
        Raises LicenseError if the key is invalid/suspended.
        """
        data = await self._post("/license/activate", {
            "license_key":          license_key,
            "hardware_fingerprint": hardware_fingerprint,
            "device_name":          device_name,
            "os":                   os,
            "app_version":          self._app_version,
        })
        return LicenseActivation.from_dict(data)

    async def check_license(
        self,
        license_key: str,
        hardware_fingerprint: str,
    ) -> LicenseCheck:
        """
        Validate on startup — confirm device is still authorized.
        Raises LicenseError if the license is no longer valid.
        """
        data = await self._post("/license/check", {
            "license_key":          license_key,
            "hardware_fingerprint": hardware_fingerprint,
            "app_version":          self._app_version,
        })
        return LicenseCheck.from_dict(data)

    async def heartbeat(
        self,
        license_key: str,
        hardware_fingerprint: str,
    ) -> bool:
        """
        Background keep-alive ping (every 30 minutes).
        Returns True if the license is still alive.
        Returns False if revoked (caller should notify user).
        NetworkError is swallowed — returns True for offline grace.
        """
        try:
            data = await self._post("/license/heartbeat", {
                "license_key":          license_key,
                "hardware_fingerprint": hardware_fingerprint,
                "app_version":          self._app_version,
            })
            return bool(data.get("alive", True))
        except NetworkError:
            return True  # Offline grace period

    # ─── Updates ──────────────────────────────────────────────────────────────

    async def check_for_update(self, current_version: str) -> UpdateInfo:
        """
        Check if a newer version exists.
        Returns UpdateInfo — caller decides whether to prompt the user.
        """
        data = await self._get(
            f"/{self._tool_slug}/update-check",
            params={"current_version": current_version},
        )
        return UpdateInfo.from_dict(data)

    async def get_releases(self) -> list[dict]:
        """Return all published release notes for this tool."""
        data = await self._get(f"/{self._tool_slug}/releases")
        return data.get("releases", [])

    async def download_update(
        self,
        download_url: str,
        dest_path: str,
        progress_callback: Optional[Callable[[int, int], None]] = None,
    ) -> str:
        """
        Stream download to dest_path.
        progress_callback(downloaded_bytes, total_bytes) is called per chunk.
        Returns the absolute path of the saved file.
        """
        from isass_client.exceptions import UpdateError
        import os

        async with httpx.AsyncClient(timeout=300, follow_redirects=True) as http:
            try:
                async with http.stream("GET", download_url) as resp:
                    resp.raise_for_status()
                    total = int(resp.headers.get("content-length", 0))
                    downloaded = 0

                    os.makedirs(os.path.dirname(os.path.abspath(dest_path)), exist_ok=True)
                    with open(dest_path, "wb") as f:
                        async for chunk in resp.aiter_bytes(65536):
                            f.write(chunk)
                            downloaded += len(chunk)
                            if progress_callback:
                                progress_callback(downloaded, total)
            except httpx.HTTPStatusError as e:
                raise UpdateError(f"Download failed: HTTP {e.response.status_code}") from e
            except Exception as e:
                raise UpdateError(f"Download failed: {e}") from e

        return os.path.abspath(dest_path)

    # ─── Internal HTTP helpers ────────────────────────────────────────────────

    async def _get(self, path: str, params: dict | None = None) -> dict:
        return await self._request("GET", path, params=params)

    async def _post(self, path: str, json: dict) -> dict:
        return await self._request("POST", path, json=json)

    async def _request(
        self, method: str, path: str,
        params: dict | None = None,
        json: dict | None = None,
    ) -> dict:
        assert self._client, "Not initialized — use `async with ISASSClient(...) as client:`"

        try:
            resp = await self._client.request(method, path, params=params, json=json)
        except (httpx.ConnectError, httpx.TimeoutException) as e:
            raise NetworkError(f"Network error: {e}") from e

        if resp.status_code == 401:
            raise AuthenticationError("Invalid credentials or expired token.", 401)

        if resp.status_code == 403:
            body = self._parse_body(resp)
            msg  = body.get("message", "Access denied")
            if "device" in msg.lower() and "maximum" in msg.lower():
                raise DeviceCapacityError(msg,
                    max_devices    = body.get("max_devices", 0),
                    active_devices = body.get("active_devices", 0),
                )
            raise LicenseError(msg, 403, body)

        if not resp.is_success:
            body = self._parse_body(resp)
            raise APIError(body.get("message", resp.text or "API error"), resp.status_code, body)

        return self._parse_body(resp)

    def _parse_body(self, resp: httpx.Response) -> dict:
        ct = resp.headers.get("content-type", "")
        if "application/json" in ct:
            try:
                return resp.json()
            except Exception:
                pass
        return {"message": resp.text}
