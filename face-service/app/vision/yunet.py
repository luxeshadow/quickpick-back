from dataclasses import dataclass
from pathlib import Path
import os

import cv2
import numpy as np


@dataclass(frozen=True)
class DetectedFace:
    x: int
    y: int
    width: int
    height: int
    confidence: float
    landmarks: tuple[tuple[float, float], ...]


class YuNetDetector:
    def __init__(
        self,
        model_path: Path,
        confidence_threshold: float = 0.9,
        nms_threshold: float = 0.3,
        top_k: int = 5000,
    ) -> None:
        self._detector = cv2.FaceDetectorYN.create(
            str(model_path),
            "",
            (320, 320),
            confidence_threshold,
            nms_threshold,
            top_k,
        )

    def detect(self, image: np.ndarray) -> list[DetectedFace]:
        if image is None or image.size == 0:
            raise ValueError("L’image fournie est vide.")
        if image.ndim != 3 or image.shape[2] != 3:
            raise ValueError("L’image doit être au format BGR avec trois canaux.")

        height, width = image.shape[:2]
        # Bound the detector's activation buffers, preserving original coordinates.
        max_side = max(320, int(os.getenv('DETECTION_MAX_SIDE', '1280')))
        scale = min(1.0, max_side / max(width, height))
        detection_image = image
        if scale < 1:
            detection_image = cv2.resize(image, (max(1, round(width * scale)), max(1, round(height * scale))))
        dh, dw = detection_image.shape[:2]
        self._detector.setInputSize((dw, dh))
        _, raw_faces = self._detector.detect(detection_image)

        if raw_faces is None:
            return []

        faces: list[DetectedFace] = []
        for face in raw_faces:
            face = face.copy()
            face[[0, 2, 4, 6, 8, 10, 12]] *= width / dw
            face[[1, 3, 5, 7, 9, 11, 13]] *= height / dh
            landmarks = tuple((float(face[index]), float(face[index + 1])) for index in range(4, 14, 2))
            faces.append(
                DetectedFace(
                    x=max(0, int(round(face[0]))),
                    y=max(0, int(round(face[1]))),
                    width=max(0, int(round(face[2]))),
                    height=max(0, int(round(face[3]))),
                    confidence=float(face[14]),
                    landmarks=landmarks,
                )
            )

        return faces
