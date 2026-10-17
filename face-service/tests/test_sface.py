from pathlib import Path

import cv2
import numpy as np
import pytest

from app.vision.sface import EMBEDDING_DIMENSION, SFaceEmbedder
from app.vision.yunet import DetectedFace


MODEL_PATH = Path("models/face_recognition_sface_2021dec.onnx")


def test_embedding_contract_is_128_dimensions() -> None:
    assert EMBEDDING_DIMENSION == 128


@pytest.mark.skipif(not MODEL_PATH.is_file(), reason="Modèle SFace non installé")
def test_model_can_be_loaded() -> None:
    assert SFaceEmbedder(MODEL_PATH) is not None


@pytest.mark.skipif(not MODEL_PATH.is_file(), reason="Modèle SFace non installé")
def test_embedding_is_l2_normalized() -> None:
    embedder = SFaceEmbedder(MODEL_PATH)
    image = np.full((240, 240, 3), 127, dtype=np.uint8)
    face = DetectedFace(
        x=40,
        y=40,
        width=160,
        height=160,
        confidence=1.0,
        landmarks=((85, 100), (155, 100), (120, 130), (92, 165), (148, 165)),
    )
    embedding = embedder.embed(image, face)

    assert embedding.shape == (128,)
    assert float(np.linalg.norm(embedding)) == pytest.approx(1.0, abs=1e-5)
