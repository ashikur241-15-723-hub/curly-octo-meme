#!/bin/sh
# Start the app in your browser. Usage: ./serve.sh [port]
# Default port 8766 avoids clashes with 5173.

cd "$(dirname "$0")"
PORT="${1:-8766}"
echo ""
echo "  Money Calculator — open this link in your browser:"
echo "  http://127.0.0.1:${PORT}"
echo ""
echo "  Press Ctrl+C to stop the server."
echo ""
exec python3 -m http.server "$PORT"
