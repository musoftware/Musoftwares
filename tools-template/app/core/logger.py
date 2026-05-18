"""
Logger — loguru setup for iSAAS desktop tools.
Logs to both stderr (colored) and a rotating file.
"""

import sys
from pathlib import Path
from loguru import logger
from app.core.config import settings


def setup_logger() -> None:
    logger.remove()

    # Console sink — colored, human-readable
    logger.add(
        sys.stderr,
        level=settings.LOG_LEVEL,
        format="<green>{time:HH:mm:ss}</green> | <level>{level:<8}</level> | <cyan>{name}</cyan>:<cyan>{line}</cyan> — <level>{message}</level>",
        colorize=True,
    )

    # File sink — rotating, machine-parseable
    log_path = Path(settings.LOG_FILE)
    log_path.parent.mkdir(parents=True, exist_ok=True)

    logger.add(
        log_path,
        level=settings.LOG_LEVEL,
        format="{time:YYYY-MM-DD HH:mm:ss} | {level:<8} | {name}:{line} — {message}",
        rotation="10 MB",
        retention="14 days",
        compression="zip",
        enqueue=True,  # thread-safe
    )

    logger.info(f"Logger initialized — {settings.APP_NAME} v{settings.APP_VERSION}")


setup_logger()
