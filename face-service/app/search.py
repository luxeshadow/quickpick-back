import base64
import binascii

import cv2
import httpx
import numpy as np
from fastapi import HTTPException

from app.config import Settings
from app.processing import _supabase_headers, STORAGE_EMBEDDING_DIMENSION
from app.schemas import SearchFacesCommand, SearchFacesResult, FaceMatch
from app.vision.yunet import YuNetDetector
from app.vision.sface import SFaceEmbedder


def search_faces(command: SearchFacesCommand, settings: Settings) -> SearchFacesResult:
    try:
        content = base64.b64decode(command.image_base64, validate=True)
    except (ValueError, binascii.Error):
        raise HTTPException(422, "Le selfie est invalide.") from None
    if len(content) > 5 * 1024 * 1024:
        raise HTTPException(422, "Le selfie dépasse 5 Mo.")
    image = cv2.imdecode(np.frombuffer(content, dtype=np.uint8), cv2.IMREAD_COLOR)
    if image is None or max(image.shape[:2]) > 4096:
        raise HTTPException(422, "Image illisible ou dimensions supérieures à 4096 pixels.")
    faces = YuNetDetector(settings.yunet_model_path).detect(image)
    if len(faces) != 1:
        raise HTTPException(422, "Le selfie doit contenir exactement un visage bien visible.")
    embedding = SFaceEmbedder(settings.sface_model_path).embed(image, faces[0])
    stored = np.pad(embedding, (0, STORAGE_EMBEDDING_DIMENSION - embedding.size))
    try:
        with httpx.Client(timeout=30) as client:
            response = client.post(
                settings.supabase_url.rstrip('/') + '/rest/v1/rpc/match_event_photo_faces',
                headers=_supabase_headers(settings),
                json={
                    'p_event_id': command.event_id,
                    'p_query_embedding': '[' + ','.join(str(float(v)) for v in stored) + ']',
                    'p_model_version': command.model_version,
                    'p_similarity_threshold': settings.match_threshold,
                    'p_match_count': settings.match_limit,
                },
            )
            response.raise_for_status()
            matches = [FaceMatch.model_validate(row) for row in response.json()]
    except (httpx.HTTPError, ValueError, TypeError):
        raise HTTPException(503, "La recherche faciale est temporairement indisponible.") from None
    best = {}
    for match in matches:
        if match.similarity >= settings.match_threshold and (
            match.photo_id not in best or match.similarity > best[match.photo_id].similarity
        ):
            best[match.photo_id] = match
    return SearchFacesResult(
        event_id=command.event_id, model_version=command.model_version,
        matches=sorted(best.values(), key=lambda m: m.similarity, reverse=True)[:settings.match_limit],
    )
