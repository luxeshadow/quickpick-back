import cv2
import numpy as np


# Template ArcFace 112x112 : œil droit, œil gauche, nez, bouche droite, bouche gauche.
ARCFACE_TEMPLATE = np.array(
    [
        [38.2946, 51.6963],
        [73.5318, 51.5014],
        [56.0252, 71.7366],
        [41.5493, 92.3655],
        [70.7299, 92.2041],
    ],
    dtype=np.float32,
)


def align_arcface(image_bgr: np.ndarray, landmarks: tuple[tuple[float, float], ...]) -> np.ndarray:
    if image_bgr is None or image_bgr.size == 0:
        raise ValueError("L’image fournie est vide.")
    if len(landmarks) != 5:
        raise ValueError("L’alignement ArcFace exige exactement cinq points faciaux.")

    source = np.asarray(landmarks, dtype=np.float32)
    if source.shape != (5, 2) or not np.isfinite(source).all():
        raise ValueError("Les points faciaux sont invalides.")

    transform, _ = cv2.estimateAffinePartial2D(source, ARCFACE_TEMPLATE, method=cv2.LMEDS)
    if transform is None:
        raise ValueError("Impossible de calculer l’alignement du visage.")

    return cv2.warpAffine(
        image_bgr,
        transform,
        (112, 112),
        flags=cv2.INTER_LINEAR,
        borderMode=cv2.BORDER_CONSTANT,
    )


def normalize_for_edgeface(aligned_bgr: np.ndarray) -> np.ndarray:
    if aligned_bgr.shape != (112, 112, 3):
        raise ValueError("Le visage aligné doit mesurer 112 × 112 pixels.")

    rgb = cv2.cvtColor(aligned_bgr, cv2.COLOR_BGR2RGB)
    return np.ascontiguousarray(rgb.astype(np.float32) / 127.5 - 1.0)
