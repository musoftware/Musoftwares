"""
TikTok Scraper Plugin Worker
============================
Python plugin for musoftware-agent-python.

Communicates with the agent via stdout (JSON lines):
  {"type": "log",      "level": "info|warn|error", "message": "..."}
  {"type": "progress", "percent": 0-100, "message": "..."}
  {"type": "result",   "data": {...}}
  {"type": "error",    "message": "..."}

Params (from MUSOFTWARE_PARAMS env):
  {
    "action":     "keyword" | "hashtag" | "profile",
    "query":      "...",
    "max_count":  50,
    "proxy_url":  "",
    "headless":   true
  }
"""

import sys
import os
import json
import asyncio


# ── IPC helpers ───────────────────────────────────────────────────────────────

def emit(type_: str, **kwargs):
    sys.stdout.write(json.dumps({"type": type_, **kwargs}) + "\n")
    sys.stdout.flush()

def log(level: str, msg: str):   emit("log",      level=level, message=msg)
def progress(pct: int, msg=""):  emit("progress",  percent=pct, message=msg)


# ── Main ──────────────────────────────────────────────────────────────────────

async def run():
    log("info", "🎵 TikTok Scraper starting...")

    params     = json.loads(os.environ.get("MUSOFTWARE_PARAMS", "{}"))
    action     = params.get("action", "keyword")
    query      = params.get("query", "")
    max_count  = int(params.get("max_count", 30))
    proxy_url  = params.get("proxy_url", "")
    headless   = params.get("headless", True)

    if not query:
        emit("error", message="No query provided")
        sys.exit(1)

    log("info", f"Action={action} | Query={query!r} | Max={max_count}")

    # Import engine (same package)
    from engine import TikTokEngine

    engine = TikTokEngine(headless=headless, proxy_url=proxy_url)
    await engine.start()

    try:
        videos = []

        if action == "keyword":
            progress(5, f'Searching: "{query}"...')
            def on_prog(cur, total):
                progress(int(5 + cur / max(total, 1) * 90), f"Collected {cur}/{total}")
            videos = await engine.scrape_keyword(query, max_count, progress_cb=on_prog)

        elif action == "hashtag":
            progress(5, f"Scraping #{query}...")
            def on_prog(cur, total):
                progress(int(5 + cur / max(total, 1) * 90), f"Collected {cur}/{total}")
            videos = await engine.scrape_hashtag(query, max_count, progress_cb=on_prog)

        elif action == "profile":
            progress(10, f"Fetching @{query}...")
            user = await engine.scrape_profile(query)
            progress(40, "Fetching videos...")
            def on_prog(cur, total):
                progress(int(40 + cur / max(total, 1) * 55), f"Videos: {cur}/{total}")
            videos = await engine.scrape_user_videos(query, max_count, progress_cb=on_prog)
            progress(100, "Done")
            emit("result", data={
                "profile": user.to_dict() if user else None,
                "videos":  [v.to_dict() for v in videos],
                "count":   len(videos),
            })
            return

        else:
            emit("error", message=f"Unknown action: {action}")
            sys.exit(1)

        progress(100, "Done")
        log("info", f"Scraped {len(videos)} videos")
        emit("result", data={
            "action": action,
            "query":  query,
            "videos": [v.to_dict() for v in videos],
            "count":  len(videos),
        })

    finally:
        await engine.stop()


if __name__ == "__main__":
    try:
        asyncio.run(run())
    except Exception as e:
        emit("error", message=str(e))
        sys.exit(1)
