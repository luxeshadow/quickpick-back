# Roadmap — Access Links, Guest Scans et Photo Delivery

## 1. Objectif et responsabilités

Le système doit permettre au photographe de charger les photos d'un événement, de générer un lien public, puis à un invité de fournir un selfie afin de retrouver et recevoir ses photos.

Chaque composant doit avoir une responsabilité unique :

- **Laravel** : source de vérité métier (users, events, photos, liens, scans, résultats, livraisons), policies, quotas, orchestration et callbacks.
- **Supabase Storage** : stockage privé des photos et des selfies temporaires.
- **Supabase PostgreSQL + pgvector** : stockage et recherche des vecteurs faciaux dans `photo_faces`.
- **Microservice Python** : détection des visages, calcul des embeddings et recherche par similarité.
- **Queue Laravel** : lancement asynchrone des traitements, retries et livraisons.

Laravel ne doit jamais accepter du microservice un `event_id`, un `photo_id` ou un statut sans vérifier qu'il correspond au job initialement créé.

## 2. Flux cible

1. Laravel crée un lot d'upload (`upload_batch`) pour un événement.
2. Le front charge les fichiers avec des URLs Supabase signées.
3. Le front confirme chaque upload à Laravel ; Laravel crée les lignes `photos` en `pending`.
4. Quand le lot est marqué terminé, Laravel envoie les photos au traitement Python.
5. Python lit les photos avec des URLs signées, détecte les visages et upsert les embeddings dans Supabase `photo_faces`.
6. Python appelle un webhook Laravel signé pour chaque photo.
7. Laravel marque les photos `processed` ou `failed` et met à jour `photo_count`.
8. Quand toutes les photos du lot sont dans un état terminal, Laravel génère automatiquement un `access_link` avec les valeurs par défaut.
9. L'invité ouvre le lien, accepte le consentement et charge un selfie temporaire.
10. Laravel crée un `guest_scan`, puis demande au microservice de chercher les vecteurs similaires limités à l'événement.
11. Python renvoie les correspondances ; Laravel les enregistre dans `guest_scan_matches`.
12. Le front récupère les résultats via un jeton de suivi du scan.
13. L'invité demande une livraison ; Laravel crée un `photo_delivery` et envoie un lien signé temporaire par le canal choisi.

## 3. Phase A — Contrats et états

### Modèles retenus pour la version 1

- **Détection et alignement** : YuNet, via OpenCV.
- **Reconnaissance** : FaceX EdgeFace `xs`, poids officiels `edgeface_xs_fp32.bin`.
- **Entrée reconnaissance** : visage RGB aligné en `112 × 112`, valeurs normalisées dans `[-1, 1]`.
- **Embedding** : `float32[512]`, normalisé L2.
- **Métrique pgvector** : cosine (`vector_cosine_ops`).
- **Seuil initial** : `0.30`, à calibrer sur un jeu de photos représentatif avant production.
- **Version persistée** : `edgeface-xs-v1`.

Ce choix conserve le type Supabase existant `vector(512)`. Le modèle utilisé pour indexer les photos et celui utilisé pour le selfie doivent toujours avoir exactement la même version. Une modification de modèle impose une réindexation ; il ne faut jamais comparer des embeddings produits par deux versions différentes.

Les fichiers exacts du détecteur et du modèle doivent être identifiés par un SHA-256 dans la configuration du microservice. Le démarrage du service doit échouer si le checksum ne correspond pas, afin d'empêcher un changement silencieux de modèle.

### États des photos

`pending → processing → processed`

En cas d'erreur : `pending|processing → failed`. Un retry peut faire `failed → processing`.

### États des scans

`processing → matched | no_match | failed`

### États des livraisons

`pending → processing → sent | failed | expired`

### Contrat Laravel ↔ Python

Toutes les commandes et callbacks doivent contenir :

- `job_id` UUID unique ;
- `photo_id` ou `guest_scan_id` ;
- `event_id` ;
- `model_version` ;
- `attempt` ;
- `timestamp` ;
- signature HMAC du body.

Les callbacks doivent être idempotents : rejouer deux fois le même `job_id` ne doit créer ni visage ni résultat en double.

## 4. Phase B — Schéma de données

### `upload_batches` (à ajouter)

- `id`, `event_id`, `created_by` ;
- `status`: `uploading`, `processing`, `completed`, `partial_failure`, `failed` ;
- `expected_count`, `uploaded_count`, `processed_count`, `failed_count` ;
- `upload_completed_at`, `processing_completed_at` ;
- timestamps.

Ajouter `upload_batch_id` nullable à `photos` et à `access_links`.

### `access_links` (à compléter)

Conserver `event_id`, `token`, `expires_at`, `is_active`, et ajouter :

- `created_by` ;
- `source`: `automatic` ou `manual` ;
- `upload_batch_id` nullable ;
- `max_scans` nullable ;
- `scans_count` par défaut à zéro ;
- `last_used_at` nullable ;
- timestamps.

Valeurs automatiques recommandées : actif, expiration à 30 jours, scans illimités. Le token doit être aléatoire (au moins 256 bits), ne jamais apparaître dans les logs et être comparé en temps constant.

La suppression demandée pour un lien est une **suppression physique**. Les `guest_scans.access_link_id` restent présents avec `nullOnDelete`, ce qui conserve l'historique des scans.

### `photo_faces` dans Supabase pgvector

- `id` UUID ;
- `photo_id` bigint (identifiant Laravel) ;
- `event_id` bigint ;
- `face_index` smallint ;
- `embedding vector(N)` ;
- `bounding_box jsonb` ;
- `detection_confidence` ;
- `model_version` ;
- timestamps.

Contraintes recommandées :

- unique (`photo_id`, `face_index`, `model_version`) ;
- index sur `event_id` ;
- index vectoriel HNSW adapté à la métrique choisie (cosine recommandée).

Supabase ne doit pas décider si un utilisateur peut accéder à un événement. Le microservice utilise un compte de service et filtre obligatoirement la recherche par `event_id` fourni par un job Laravel signé.

### `guest_scans` (à compléter)

- champs existants ;
- `public_id` UUID unique ;
- `selfie_storage_path` temporaire ;
- `threshold` utilisé ;
- `model_version` ;
- `started_at`, `completed_at`, `expires_at` ;
- `consent_at`, `ip_hash`, `user_agent_hash` ;
- `result_token_hash` pour consulter les résultats sans compte.

### `guest_scan_matches` (à ajouter)

- `id`, `guest_scan_id`, `photo_id` ;
- `similarity` decimal ;
- `rank` ;
- `face_index` nullable ;
- timestamps ;
- unique (`guest_scan_id`, `photo_id`).

Cette table est indispensable : `matches_count` seul ne permet pas de restituer les photos trouvées.

### `photo_deliveries` (à compléter)

- champs existants ;
- `public_id` UUID ;
- `attempts` ;
- `last_error` nullable ;
- `requested_at`, `processing_at`, `sent_at`, `expires_at` ;
- `provider_reference` nullable ;
- `download_token_hash` nullable.

Ne jamais stocker une URL Supabase signée en base : elle expire. Stocker les chemins puis générer une URL signée au moment de la consultation ou de la livraison.

## 5. Phase C — Access Links

Implémenter modèle, enum de source, actions, requests, policy, contrôleur, routes, Swagger et tests.

Endpoints authentifiés :

- `POST /api/events/{event}/access-links` : génération manuelle par le propriétaire ;
- `GET /api/events/{event}/access-links` : liste des liens de l'événement ;
- `GET /api/access-links/{accessLink}` : détail propriétaire ;
- `PATCH /api/access-links/{accessLink}` : activer, désactiver ou changer l'expiration ;
- `DELETE /api/access-links/{accessLink}` : suppression physique.

Endpoint public :

- `GET /api/public/access-links/{token}` : vérifie existence, activité, expiration et quota sans exposer l'événement complet.

Policy : seul le propriétaire de l'événement peut créer, lister, modifier ou supprimer ses liens. Les admins peuvent intervenir si cette règle est souhaitée globalement.

L'action `GenerateDefaultAccessLinkAction` sera injectée à la fois dans le contrôleur manuel et dans le job de fin de traitement d'un upload.

## 6. Phase D — Upload et vectorisation

Implémenter avant GuestScan :

- création/finalisation des lots d'upload ;
- validation MIME, taille, quota `max_photos` et appartenance à l'événement ;
- URLs signées Supabase limitées à un chemin imposé ;
- jobs `ProcessPhotoJob` et `FinalizeUploadBatchJob` ;
- endpoint interne de callback Python signé ;
- retries avec backoff et dead-letter via `failed_jobs` ;
- génération automatique du lien uniquement lorsque le lot est terminé.

Le quota photo doit compter les photos non supprimées déjà présentes et celles réservées dans les lots en cours, afin d'éviter le dépassement par uploads concurrents.

## 7. Phase E — GuestScan

Endpoints publics protégés par rate limiting :

- `POST /api/public/access-links/{token}/guest-scans` : consentement + création du scan + URL signée d'upload selfie ;
- `POST /api/public/guest-scans/{publicId}/confirm-upload` : confirme le selfie et lance Python ;
- `GET /api/public/guest-scans/{publicId}?result_token=...` : état et résultats ;
- `DELETE /api/public/guest-scans/{publicId}` : suppression anticipée des données invité.

Endpoints propriétaire :

- `GET /api/events/{event}/guest-scans` : statistiques et sessions sans exposer inutilement le selfie ;
- `GET /api/guest-scans/{guestScan}` : détail autorisé.

Le microservice calcule un embedding du selfie, recherche par cosine similarity dans `photo_faces` avec `event_id`, déduplique par `photo_id`, applique le seuil versionné et retourne les meilleurs résultats.

## 8. Phase F — Photo Delivery

Endpoints :

- `POST /api/public/guest-scans/{publicId}/deliveries` : demande email/SMS/WhatsApp selon les fournisseurs activés ;
- `GET /api/public/photo-deliveries/{publicId}` : suivi avec token ;
- `POST /api/photo-deliveries/{delivery}/retry` : retry propriétaire/admin.

Le job de livraison doit :

1. vérifier que le scan est `matched` ;
2. charger uniquement les `guest_scan_matches` ;
3. générer des URLs Supabase signées courtes ou une archive temporaire ;
4. envoyer via le provider ;
5. enregistrer l'état, la référence provider et l'erreur éventuelle.

Les destinataires doivent être masqués dans les réponses du propriétaire et chiffrés en base si possible.

## 9. Phase G — Sécurité et confidentialité

- Buckets Supabase privés ; aucune URL publique permanente.
- HMAC + allowlist réseau entre Laravel et Python.
- Rate limits par token de lien et IP hachée.
- Taille/MIME contrôlés côté Laravel et Python ; suppression des métadonnées EXIF si nécessaire.
- Selfie et embedding invité supprimés automatiquement après 24 heures, ou plus tôt sur demande.
- Liens de livraison à durée courte et usage limité.
- Aucun embedding dans les réponses API ou les logs.
- Journalisation des actions propriétaire/admin et métriques sans données biométriques.
- Consentement explicite avant le scan facial et texte de rétention clairement affiché.

## 10. Phase H — Tests et déploiement progressif

Tests Laravel : policies, expiration des liens, suppression physique, quotas, idempotence des callbacks, isolation entre événements, filtres et transitions d'état.

Tests Python : détection sans visage/multi-visages, modèle versionné, seuils, déduplication et recherche limitée à un événement.

Tests contractuels : payloads Laravel/Python figés dans des fixtures partagées.

Tests end-to-end : upload → vectorisation → lien automatique → selfie → matches → livraison.

Déploiement conseillé :

1. migrations et flags désactivés ;
2. traitement photo sur un événement interne ;
3. scan sans livraison ;
4. livraison email seulement ;
5. activation progressive des autres canaux.

## 11. Ordre d'implémentation recommandé

1. Valider le contrat d'embedding (`model_version`, dimension et métrique).
2. Ajouter `upload_batches` et `guest_scan_matches`.
3. Implémenter AccessLink manuel avec policies et suppression physique.
4. Implémenter le pipeline upload et le callback Python idempotent.
5. Brancher la génération automatique du lien à la finalisation du lot.
6. Implémenter GuestScan et la recherche pgvector.
7. Implémenter PhotoDelivery, d'abord par email.
8. Ajouter nettoyage automatique, observabilité, tests E2E et montée en charge.

Ne pas commencer la recherche faciale avant d'avoir figé le contrat de vecteur et la table `guest_scan_matches`. Changer de dimension, de normalisation ou de métrique après indexation imposerait une réindexation complète des visages.
