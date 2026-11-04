# Un seul service Render pour QuickPick

Le Dockerfile racine exécute désormais Apache/Laravel, Python YuNet/SFace, un worker
Laravel et le scheduler sous Supervisor. Supabase conserve PostgreSQL et les fichiers.
Il n'est pas nécessaire de créer un Private Service, un Background Worker ou un Cron
Job distinct pour cette configuration. Les processus partagent la RAM et le CPU.

## Render

1. Déployer la branche contenant ces modifications, avec le Dockerfile racine.
2. **Vider Settings > Docker Command**. Une ancienne commande terminant par
   `apache2-foreground` ne démarrera pas Supervisor ni les autres processus.
3. Conserver les variables PostgreSQL, APP_KEY et Supabase existantes.
4. Configurer `FACE_SERVICE_HMAC_SECRET` avec au moins 32 caractères et
   `FACE_MODEL_VERSION=sface-2021dec-v1`.
5. Utiliser `/up` comme Health Check Path du service web.

L'entrée du conteneur configure automatiquement :

```dotenv
FACE_SERVICE_URL=http://127.0.0.1:8001
LARAVEL_CALLBACK_URL=http://127.0.0.1:PORT/api/internal/photo-processing/callback
QUEUE_CONNECTION=database
```

PORT est remplacé par la valeur Render. La clé HMAC Python est copiée depuis la clé
Laravel ; la clé serveur Supabase existante SUPABASE_SERVICE_ROLE_KEY est acceptée
comme alternative à SUPABASE_SECRET_KEY. Aucun secret n'est écrit dans l'image.
Python n'expose pas son port publiquement. Un seul worker et un seul processus
Uvicorn sont démarrés. Les modèles sont téléchargés et vérifiés lors du build.

Les migrations et seeders restent des opérations explicites dans le Shell, pas à
chaque redémarrage. La base doit être migrée avant l'utilisation du worker/scheduler.
Le worker attend la disponibilité des deux services et la validité des modèles.
Son redémarrage supervisé n'ajoute pas de tentatives aux jobs : les limites du job
Laravel restent en vigueur.

## Vérifications après déploiement

Dans les logs Render, vérifier le démarrage des quatre programmes : apache,
face-service, queue, scheduler. Dans le Shell :

```sh
curl -fsS http://127.0.0.1:8001/health
php artisan migrate:status
```

Le health check web seul ne prouve pas que Python fonctionne. Tester un upload
jusqu'au callback puis une recherche depuis un selfie. Les logs de chaque programme
sont transmis aux logs Render.

Utiliser une seule instance Render pour cette configuration : plusieurs instances
exécuteraient plusieurs schedulers. Une petite instance peut manquer de RAM pendant
une recherche faciale : la réunion des services réduit les frais par service, mais
ne garantit pas que 512 Mo suffisent. Mesurer la mémoire sous charge ; ne pas créer
de requêtes selfie concurrentes en boucle.

## Vérification locale

## Limitation des pics mémoire

Une seule inférence est admise à la fois, y compris les photos traitées après leur
réponse HTTP 202. Une autre demande reçoit 503 avant acceptation : les jobs photo
suivent alors leurs tentatives et backoffs existants, sans retry infini. Un scan
selfie concurrent reçoit l'erreur temporaire habituelle ; le frontend doit la gérer.
La détection travaille au maximum sur 1280 pixels de côté (`DETECTION_MAX_SIDE`),
puis les coordonnées sont remappées sur l'original pour SFace et le stockage.
Cela peut réduire la détection des très petits visages sur les photos de groupe.
Les images stockées ne sont pas modifiées. SFace n'est pas chargé s'il n'y a aucun visage.
Apache est limité à deux processus de requêtes et l'OPcache à 64 Mo.
Ces limites réduisent les pics ; elles ne garantissent pas que 512 Mo suffisent à
tous les fichiers et toutes les charges. Aucun rattrapage automatique des photos
interrompues par un arrêt brutal n'est ajouté.

## Construction locale

```sh
docker build -t quickpick-combined .
docker run --rm -p 10000:10000 --env-file .env quickpick-combined
```

La base doit être accessible depuis le conteneur ; 127.0.0.1 ne désigne pas la base
de la machine hôte. Le Dockerfile Python autonome reste disponible pour un futur
déploiement séparé, sans changement de modèle ni de dimension vectorielle.
