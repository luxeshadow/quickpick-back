# Configuration Supabase Storage

1. Créer un bucket privé nommé `event-media`.
2. Copier l'URL du projet dans `SUPABASE_URL`.
3. Créer/copier une clé serveur `sb_secret_...` dans `SUPABASE_SECRET_KEY`.
   L'ancienne clé JWT `service_role` reste compatible via `SUPABASE_SERVICE_ROLE_KEY`.
4. Ne jamais préfixer cette clé par `VITE_` et ne jamais l'envoyer au front.
5. Configurer dans `.env` :

```dotenv
SUPABASE_URL=https://PROJECT_REF.supabase.co
SUPABASE_SECRET_KEY=sb_secret_xxx
SUPABASE_MEDIA_BUCKET=event-media
```

Le bucket central `event-media` doit rester privé avec les restrictions suivantes :

- taille maximale globale : `500 MB` ;
- MIME autorisés : `image/jpeg`, `image/png`, `image/webp`, `video/mp4`, `video/quicktime`, `video/webm`.

Laravel crée automatiquement les chemins `events/{event_id}/images/...` et `events/{event_id}/videos/...`. Il applique une limite plus stricte de `25 MB` aux images. Les vidéos sont conservées mais ne sont jamais envoyées au pipeline de vectorisation faciale.

6. Après modification :

```bash
php artisan config:clear
```

Laravel utilise la clé uniquement pour créer les URLs signées et vérifier l'existence des objets. Le front reçoit une URL temporaire et effectue l'upload avec `PUT`.
