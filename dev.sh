#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/docker"

if [ ! -f .env ]; then
    echo "docker/.env manquant : copie .env.exemple vers .env et renseigne des identifiants (différents de ceux de prod)." >&2
    exit 1
fi

echo "Arrêt des containers existants (si présents)..."
docker compose down

echo "Build et démarrage des containers (serveur web + base de données, code monté en bind mount depuis src/)..."
docker compose up -d --build
echo "Dev démarrée sur http://localhost:8080"
