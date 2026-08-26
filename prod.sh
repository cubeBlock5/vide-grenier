#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/docker-prod"

if [ ! -f .env ]; then
    echo "docker-prod/.env manquant : copie .env.exemple vers .env et renseigne des identifiants (différents de ceux du dev)." >&2
    exit 1
fi

mkdir -p sql
echo "Récupération de sql/import.sql depuis main sur GitHub..."
curl -fSL --progress-bar -o sql/import.sql \
    https://raw.githubusercontent.com/cubeBlock5/vide-grenier/main/src/sql/import.sql
echo "OK."

echo "Arrêt des containers existants (si présents)..."
docker compose down

echo "Build des containers (clone de main + composer install, sans cache pour être sûr de récupérer le dernier commit)..."
docker compose build --no-cache

echo "Démarrage des containers..."
docker compose up -d
echo "Prod démarrée sur http://localhost:8081"
