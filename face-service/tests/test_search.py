import base64
import hashlib
import hmac
import json
import time
from unittest.mock import Mock

import cv2
import httpx
import numpy as np
import pytest
from fastapi import HTTPException
from fastapi.testclient import TestClient

from app.config import Settings, get_settings
from app.main import app
from app.schemas import SearchFacesCommand
from app.search import search_faces


@pytest.fixture
def settings():
    return Settings(_env_file=None, laravel_hmac_secret='x' * 32,
                    supabase_url='https://storage.test', supabase_secret_key='sb_secret_test')


def command():
    _, data = cv2.imencode('.jpg', np.zeros((100, 100, 3), dtype=np.uint8))
    return SearchFacesCommand(event_id=7, model_version='sface-2021dec-v1',
                              image_base64=base64.b64encode(data).decode())


@pytest.mark.parametrize('faces', [[], [Mock(), Mock()]])
def test_requires_exactly_one_face(monkeypatch, settings, faces):
    detector = Mock()
    detector.detect.return_value = faces
    monkeypatch.setattr('app.search.YuNetDetector', lambda _: detector)
    with pytest.raises(HTTPException) as error:
        search_faces(command(), settings)
    assert error.value.status_code == 422


def test_rpc_is_scoped_versioned_padded_and_deduplicated(monkeypatch, settings):
    detector = Mock()
    detector.detect.return_value = [Mock()]
    embedder = Mock()
    embedder.embed.return_value = np.ones(128, dtype=np.float32) / np.sqrt(128)
    monkeypatch.setattr('app.search.YuNetDetector', lambda _: detector)
    monkeypatch.setattr('app.search.SFaceEmbedder', lambda _: embedder)
    captured = {}

    def handler(request):
        captured.update(json.loads(request.content))
        return httpx.Response(200, json=[
            {'photo_id': 2, 'face_index': 0, 'similarity': 0.7},
            {'photo_id': 2, 'face_index': 1, 'similarity': 0.9},
            {'photo_id': 3, 'face_index': 0, 'similarity': 0.2},
        ])
    real_client = httpx.Client
    monkeypatch.setattr('app.search.httpx.Client', lambda **kwargs: real_client(transport=httpx.MockTransport(handler)))
    result = search_faces(command(), settings)
    assert captured['p_event_id'] == 7
    assert captured['p_model_version'] == settings.model_version
    vector = json.loads(captured['p_query_embedding'])
    assert len(vector) == 512 and all(v == 0 for v in vector[128:])
    assert len(result.matches) == 1 and result.matches[0].similarity == 0.9
    assert 'embedding' not in result.model_dump_json()


def test_search_endpoint_requires_hmac_and_matching_version(settings):
    app.dependency_overrides[get_settings] = lambda: settings
    try:
        with TestClient(app) as client:
            assert client.post('/v1/faces/search', json={}).status_code == 401
            body = command().model_copy(update={'model_version': 'other'}).model_dump_json().encode()
            timestamp = str(int(time.time()))
            signature = hmac.new(b'x' * 32, timestamp.encode() + b'.' + body, hashlib.sha256).hexdigest()
            assert client.post('/v1/faces/search', content=body, headers={
                'X-QuickPick-Timestamp': timestamp, 'X-QuickPick-Signature': signature,
            }).status_code == 409
    finally:
        app.dependency_overrides.clear()
