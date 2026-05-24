#!/bin/bash

echo "============================================="
echo "  MuSoftware - Starting Development Servers"
echo "============================================="
echo ""

# Go to the script directory
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$DIR"

# 1. Start Laravel PHP Server
echo "[1/3] Starting Laravel PHP server..."
php artisan serve --env=local &
LARAVEL_PID=$!

# 2. Start Vite
echo "[2/3] Starting Vite (npm run dev)..."
npm run dev &
VITE_PID=$!

# 3. Start Musoftware Runtime agent
echo "[3/3] Starting Musoftware Runtime agent..."
cd ../newmusoftwareTools/musoftware-runtime && npm run dev &
RUNTIME_PID=$!

echo ""
echo "============================================="
echo "  All 3 servers are running in the background"
echo "  Laravel: http://127.0.0.1:8000"
echo "  Vite:    http://127.0.0.1:5174"
echo "  Runtime: http://127.0.0.1:18400"
echo "============================================="
echo "Press Ctrl+C to stop all servers."

# Trap Ctrl+C (SIGINT) and kill all child processes
trap "echo -e '\nStopping servers...'; kill $LARAVEL_PID $VITE_PID $RUNTIME_PID 2>/dev/null; exit" SIGINT SIGTERM

# Wait indefinitely so the script doesn't exit, keeping trap alive
wait
