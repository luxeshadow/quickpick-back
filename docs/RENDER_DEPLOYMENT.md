# Laravel sur Render (Docker)

Le Dockerfile à la racine construit l'API Laravel seulement. Le frontend et le
microservice Python sont des services distincts. Aucune clé n'est incluse dans l'image.

## Web Service

- Dépôt : `https://github.com/luxeshadow/quickpick-back.git`
- Branche : celle contenant le Dockerfile (actuellement travail local sur `develop`).
- Runtime : Docker ; Root Directory : vide ; Dockerfile Path : `./Dockerfile`.
- Health Check Path : `/up`.
- Ne pas remplacer le Docker Command : le CMD démarre Apache sur `PORT` (10000 par défaut).
- Apache sert uniquement `public/`, avec la réécriture Laravel activée.
- Les migrations ne sont pas exécutées automatiquement au démarrage.

## Variables d'environnement

Configurer dans Render (ne pas committer de `.env`) :

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:VOTRE_CLE_LARAVEL
APP_URL=https://VOTRE_API.onrender.com
FRONTEND_URL=https://VOTRE_FRONTEND
LOG_CHANNEL=stderr
DB_CONNECTION=pgsql
DB_HOST=VOTRE_HOTE_POSTGRES
DB_PORT=5432
DB_DATABASE=VOTRE_BASE
DB_USERNAME=VOTRE_UTILISATEUR
DB_PASSWORD=VOTRE_MOT_DE_PASSE
DB_SSLMODE=require
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
SUPABASE_URL=https://VOTRE_PROJET.supabase.co
SUPABASE_SECRET_KEY=VOTRE_CLE_SERVEUR
SUPABASE_MEDIA_BUCKET=event-media
FACE_SERVICE_URL=http://VOTRE_SERVICE_PYTHON:8001
FACE_SERVICE_HMAC_SECRET=VOTRE_SECRET_PARTAGE
FACE_MODEL_VERSION=sface-2021dec-v1
FACE_SERVICE_TIMEOUT=15
FACE_SERVICE_SEARCH_TIMEOUT=60
```

Conserver une APP_KEY stable ; ne pas la régénérer à chaque déploiement. Pour une
nouvelle installation uniquement : `php artisan key:generate --show` donne une clé
à enregistrer dans Render. Configurer aussi le SMTP requis par l'application.
Ne pas utiliser 127.0.0.1 comme adresse du microservice distant ou de PostgreSQL.

## Base et tâches de fond

Après configuration de la base, exécuter dans un shell du service ou une étape de
pré-déploiement disponible sur votre offre :

```sh
php artisan migrate --force
php artisan db:seed --force
```

Créer un Background Worker avec la même image et les mêmes variables, en utilisant :

```sh
php artisan queue:work --queue=face-processing,default --tries=5 --timeout=120
```

Le job photo conserve ses propres limites de tentatives et de délai. Le scheduler
Laravel doit également être exécuté séparément (`php artisan schedule:run` chaque
minute). Le service Python conserve son propre Dockerfile dans `face-service/` et
doit être déployé avec ses modèles et la même clé HMAC.

Les fichiers sont stockés sur Supabase ; ne pas compter sur le disque éphémère du
conteneur pour une base SQLite ou des médias persistants.

## Vérification

Sur une machine disposant de Docker :

```sh
docker build -t quickpick-back .
docker run --rm -p 10000:10000 --env-file .env quickpick-back
```

Tester `/up`. Une vérification du microservice et du traitement de photos reste
nécessaire après configuration des services distants.

Références : https://render.com/docs/docker et https://render.com/docs/web-services
