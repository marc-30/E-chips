#!/bin/sh
cd "$(dirname "$0")"
PORT=5173
( sleep 1; (command -v xdg-open >/dev/null && xdg-open http://localhost:$PORT) || open http://localhost:$PORT ) >/dev/null 2>&1 &
# PHP si dispo : permet de tester la commande / le paiement (dossier api/). Sinon simple serveur statique.
if command -v php >/dev/null; then php -S localhost:$PORT; elif command -v python3 >/dev/null; then python3 -m http.server $PORT; else npx --yes serve . -l $PORT; fi
