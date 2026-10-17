# QuickPick Face Service

Microservice privé chargé de détecter les visages avec YuNet et de produire les embeddings
`sface-2021dec-v1`. Cette variante fonctionne nativement sous Windows, sans Docker.

## Démarrage local

```bash
cd face-service
python -m venv .venv
.venv\\Scripts\\activate
pip install -e ".[dev]"
copy .env.example .env
uvicorn app.main:app --reload --port 8001
```

Sous PowerShell, l'activation peut être évitée :

```powershell
.\.venv\Scripts\python.exe -m pip install -e ".[dev]"
.\.venv\Scripts\python.exe -m uvicorn app.main:app --reload --port 8001
```

`GET /health` indique `ready` lorsque YuNet et SFace sont présents avec les bons SHA-256.

Le détecteur officiel OpenCV YuNet utilisé est `face_detection_yunet_2023mar.onnx`, identifié par le SHA-256
`8f2383e4dd3cfbb4553ea8718107fc0423210dc964f9f4280604804ed2552fa4`.

Le modèle d'embedding est `face_recognition_sface_2021dec.onnx`, identifié par le SHA-256
`0ba9fbfa01b5270c96627c4ef784da859931e02f04419c829e83484087c34e79`. Sa sortie
`float32[128]` est normalisée L2 par le service.
Pour rester compatible avec la colonne PostgreSQL `vector(512)` existante, le stockage
ajoute 384 zéros. Cette opération conserve exactement la distance cosinus entre embeddings SFace.

## Recherche par selfie

`POST /v1/faces/search` reçoit un JSON signé HMAC avec `event_id`, `model_version`
et `image_base64`. Il exige exactement un visage et appelle la RPC Supabase
`match_event_photo_faces`, isolée par événement et modèle. Les embeddings ne sont
jamais renvoyés ; le selfie reste en mémoire uniquement. Installer d'abord
`../docs/supabase/002_sface_search.sql`. Voir `../docs/GUEST_SCAN_API.md` pour l'API publique.

## Docker (optionnel)

```bash
docker compose build
docker compose up -d
```

Docker n'est pas requis sous Windows. L'image optionnelle utilise les mêmes modèles OpenCV
et ne compile plus de bibliothèque FaceX.

## Signature Laravel

Laravel signe exactement `timestamp + "." + corps JSON brut` avec HMAC-SHA256. Les en-têtes attendus sont :

- `X-QuickPick-Timestamp` : timestamp Unix, tolérance 5 minutes ;
- `X-QuickPick-Signature` : hexadécimal, avec ou sans préfixe `sha256=`.
