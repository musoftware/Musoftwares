"""
QThread scraper workers — runs the async scraper engine off the GUI thread.
"""
import asyncio
from typing import Callable, Optional

from PySide6.QtCore import QThread, Signal

from app.engine.tiktok_engine import TikTokEngine, TikTokVideo, TikTokUser
from app.core.config import settings


class ScraperWorker(QThread):
    """
    Base scraper worker. Subclass and implement `_run_task()`.
    Signals:
        progress(current, total)  — emitted during scraping
        result(list)              — emitted with row dicts on completion
        profile_ready(dict)       — emitted when profile info is fetched
        error(str)                — emitted on failure
        finished()                — always emitted at the end
    """
    progress     = Signal(int, int)
    result       = Signal(list)
    profile_ready = Signal(dict)
    error        = Signal(str)
    status_msg   = Signal(str)

    def __init__(self, parent=None):
        super().__init__(parent)
        self._engine: Optional[TikTokEngine] = None
        self._stopped = False

    def stop(self):
        self._stopped = True

    def run(self):
        loop = asyncio.new_event_loop()
        asyncio.set_event_loop(loop)
        try:
            loop.run_until_complete(self._run_async())
        except Exception as e:
            self.error.emit(str(e))
        finally:
            loop.close()

    async def _run_async(self):
        self._engine = TikTokEngine(
            headless=settings.HEADLESS,
            proxy_url=settings.PROXY_URL,
            delay_ms=settings.REQUEST_DELAY_MS,
        )
        try:
            self.status_msg.emit("Starting browser engine…")
            await self._engine.start()
            await self._run_task()
        except Exception as e:
            self.error.emit(str(e))
        finally:
            self.status_msg.emit("Stopping engine…")
            await self._engine.stop()

    async def _run_task(self):
        raise NotImplementedError


class ProfileWorker(ScraperWorker):
    """Fetch a single user profile + their video list."""
    def __init__(self, username: str, max_videos: int = 30, parent=None):
        super().__init__(parent)
        self._username   = username
        self._max_videos = max_videos

    async def _run_task(self):
        self.status_msg.emit(f"Fetching profile @{self._username}…")
        user = await self._engine.scrape_profile(self._username)
        if user:
            self.profile_ready.emit(user.to_dict())
        else:
            self.error.emit(f"Could not find user @{self._username}")
            return

        self.status_msg.emit(f"Fetching videos for @{self._username}…")
        def on_progress(cur, total):
            self.progress.emit(cur, total)
            self.status_msg.emit(f"Collected {cur}/{total} videos…")

        videos = await self._engine.scrape_user_videos(
            self._username, self._max_videos, progress_cb=on_progress
        )
        self.result.emit([v.to_dict() for v in videos])


class HashtagWorker(ScraperWorker):
    """Scrape a hashtag feed."""
    def __init__(self, hashtag: str, max_count: int = 30, parent=None):
        super().__init__(parent)
        self._hashtag  = hashtag
        self._max      = max_count

    async def _run_task(self):
        self.status_msg.emit(f"Scraping #{self._hashtag}…")
        def on_progress(cur, total):
            self.progress.emit(cur, total)

        videos = await self._engine.scrape_hashtag(
            self._hashtag, self._max, progress_cb=on_progress
        )
        self.result.emit([v.to_dict() for v in videos])


class KeywordWorker(ScraperWorker):
    """Search by keyword."""
    def __init__(self, keyword: str, max_count: int = 20, parent=None):
        super().__init__(parent)
        self._keyword = keyword
        self._max     = max_count

    async def _run_task(self):
        self.status_msg.emit(f"Searching TikTok for '{self._keyword}'…")
        def on_progress(cur, total):
            self.progress.emit(cur, total)

        videos = await self._engine.scrape_keyword(
            self._keyword, self._max, progress_cb=on_progress
        )
        self.result.emit([v.to_dict() for v in videos])
