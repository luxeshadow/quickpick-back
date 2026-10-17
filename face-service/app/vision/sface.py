from pathlib import Path

import cv2
import numpy as np

from app.vision.yunet import DetectedFace


EMBEDDING_DIMENSION = 128


class SFaceEmbedder:
    def __init__(self, model_path: Path) -> None:
        if not model_path.is_file():
            raise FileNotFoundError(f"Modèle SFace introuvable : {model_path}")
        self._recognizer = cv2.FaceRecognizerSF.create(str(model_path), "")

    def embed(self, image: np.ndarray, face: DetectedFace) -> np.ndarray:
        raw_face = np.asarray(
            [
                face.x,
                face.y,
                face.width,
                face.height,
                *(coordinate for landmark in face.landmarks for coordinate in landmark),
                face.confidence,
            ],
            dtype=np.float32,
        )
        aligned = self._recognizer.alignCrop(image, raw_face)
        embedding = self._recognizer.feature(aligned).reshape(-1).astype(np.float32)
        if embedding.shape != (EMBEDDING_DIMENSION,) or not np.isfinite(embedding).all():
            raise RuntimeError("SFace a retourné un embedding invalide.")
        norm = float(np.linalg.norm(embedding))
        if norm < 1e-12:
            raise RuntimeError("SFace a retourné un embedding de norme nulle.")
        return embedding / norm
