#!/bin/bash
set -e

# Adapter le port d'écoute Apache au port fourni par Render ($PORT, généralement 10000)
if [ -n "$PORT" ]; then
    echo "Configuration du port d'écoute Apache sur : $PORT"
    sed -i "s/80/$PORT/g" /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf
fi

# Lien symbolique pour le stockage public des médias
php artisan storage:link --no-interaction || true

# Mise en cache de la configuration et des routes si la clé d'application est présente
if [ -n "$APP_KEY" ]; then
    echo "Optimisation du cache Laravel..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Exécution des migrations si une base de données est accessible
if [ -n "$DB_HOST" ] || [ -n "$DB_URL" ]; then
    echo "Exécution des migrations de base de données..."
    php artisan migrate --force --no-interaction || true
fi

echo "Démarrage du serveur web..."
exec "$@"
