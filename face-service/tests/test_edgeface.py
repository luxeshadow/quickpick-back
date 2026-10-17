import numpy as np
import pytest

from app.vision.edgeface import EMBEDDING_DIMENSION


def test_embedding_contract_is_512_dimensions() -> None:
    assert EMBEDDING_DIMENSION == 512


def test_l2_contract_example() -> None:
    embedding = np.ones(EMBEDDING_DIMENSION, dtype=np.float32)
    embedding /= np.linalg.norm(embedding)

    assert float(np.linalg.norm(embedding)) == pytest.approx(1.0, abs=1e-6)

