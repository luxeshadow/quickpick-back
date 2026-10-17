from pathlib import Path

import numpy as np
import pytest

from app.vision.yunet import YuNetDetector


MODEL_PATH = Path("models/face_detection_yunet_2023mar.onnx")


@pytest.mark.skipif(not MODEL_PATH.is_file(), reason="Modèle YuNet non installé")
def test_blank_image_contains_no_face() -> None:
    detector = YuNetDetector(MODEL_PATH)
    image = np.zeros((320, 320, 3), dtype=np.uint8)

    assert detector.detect(image) == []


@pytest.mark.skipif(not MODEL_PATH.is_file(), reason="Modèle YuNet non installé")
def test_empty_image_is_rejected() -> None:
    detector = YuNetDetector(MODEL_PATH)

    with pytest.raises(ValueError, match="vide"):
        detector.detect(np.array([], dtype=np.uint8))
