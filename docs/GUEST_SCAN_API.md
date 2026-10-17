# Recherche depuis un selfie

## Installation

Exécuter `docs/supabase/002_sface_search.sql` dans le SQL Editor du même projet Supabase
que `event-media`. Ce script conserve `vector(512)` et limite la RPC au rôle serveur.
Il faut ensuite retraiter les photos dont les vecteurs ne portent pas `sface-2021dec-v1`.
Ne pas simplement changer la version des anciens embeddings EdgeFace.
Le traitement Python enregistre désormais `model_version` pour chaque visage.

Redémarrer Python et le worker Laravel après mise à jour. Aucun nouveau paquet requis.
Laravel : `FACE_SERVICE_SEARCH_TIMEOUT=60`. Python : `MATCH_THRESHOLD=0.363`,
`MATCH_LIMIT=100` (maximum 500). Le seuil doit être calibré sur les photos réelles.
La valeur initiale 0.363 suit l'exemple SFace officiel d'OpenCV :
https://docs.opencv.org/doc/doxygen/html/d0/dd4/tutorial_dnn_face.html

## Frontend

Capturer une image après cadrage du visage, puis envoyer :

```javascript
const data = new FormData();
data.append('selfie', blob, 'selfie.jpg');
data.append('consent', '1'); // uniquement après consentement de la personne
const response = await fetch(`${apiUrl}/api/public/access-links/${token}/guest-scans`, {
  method: 'POST', headers: { Accept: 'application/json' }, body: data,
});
const result = await response.json();
```

Conserver le token de `/media?token=...`. Aucun Bearer requis. Ne pas définir
manuellement Content-Type pour FormData. Afficher le spinner pendant la requête,
prévoir au moins 60 secondes de délai côté frontend et gérer les erreurs.
Ne pas renvoyer automatiquement en boucle en cas d'échec.

Réponse HTTP 200 (valeurs illustratives) :

```json
{
  "status": "success",
  "scan": {"id": 1, "status": "matched", "matches_count": 1},
  "media": [{"id": 12, "filename": "photo.jpg", "width": 1920, "height": 1080,
    "similarity": 0.72, "url": "https://storage.example/signed-url"}]
}
```

Sans résultat : `scan.status=no_match`, `matches_count=0`, `media=[]`.
422 : selfie illisible, aucun visage ou plusieurs visages, consentement absent.
410 : lien invalide/inactif/expiré, quota épuisé, événement ou abonnement inactif.
429 : limite de requêtes. 503 : recherche indisponible.

Le selfie et son embedding ne sont jamais conservés ni retournés. Seuls les IDs des
photos, les scores et l'état du scan sont conservés. Une tentative acceptée après
validation du lien consomme le quota, même si la recherche échoue. Les réservations
sont verrouillées en base pour éviter de dépasser la limite par requêtes concurrentes.

La recherche utilise YuNet/SFace, un padding de 128 à 512 dimensions et la RPC filtrée
par événement ET modèle. Laravel vérifie le contrat retour, puis exclut les vidéos,
les photos d'autres événements, non uploadées, non traitées ou d'une autre version.
Les URLs signées sont générées à la demande et ne sont pas enregistrées en base.
