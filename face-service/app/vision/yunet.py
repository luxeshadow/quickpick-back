from dataclasses import dataclass
from pathlib import Path

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
        self._detector.setInputSize((width, height))
        _, raw_faces = self._detector.detect(image)

        if raw_faces is None:
            return []

        faces: list[DetectedFace] = []
        for face in raw_faces:
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
