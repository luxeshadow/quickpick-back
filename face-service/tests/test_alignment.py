import numpy as np
import pytest

from app.vision.alignment import ARCFACE_TEMPLATE, align_arcface, normalize_for_edgeface


def test_alignment_produces_expected_shape() -> None:
    image = np.zeros((200, 200, 3), dtype=np.uint8)
    landmarks = tuple((float(x + 20), float(y + 10)) for x, y in ARCFACE_TEMPLATE)

    aligned = align_arcface(image, landmarks)

    assert aligned.shape == (112, 112, 3)
    assert aligned.dtype == np.uint8


def test_normalization_converts_bgr_to_rgb_and_minus_one_to_one() -> None:
    image = np.zeros((112, 112, 3), dtype=np.uint8)
    image[0, 0] = [0, 127, 255]

    normalized = normalize_for_edgeface(image)

    assert normalized.shape == (112, 112, 3)
    assert normalized.dtype == np.float32
    assert normalized[0, 0, 0] == pytest.approx(1.0)
    assert normalized[0, 0, 2] == pytest.approx(-1.0)


def test_alignment_requires_five_landmarks() -> None:
    with pytest.raises(ValueError, match="cinq"):
        align_arcface(np.zeros((100, 100, 3), dtype=np.uint8), ((1.0, 1.0),))
