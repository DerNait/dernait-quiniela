#!/usr/bin/env bash
# ==========================================================================
# Despliegue / actualización en el servidor.
# Úsalo desde la raíz del proyecto:  bash deploy/deploy.sh
# Requisitos previos:
#   - .env ya configurado (copiar de .env.production.example).
#   - Los assets compilados (public/build) subidos por rsync DESDE LOCAL.
#     En este server NO se corre `npm run build` (memoria limitada).
# ==========================================================================
set -euo pipefail

cd "$(dirname "$0")/.."

COMPOSE="docker compose -f docker-compose.prod.yml"
export HOST_UID="$(id -u)"
export HOST_GID="$(id -g)"

# Ejecutamos artisan/composer como el dueño real de los archivos (HOST_UID),
# para que puedan escribir vendor/, bootstrap/cache/ y storage/ sin chocar con
# permisos del volumen montado.
APP_EXEC="$COMPOSE exec -T -u ${HOST_UID}:${HOST_GID} -e HOME=/tmp app"

echo "==> 1/6 Trayendo últimos cambios (git pull)"
git config --global --add safe.directory "$PWD" 2>/dev/null || true
git pull --ff-only || echo "   (sin remoto; se omite)"

echo "==> 2/6 Construyendo imagen y levantando contenedores"
$COMPOSE build
$COMPOSE up -d

echo "==> 3/6 Instalando dependencias PHP (sin dev)"
$APP_EXEC composer install --no-dev --optimize-autoloader --no-interaction

# Los assets de Vite se compilan en local y se suben por rsync. Solo nos
# aseguramos de no usar el dev server.
rm -f public/hot

echo "==> 4/6 Migrando base de datos y creando admin"
$APP_EXEC php artisan migrate --force
$APP_EXEC php artisan db:seed --force

echo "==> 5/6 Optimizando (rutas y vistas en caché)"
$APP_EXEC php artisan storage:link 2>/dev/null || true
$APP_EXEC php artisan route:clear
$APP_EXEC php artisan view:clear
$APP_EXEC php artisan config:clear
$APP_EXEC php artisan route:cache
$APP_EXEC php artisan view:cache

echo "==> 6/6 Listo. Estado de los contenedores:"
$COMPOSE ps
echo ""
echo "✅ Despliegue terminado. La app escucha en 127.0.0.1:${APP_PORT:-8310}."
echo "   El vhost del host debe apuntar a ese puerto + HTTPS (certbot)."
