import numpy as np

from app.processing import STORAGE_EMBEDDING_DIMENSION


def test_storage_dimension_remains_compatible_with_existing_pgvector_column() -> None:
    embedding = np.ones(128, dtype=np.float32)
    stored = np.pad(embedding, (0, STORAGE_EMBEDDING_DIMENSION - embedding.size))

    assert stored.shape == (512,)
    assert np.array_equal(stored[:128], embedding)
    assert np.count_nonzero(stored[128:]) == 0
