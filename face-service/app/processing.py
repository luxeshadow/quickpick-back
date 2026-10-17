import hashlib
import hmac
import json
import logging
import time
from datetime import datetime, timezone
from urllib.parse import quote

import cv2
import httpx
import numpy as np

from app.config import Settings
from app.schemas import ProcessPhotoCommand
from app.vision.sface import SFaceEmbedder
from app.vision.yunet import DetectedFace, YuNetDetector


logger = logging.getLogger(__name__)
STORAGE_EMBEDDING_DIMENSION = 512


def _supabase_headers(settings: Settings) -> dict[str, str]:
    secret = settings.supabase_secret_key.get_secret_value()
    headers = {"apikey": secret}
    if not secret.startswith("sb_secret_"):
        headers["Authorization"] = f"Bearer {secret}"
    return headers


def _signed_headers(body: bytes, secret: str) -> dict[str, str]:
    timestamp = str(int(time.time()))
    digest = hmac.new(
        secret.encode("utf-8"), timestamp.encode("ascii") + b"." + body, hashlib.sha256
    ).hexdigest()
    return {
        "Content-Type": "application/json",
        "X-QuickPick-Timestamp": timestamp,
        "X-QuickPick-Signature": f"sha256={digest}",
    }


def _download_photo(command: ProcessPhotoCommand, settings: Settings) -> bytes:
    encoded_path = "/".join(
        quote(segment, safe="")
        for segment in (settings.supabase_media_bucket, *command.storage_path.split("/"))
    )
    url = f"{settings.supabase_url.rstrip('/')}/storage/v1/object/{encoded_path}"
    with httpx.Client(timeout=30) as client:
        response = client.get(url, headers=_supabase_headers(settings))
        response.raise_for_status()
        return response.content


def _replace_faces(
    command: ProcessPhotoCommand,
    settings: Settings,
    faces_with_embeddings: list[tuple[DetectedFace, np.ndarray]],
) -> None:
    url = f"{settings.supabase_url.rstrip('/')}/rest/v1/photo_faces"
    headers = _supabase_headers(settings)
    headers["Prefer"] = "return=minimal"
    rows = []
    for face_index, (face, embedding) in enumerate(faces_with_embeddings):
        if embedding.size > STORAGE_EMBEDDING_DIMENSION:
            raise ValueError("L'embedding dépasse la dimension vectorielle de stockage.")
        stored_embedding = np.pad(
            embedding,
            (0, STORAGE_EMBEDDING_DIMENSION - embedding.size),
            mode="constant",
        )
        rows.append(
            {
                "photo_id": command.photo_id,
                "event_id": command.event_id,
                "model_version": command.model_version,
                "face_index": face_index,
                "confidence": round(face.confidence, 4),
                "x": face.x,
                "y": face.y,
                "width": face.width,
                "height": face.height,
                "embedding": "["
                + ",".join(f"{value:.8f}" for value in stored_embedding)
                + "]",
            }
        )

    with httpx.Client(timeout=30) as client:
        delete_response = client.delete(
            url,
            params={"photo_id": f"eq.{command.photo_id}"},
            headers=headers,
        )
        delete_response.raise_for_status()
        if rows:
            insert_response = client.post(url, json=rows, headers=headers)
            insert_response.raise_for_status()


def _send_callback(
    command: ProcessPhotoCommand,
    settings: Settings,
    callback_status: str,
    faces_count: int = 0,
    error: str | None = None,
) -> None:
    payload = {
        "job_id": str(command.job_id),
        "photo_id": command.photo_id,
        "event_id": command.event_id,
        "model_version": command.model_version,
        "status": callback_status,
        "faces_count": faces_count,
        "error": error,
        "timestamp": datetime.now(timezone.utc).isoformat(),
    }
    body = json.dumps(payload, separators=(",", ":"), ensure_ascii=False).encode("utf-8")
    secret = settings.laravel_hmac_secret.get_secret_value()
    with httpx.Client(timeout=15) as client:
        response = client.post(
            settings.laravel_callback_url,
            content=body,
            headers=_signed_headers(body, secret),
        )
        response.raise_for_status()


def process_photo(command: ProcessPhotoCommand, settings: Settings) -> None:
    try:
        content = _download_photo(command, settings)
        image = cv2.imdecode(np.frombuffer(content, dtype=np.uint8), cv2.IMREAD_COLOR)
        if image is None:
            raise ValueError("Le fichier téléchargé n'est pas une image lisible.")

        detector = YuNetDetector(settings.yunet_model_path)
        embedder = SFaceEmbedder(settings.sface_model_path)
        faces = detector.detect(image)
        faces_with_embeddings = [(face, embedder.embed(image, face)) for face in faces]
        _replace_faces(command, settings, faces_with_embeddings)

        _send_callback(command, settings, "processed", faces_count=len(faces))
    except Exception as exception:
        logger.exception("Échec du traitement de la photo %s", command.photo_id)
        try:
            _send_callback(command, settings, "failed", error=str(exception)[:2000])
        except Exception:
            logger.exception("Impossible d'envoyer le callback d'échec à Laravel")
