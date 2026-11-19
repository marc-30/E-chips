#!/bin/sh
cd "$(dirname "$0")"
PORT=5173
( sleep 1; (command -v xdg-open >/dev/null && xdg-open http://localhost:$PORT) || open http://localhost:$PORT ) >/dev/null 2>&1 &
if command -v python3 >/dev/null; then python3 -m http.server $PORT; else npx --yes serve . -l $PORT; fi
