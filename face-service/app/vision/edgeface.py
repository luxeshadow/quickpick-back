import ctypes
from pathlib import Path

import numpy as np


EMBEDDING_DIMENSION = 512
NORM_TOLERANCE = 1e-3


class EdgeFaceEmbedder:
    def __init__(self, library_path: Path, weights_path: Path) -> None:
        if not library_path.is_file():
            raise FileNotFoundError(f"Bibliothèque FaceX introuvable : {library_path}")
        if not weights_path.is_file():
            raise FileNotFoundError(f"Poids EdgeFace introuvables : {weights_path}")

        self._library = ctypes.CDLL(str(library_path))
        self._configure_api()
        self._handle = self._library.facex_init(str(weights_path).encode(), None, None)
        if not self._handle:
            raise RuntimeError("FaceX n’a pas pu initialiser le modèle EdgeFace.")

    def _configure_api(self) -> None:
        self._library.facex_init.argtypes = [ctypes.c_char_p, ctypes.c_char_p, ctypes.c_char_p]
        self._library.facex_init.restype = ctypes.c_void_p
        self._library.facex_embed.argtypes = [
            ctypes.c_void_p,
            ctypes.POINTER(ctypes.c_float),
            ctypes.POINTER(ctypes.c_float),
        ]
        self._library.facex_embed.restype = ctypes.c_int
        self._library.facex_free.argtypes = [ctypes.c_void_p]
        self._library.facex_free.restype = None

    def embed(self, normalized_rgb: np.ndarray) -> np.ndarray:
        face = np.ascontiguousarray(normalized_rgb, dtype=np.float32)
        if face.shape != (112, 112, 3):
            raise ValueError("EdgeFace attend une image RGB float32 de taille 112 × 112.")
        if not np.isfinite(face).all() or face.min() < -1.0001 or face.max() > 1.0001:
            raise ValueError("Les pixels EdgeFace doivent être finis et normalisés dans [-1, 1].")

        embedding = np.empty(EMBEDDING_DIMENSION, dtype=np.float32)
        result = self._library.facex_embed(
            self._handle,
            face.ctypes.data_as(ctypes.POINTER(ctypes.c_float)),
            embedding.ctypes.data_as(ctypes.POINTER(ctypes.c_float)),
        )
        if result != 0:
            raise RuntimeError(f"FaceX a échoué avec le code {result}.")
        if embedding.shape != (EMBEDDING_DIMENSION,) or not np.isfinite(embedding).all():
            raise RuntimeError("FaceX a retourné un embedding invalide.")

        norm = float(np.linalg.norm(embedding))
        if norm < 1e-12:
            raise RuntimeError("FaceX a retourné un embedding de norme nulle.")

        # Les poids v1.0.0 produisent 512 valeurs valides mais la bibliothèque
        # native ne normalise pas sa sortie malgré le contrat annoncé.
        embedding /= norm
        normalized_norm = float(np.linalg.norm(embedding))
        if abs(normalized_norm - 1.0) > NORM_TOLERANCE:
            raise RuntimeError(
                f"Impossible de normaliser l’embedding EdgeFace (norme={normalized_norm:.6f})."
            )
        return embedding

    def close(self) -> None:
        if getattr(self, "_handle", None):
            self._library.facex_free(self._handle)
            self._handle = None

    def __enter__(self) -> "EdgeFaceEmbedder":
        return self

    def __exit__(self, *_: object) -> None:
        self.close()
