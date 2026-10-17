# Utilisateurs

Toutes les routes nécessitent `Authorization: Bearer TOKEN` et `Accept: application/json`.

- `PATCH /api/users/me` : propriétaire uniquement ; champs partiels `full_name`, `email`, `phone`.
  Un changement d'email efface `email_verified_at`. Aucun changement de mot de passe,
  rôle, identifiant social ou statut par cette route.
- `GET /api/users?search=Camille&per_page=20&page=1` : admin uniquement ; nom/email,
  recherche insensible à la casse, pagination 1 à 100 utilisateurs par page.
- `GET /api/users/search?search=Camille` : alias de la liste.
- `GET /api/users/{id}` : admin uniquement ; détail pour la fiche utilisateur.
- `DELETE /api/users/{id}` : admin uniquement ; suppression définitive du compte,
  de ses données Laravel liées en cascade, des sessions et des tokens personnels.
  Ne supprime pas les fichiers ni les embeddings Supabase. Aucun utilisateur réel
  n'est supprimé lors de l'installation de ces routes.

Liste : `{ "status": "success", "users": [...], "pagination": { "current_page": 1,
"last_page": 1, "per_page": 20, "total": 1 } }`.
Détail : `{ "status": "success", "user": {...} }`.

Champs utilisateur : `id`, `full_name`, `email`, `phone`, `status`, `registered_at`,
`email_verified_at`, `roles` (admin), `current_subscription` (admin, ou null),
`statistics: {events, photos, videos, total_media}` (admin).
L'abonnement courant inclut `subscription_plan`, `starts_at`, `ends_at` (null = permanent),
`status`. Seuls les abonnements actifs dont les dates sont valides sont courants.

Le détail inclut en plus `subscriptions` (historique avec plans), `payments`
(montants, devises, statuts, `paid_at`), et `events` avec `id`, `name`, `event_date`,
`photos_count`, `videos_count`, `total_media`. Les compteurs incluent uniquement les
médias uploadés des événements non archivés. Aucune URL photo signée n'est générée ici.

Suppression : HTTP 200, `{ "status": "success", "message": "Utilisateur supprimé avec succès." }`.
Profil : HTTP 200, `{ "status": "success", "message": "Profil mis à jour avec succès.", "user": {...} }`.
Erreurs : 401 sans connexion, 403 sans droits admin, 404 inconnu, 422 validation.
