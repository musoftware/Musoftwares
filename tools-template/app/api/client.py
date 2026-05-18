"""
iSAAS API Client — base httpx async client for all desktop tools.
Handles auth headers, retries, timeouts, and error parsing.
"""

import httpx
from loguru import logger
from app.core.config import settings


class APIError(Exception):
    def __init__(self, message: str, status_code: int = 0):
        super().__init__(message)
        self.status_code = status_code


class APIClient:
    """
    Async httpx client — singleton per tool session.
    Usage:
        async with APIClient() as client:
            data = await client.get("/auth/me")
    """

    def __init__(self, token: str | None = None):
        self._token = token or settings.API_TOKEN
        self._client: httpx.AsyncClient | None = None

    async def __aenter__(self):
        self._client = httpx.AsyncClient(
            base_url=settings.API_BASE_URL,
            timeout=settings.API_TIMEOUT,
            headers=self._build_headers(),
            follow_redirects=True,
        )
        return self

    async def __aexit__(self, *args):
        if self._client:
            await self._client.aclose()

    def _build_headers(self) -> dict:
        headers = {
            "Accept": "application/json",
            "Content-Type": "application/json",
            "X-App-Version": settings.APP_VERSION,
            "X-Tool-Slug": settings.TOOL_SLUG,
        }
        if self._token:
            headers["Authorization"] = f"Bearer {self._token}"
        return headers

    def set_token(self, token: str) -> None:
        self._token = token
        if self._client:
            self._client.headers["Authorization"] = f"Bearer {token}"

    async def get(self, path: str, params: dict | None = None) -> dict:
        return await self._request("GET", path, params=params)

    async def post(self, path: str, json: dict | None = None) -> dict:
        return await self._request("POST", path, json=json)

    async def _request(
        self, method: str, path: str,
        params: dict | None = None, json: dict | None = None
    ) -> dict:
        assert self._client, "Client not initialized — use `async with APIClient()`"
        for attempt in range(1, settings.API_RETRY_ATTEMPTS + 1):
            try:
                response = await self._client.request(
                    method, path, params=params, json=json
                )
                if response.is_success:
                    return response.json()

                error_body = response.json() if response.headers.get("content-type", "").startswith("application/json") else {}
                message = error_body.get("message") or response.text or "API error"
                raise APIError(message, status_code=response.status_code)

            except httpx.TimeoutException:
                logger.warning(f"Request timeout ({attempt}/{settings.API_RETRY_ATTEMPTS}): {method} {path}")
                if attempt == settings.API_RETRY_ATTEMPTS:
                    raise APIError("Request timed out. Check your internet connection.")
            except httpx.ConnectError:
                logger.warning(f"Connection error ({attempt}/{settings.API_RETRY_ATTEMPTS}): {method} {path}")
                if attempt == settings.API_RETRY_ATTEMPTS:
                    raise APIError("Cannot connect to server. Running in offline mode.")
        raise APIError("Max retries exceeded.")
