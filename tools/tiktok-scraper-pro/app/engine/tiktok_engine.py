"""
TikTok Scraper Engine
=====================
Uses Playwright to intercept TikTok's internal JSON API calls.
No API key required. Works by loading TikTok pages in a headless browser
and capturing the XHR responses that contain structured post/user data.

Key endpoints captured:
  - /api/user/detail/?uniqueId=...   → profile info
  - /api/post/item_list/             → user video list
  - /api/challenge/item_list/        → hashtag videos
  - /api/search/item/full/           → keyword search
"""

import asyncio
import json
import time
from typing import AsyncGenerator, Optional
from loguru import logger


# ─── Data Models ─────────────────────────────────────────────────────────────

class TikTokUser:
    def __init__(self, data: dict):
        user = data.get("userInfo", {}).get("user", data.get("user", {}))
        stats = data.get("userInfo", {}).get("stats", data.get("stats", {}))
        self.id            = user.get("id", "")
        self.username      = user.get("uniqueId", "")
        self.nickname      = user.get("nickname", "")
        self.bio           = user.get("signature", "")
        self.avatar_url    = user.get("avatarMedium", "")
        self.followers     = stats.get("followerCount", 0)
        self.following     = stats.get("followingCount", 0)
        self.likes         = stats.get("heartCount", 0)
        self.video_count   = stats.get("videoCount", 0)
        self.verified      = user.get("verified", False)
        self.private       = user.get("privateAccount", False)

    def to_dict(self) -> dict:
        return {
            "id": self.id, "username": self.username, "nickname": self.nickname,
            "bio": self.bio, "followers": self.followers, "following": self.following,
            "likes": self.likes, "videos": self.video_count,
            "verified": self.verified, "private": self.private,
            "avatar_url": self.avatar_url,
        }


class TikTokVideo:
    def __init__(self, item: dict):
        desc    = item.get("desc", "")
        author  = item.get("author", {})
        stats   = item.get("stats", {})
        video   = item.get("video", {})
        self.id            = item.get("id", "")
        self.description   = desc[:200] if desc else ""
        self.author        = author.get("uniqueId", "")
        self.author_name   = author.get("nickname", "")
        self.likes         = stats.get("diggCount", 0)
        self.comments      = stats.get("commentCount", 0)
        self.shares        = stats.get("shareCount", 0)
        self.plays         = stats.get("playCount", 0)
        self.duration      = video.get("duration", 0)
        self.width         = video.get("width", 0)
        self.height        = video.get("height", 0)
        self.cover_url     = video.get("cover", "")
        self.download_url  = video.get("downloadAddr", "")
        self.create_time   = item.get("createTime", 0)
        hashtags           = [c["hashtagName"] for c in item.get("challenges", []) if "hashtagName" in c]
        self.hashtags      = hashtags

    def to_dict(self) -> dict:
        return {
            "id": self.id,
            "description": self.description,
            "author": self.author,
            "author_name": self.author_name,
            "likes": self.likes,
            "comments": self.comments,
            "shares": self.shares,
            "plays": self.plays,
            "duration_sec": self.duration,
            "hashtags": ", ".join(self.hashtags),
            "cover_url": self.cover_url,
            "download_url": self.download_url,
            "created_at": self.create_time,
            "engagement_rate": round(
                (self.likes + self.comments + self.shares) / max(self.plays, 1) * 100, 2
            ),
        }


# ─── Scraper ─────────────────────────────────────────────────────────────────

class TikTokEngine:
    """
    Playwright-based TikTok scraper.
    Intercepts internal API JSON responses from a headless browser session.
    """

    def __init__(self, headless: bool = True, proxy_url: str = "", delay_ms: int = 800):
        self.headless   = headless
        self.proxy_url  = proxy_url
        self.delay_ms   = delay_ms
        self._playwright = None
        self._browser    = None
        self._context    = None
        self._captured: list[dict] = []

    async def start(self):
        from playwright.async_api import async_playwright
        self._pw_ctx = async_playwright()
        self._playwright = await self._pw_ctx.__aenter__()
        launch_opts: dict = {"headless": self.headless}
        if self.proxy_url:
            launch_opts["proxy"] = {"server": self.proxy_url}
        self._browser = await self._playwright.chromium.launch(**launch_opts)
        self._context = await self._browser.new_context(
            user_agent=(
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                "AppleWebKit/537.36 (KHTML, like Gecko) "
                "Chrome/124.0.0.0 Safari/537.36"
            ),
            viewport={"width": 1280, "height": 720},
            locale="en-US",
        )
        logger.info("TikTok engine started (headless={headless})", headless=self.headless)

    async def stop(self):
        if self._browser:
            await self._browser.close()
        if self._playwright:
            await self._pw_ctx.__aexit__(None, None, None)
        logger.info("TikTok engine stopped")

    def _intercept_handler(self, route, request):
        """Pass through but capture JSON API responses."""
        asyncio.ensure_future(route.continue_())

    async def _capture_json(self, page, url: str, wait_for: str, timeout_ms: int = 15000) -> list[dict]:
        """Load a URL and capture all JSON API responses that match our patterns."""
        captured = []

        async def handle_response(response):
            if any(pat in response.url for pat in [
                "/api/user/detail", "/api/post/item_list",
                "/api/challenge/item_list", "/api/search/item/full",
            ]):
                try:
                    data = await response.json()
                    captured.append({"url": response.url, "data": data})
                except Exception:
                    pass

        page.on("response", handle_response)
        await page.goto(url, wait_until="domcontentloaded", timeout=timeout_ms)
        await page.wait_for_timeout(self.delay_ms)
        return captured

    # ─── Public API ──────────────────────────────────────────────────────────

    async def scrape_profile(self, username: str) -> Optional[TikTokUser]:
        """Fetch a user's profile information."""
        page = await self._context.new_page()
        try:
            url  = f"https://www.tiktok.com/@{username}"
            captured = await self._capture_json(page, url, "userInfo")
            for c in captured:
                if "/api/user/detail" in c["url"] and "userInfo" in c["data"]:
                    return TikTokUser(c["data"])
            # Fallback: parse from page HTML meta tags
            return await self._parse_profile_from_page(page, username)
        except Exception as e:
            logger.warning(f"Profile scrape failed for @{username}: {e}")
            return None
        finally:
            await page.close()

    async def _parse_profile_from_page(self, page, username: str) -> Optional[TikTokUser]:
        """Fallback parser that reads meta tags and JSON-LD from the page."""
        try:
            script = await page.query_selector('script#SIGI_STATE')
            if script:
                raw = await script.inner_text()
                data = json.loads(raw)
                users = data.get("UserPage", {}).get("uniqueIdToUserId", {})
                user_module = data.get("UserModule", {}).get("users", {})
                if username in user_module:
                    return TikTokUser({"user": user_module[username]})
        except Exception as e:
            logger.debug(f"Fallback profile parse error: {e}")
        return None

    async def scrape_user_videos(
        self, username: str, max_count: int = 30,
        progress_cb=None,
    ) -> list[TikTokVideo]:
        """Scrape a user's video list."""
        page = await self._context.new_page()
        videos = []
        try:
            url = f"https://www.tiktok.com/@{username}"
            await page.goto(url, wait_until="domcontentloaded", timeout=15000)
            await page.wait_for_timeout(2000)

            # Scroll to trigger lazy loading and API calls
            collected_ids: set[str] = set()
            for scroll_round in range(min(max_count // 6 + 1, 10)):
                captured_responses: list[dict] = []

                async def handle_response(response):
                    if "/api/post/item_list" in response.url:
                        try:
                            data = await response.json()
                            captured_responses.append(data)
                        except Exception:
                            pass

                page.on("response", handle_response)
                await page.evaluate("window.scrollBy(0, window.innerHeight * 3)")
                await page.wait_for_timeout(self.delay_ms)
                page.remove_listener("response", handle_response)

                for response_data in captured_responses:
                    for item in response_data.get("itemList", []):
                        vid_id = item.get("id", "")
                        if vid_id not in collected_ids:
                            collected_ids.add(vid_id)
                            videos.append(TikTokVideo(item))
                            if progress_cb:
                                progress_cb(len(videos), max_count)

                if len(videos) >= max_count or not captured_responses:
                    break

        except Exception as e:
            logger.warning(f"Video scrape error for @{username}: {e}")
        finally:
            await page.close()

        return videos[:max_count]

    async def scrape_hashtag(
        self, hashtag: str, max_count: int = 30,
        progress_cb=None,
    ) -> list[TikTokVideo]:
        """Scrape videos from a hashtag feed."""
        hashtag = hashtag.lstrip("#")
        page = await self._context.new_page()
        videos = []
        try:
            url = f"https://www.tiktok.com/tag/{hashtag}"
            await page.goto(url, wait_until="domcontentloaded", timeout=15000)
            await page.wait_for_timeout(2000)

            collected_ids: set[str] = set()
            for _ in range(min(max_count // 6 + 1, 8)):
                captured: list[dict] = []

                async def on_response(response):
                    if "/api/challenge/item_list" in response.url:
                        try:
                            captured.append(await response.json())
                        except Exception:
                            pass

                page.on("response", on_response)
                await page.evaluate("window.scrollBy(0, window.innerHeight * 3)")
                await page.wait_for_timeout(self.delay_ms)
                page.remove_listener("response", on_response)

                for d in captured:
                    for item in d.get("itemList", []):
                        vid_id = item.get("id", "")
                        if vid_id not in collected_ids:
                            collected_ids.add(vid_id)
                            videos.append(TikTokVideo(item))
                            if progress_cb:
                                progress_cb(len(videos), max_count)

                if len(videos) >= max_count or not captured:
                    break
        except Exception as e:
            logger.warning(f"Hashtag scrape error for #{hashtag}: {e}")
        finally:
            await page.close()

        return videos[:max_count]

    async def scrape_keyword(
        self, keyword: str, max_count: int = 20,
        progress_cb=None,
    ) -> list[TikTokVideo]:
        """Scrape videos by keyword search."""
        from urllib.parse import quote
        page = await self._context.new_page()
        videos = []
        try:
            url = f"https://www.tiktok.com/search/video?q={quote(keyword)}"
            captured: list[dict] = []

            async def on_response(response):
                if "/api/search/item/full" in response.url:
                    try:
                        captured.append(await response.json())
                    except Exception:
                        pass

            page.on("response", on_response)
            await page.goto(url, wait_until="domcontentloaded", timeout=15000)
            await page.wait_for_timeout(3000)
            page.remove_listener("response", on_response)

            for d in captured:
                for item_data in d.get("item_list", []):
                    videos.append(TikTokVideo(item_data))
                    if progress_cb:
                        progress_cb(len(videos), max_count)
                    if len(videos) >= max_count:
                        break

        except Exception as e:
            logger.warning(f"Keyword search error for '{keyword}': {e}")
        finally:
            await page.close()

        return videos[:max_count]
