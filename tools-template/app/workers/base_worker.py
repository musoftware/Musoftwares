"""
Base Worker — QThread base class for all async background tasks.
Provides standardized signals: progress, result, error, finished.

Usage:
    class MyWorker(BaseWorker):
        def work(self):
            for i in range(10):
                self.update_progress(i * 10, f"Step {i}")
            return {"done": True}

    worker = MyWorker()
    worker.result.connect(on_result)
    worker.error.connect(on_error)
    worker.start()
"""

import asyncio
import traceback
from PySide6.QtCore import QThread, Signal
from loguru import logger


class BaseWorker(QThread):
    # Signals every subclass can use
    progress  = Signal(int, str)   # (0-100, status message)
    result    = Signal(object)     # any return value from work()
    error     = Signal(str)        # error message string
    finished  = Signal()           # always emitted at the end

    def __init__(self, parent=None):
        super().__init__(parent)
        self._cancelled = False

    def cancel(self):
        """Request cancellation — subclass should check self._cancelled periodically."""
        self._cancelled = True
        logger.debug(f"{self.__class__.__name__} cancellation requested")

    # ─── Override this ────────────────────────────────────────────────────────
    def work(self):
        """
        Synchronous work method — called in the background thread.
        Return a value to emit via result signal.
        Use self.update_progress() to report progress.
        Use asyncio.run() for async operations.
        Raise an exception to emit via error signal.
        """
        raise NotImplementedError("Subclass must implement work()")

    # ─── Helpers ──────────────────────────────────────────────────────────────
    def update_progress(self, percent: int, message: str = ""):
        """Emit a progress update safely from the worker thread."""
        self.progress.emit(max(0, min(100, percent)), message)

    async def run_async(self, coro):
        """Convenience wrapper to run a coroutine from a sync context."""
        return asyncio.run(coro)

    # ─── QThread entry point ─────────────────────────────────────────────────
    def run(self):
        logger.debug(f"{self.__class__.__name__} started")
        try:
            value = self.work()
            if not self._cancelled:
                self.result.emit(value)
        except Exception as e:
            logger.error(f"{self.__class__.__name__} error: {e}\n{traceback.format_exc()}")
            self.error.emit(str(e))
        finally:
            self.finished.emit()
            logger.debug(f"{self.__class__.__name__} finished")
