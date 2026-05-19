"""
Musoftware Python Agent
========================
A general-purpose plugin host for Python tools.
Each tool is a plugin in plugins/ — auto-downloaded after subscription.

Exposes HTTP + WebSocket on 127.0.0.1:18401
  Browser UI → ws://127.0.0.1:18401/ws
  Status poll → GET http://127.0.0.1:18401/status
"""

import asyncio
import json
import logging
import os
import sys
import signal
from pathlib import Path

# Agent constants
AGENT_VERSION = "1.0.0"
AGENT_TYPE    = "python"
DEFAULT_PORT  = 18401

def setup_logging(log_level: str = "INFO") -> logging.Logger:
    log_dir = Path(__file__).parent.parent / "logs"
    log_dir.mkdir(parents=True, exist_ok=True)

    logging.basicConfig(
        level=getattr(logging, log_level.upper(), logging.INFO),
        format="[%(asctime)s] %(levelname)-5s %(message)s",
        datefmt="%H:%M:%S",
        handlers=[
            logging.StreamHandler(sys.stdout),
            logging.FileHandler(log_dir / "agent.log", encoding="utf-8"),
        ],
    )
    return logging.getLogger("musoftware-agent-python")


async def main():
    # Lazy imports after basic setup
    from aiohttp import web
    import aiohttp

    from config   import load_config, save_config
    from plugin_loader import PluginLoader
    from plugin_syncer  import PluginSyncer
    from task_runner    import TaskRunner

    config = load_config()
    logger = setup_logging(config.get("log_level", "INFO"))

    logger.info("╔══════════════════════════════════════════╗")
    logger.info(f"║  Musoftware Python Agent  v{AGENT_VERSION}           ║")
    logger.info(f"║  Listening → http://127.0.0.1:{config['port']}        ║")
    logger.info("╚══════════════════════════════════════════╝")

    # ── Core modules ──────────────────────────────────────────────────────────
    plugin_loader = PluginLoader(config, logger)
    await plugin_loader.load_all()

    runner = TaskRunner(logger)

    # ── WebSocket client registry ──────────────────────────────────────────────
    ws_clients: set = set()

    def broadcast(event: str, data: dict):
        payload = json.dumps({"event": event, "data": data})
        dead = set()
        for ws in ws_clients:
            try:
                asyncio.create_task(ws.send_str(payload))
            except Exception:
                dead.add(ws)
        ws_clients.difference_update(dead)

    # Wire runner events → broadcast
    runner.on_log      = lambda d: broadcast("task.log",      d)
    runner.on_progress = lambda d: broadcast("task.progress", d)
    runner.on_done     = lambda d: broadcast("task.done",     d)
    runner.on_error    = lambda d: broadcast("task.error",    d)

    # ── HTTP routes ────────────────────────────────────────────────────────────
    routes = web.RouteTableDef()

    @routes.get("/status")
    async def status(req):
        return web.json_response({
            "online":      True,
            "agent":       AGENT_TYPE,
            "version":     AGENT_VERSION,
            "plugins":     [
                {"id": p["id"], "name": p["name"], "slug": p.get("tool_slug"), "version": p["version"]}
                for p in plugin_loader.get_all()
            ],
            "activeTasks": runner.get_active_tasks(),
        })

    @routes.get("/plugins")
    async def list_plugins(req):
        return web.json_response({"plugins": plugin_loader.get_all()})

    @routes.post("/plugins/{slug}/run")
    async def run_plugin(req):
        slug   = req.match_info["slug"]
        plugin = plugin_loader.get_by_slug(slug)
        if not plugin:
            return web.json_response({"error": f"Plugin '{slug}' not installed"}, status=404)
        try:
            body   = await req.json() if req.can_read_body else {}
            params = body.get("params", {})
            task_id = await runner.run(plugin, params)
            return web.json_response({"taskId": task_id, "status": "started"})
        except Exception as e:
            return web.json_response({"error": str(e)}, status=500)

    @routes.post("/tasks/{task_id}/stop")
    async def stop_task(req):
        runner.stop(req.match_info["task_id"])
        return web.json_response({"stopped": True})

    @routes.get("/tasks/{task_id}/logs")
    async def get_logs(req):
        return web.json_response({"logs": runner.get_logs(req.match_info["task_id"])})

    @routes.post("/auth")
    async def auth(req):
        body = await req.json()
        token   = body.get("token")
        user_id = body.get("userId")
        if not token or not user_id:
            return web.json_response({"error": "token and userId required"}, status=400)
        save_config({"token": token, "userId": user_id})
        config["token"]  = token
        config["userId"] = user_id
        logger.info(f"Auth configured for user {user_id}")
        if syncer:
            asyncio.create_task(syncer.sync())
        return web.json_response({"ok": True})

    @routes.get("/ws")
    async def websocket_handler(req):
        ws = web.WebSocketResponse()
        await ws.prepare(req)
        ws_clients.add(ws)
        logger.info("Browser WS connected")

        # Greeting
        await ws.send_str(json.dumps({
            "event": "agent.ready",
            "data": {
                "agent":   AGENT_TYPE,
                "version": AGENT_VERSION,
                "plugins": [{"id": p["id"], "slug": p.get("tool_slug")} for p in plugin_loader.get_all()],
            },
        }))

        async for msg in ws:
            if msg.type == aiohttp.WSMsgType.TEXT:
                try:
                    data = json.loads(msg.data)
                    if data.get("type") == "ping":
                        await ws.send_str(json.dumps({"event": "pong"}))
                    elif data.get("type") == "stop":
                        runner.stop(data.get("payload", {}).get("taskId"))
                except Exception:
                    pass
            elif msg.type in (aiohttp.WSMsgType.ERROR, aiohttp.WSMsgType.CLOSE):
                break

        ws_clients.discard(ws)
        logger.info("Browser WS disconnected")
        return ws

    # ── Build app with CORS ────────────────────────────────────────────────────
    app = web.Application()
    app.add_routes(routes)

    # CORS middleware — allow musoftwares.com and localhost
    import re
    @web.middleware
    async def cors_middleware(req, handler):
        origin = req.headers.get("Origin", "")
        allowed = (
            re.match(r"^https?://(.*\.)?musoftware\.com$", origin) or
            re.match(r"^https?://(localhost|127\.0\.0\.1)(:\d+)?$", origin) or
            not origin
        )
        if not allowed:
            return web.Response(status=403, text="CORS blocked")
        resp = await handler(req)
        if origin:
            resp.headers["Access-Control-Allow-Origin"] = origin
            resp.headers["Access-Control-Allow-Credentials"] = "true"
            resp.headers["Access-Control-Allow-Methods"] = "GET,POST,DELETE,OPTIONS"
            resp.headers["Access-Control-Allow-Headers"] = "Content-Type,Authorization"
        return resp

    app._middlewares = (cors_middleware, *app._middlewares)

    # ── Plugin syncer ──────────────────────────────────────────────────────────
    syncer = None
    if config.get("token"):
        syncer = PluginSyncer(config, logger, plugin_loader, broadcast)
        asyncio.create_task(syncer.start())
    else:
        logger.warning("No auth token — open musoftwares.com to connect the agent")

    # ── Start server ───────────────────────────────────────────────────────────
    runner_app = web.AppRunner(app)
    await runner_app.setup()
    site = web.TCPSite(runner_app, "127.0.0.1", config["port"])
    await site.start()
    logger.info(f"Python agent ready — http://127.0.0.1:{config['port']}")

    # ── Run forever ────────────────────────────────────────────────────────────
    stop_event = asyncio.Event()
    loop = asyncio.get_running_loop()

    def _handle_signal():
        logger.info("Shutdown signal received")
        runner.stop_all()
        stop_event.set()

    for sig in (signal.SIGINT, signal.SIGTERM):
        try:
            loop.add_signal_handler(sig, _handle_signal)
        except (NotImplementedError, RuntimeError):
            pass  # Windows doesn't support add_signal_handler for all signals

    await stop_event.wait()
    await runner_app.cleanup()
    logger.info("Python agent stopped.")


if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        print("\nStopped.")
