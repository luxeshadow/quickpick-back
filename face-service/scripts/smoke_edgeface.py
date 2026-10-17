from pathlib import Path

import numpy as np

from app.vision.edgeface import EMBEDDING_DIMENSION, EdgeFaceEmbedder


with EdgeFaceEmbedder(
    Path("/opt/facex/libfacex.so"),
    Path("/opt/models/edgeface_xs_fp32.bin"),
) as embedder:
    embedding = embedder.embed(np.zeros((112, 112, 3), dtype=np.float32))

assert embedding.shape == (EMBEDDING_DIMENSION,)
assert np.isfinite(embedding).all()
assert abs(float(np.linalg.norm(embedding)) - 1.0) <= 1e-3
print("EdgeFace smoke test: 512 dimensions, norme L2 valide")

