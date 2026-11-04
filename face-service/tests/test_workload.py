import hashlib
import hmac
import json
import time
from unittest.mock import Mock

import numpy as np
import pytest
from fastapi.testclient import TestClient

from app.config import Settings, get_settings
from app.main import app, process_photo_locked
from app.workload import inference_lock
from app.vision.yunet import YuNetDetector


def test_busy_photo_is_rejected_before_acceptance(monkeypatch):
    settings = Settings(_env_file=None, laravel_hmac_secret='x' * 32,
                        supabase_url='https://test.invalid', supabase_secret_key='test')
    app.dependency_overrides[get_settings] = lambda: settings
    monkeypatch.setattr('app.main.models_are_ready', lambda _: True)
    body = json.dumps({'job_id': '00000000-0000-4000-8000-000000000001',
        'photo_id': 1, 'event_id': 1, 'storage_path': 'test.jpg',
        'model_version': settings.model_version, 'attempt': 1,
        'timestamp': '2026-11-01T00:00:00Z'}).encode()
    timestamp = str(int(time.time()))
    signature = hmac.new(b'x' * 32, timestamp.encode() + b'.' + body, hashlib.sha256).hexdigest()
    inference_lock.acquire()
    try:
        with TestClient(app) as client:
            response = client.post('/v1/photos/process', content=body, headers={
                'X-QuickPick-Timestamp': timestamp, 'X-QuickPick-Signature': signature})
        assert response.status_code == 503
        assert response.headers['retry-after'] == '30'
    finally:
        inference_lock.release()
        app.dependency_overrides.clear()


def test_processing_lock_is_released_after_failure(monkeypatch):
    def fail(*args):
        raise RuntimeError('test')
    monkeypatch.setattr('app.main.process_photo_in_background', fail)
    inference_lock.acquire()
    with pytest.raises(RuntimeError):
        process_photo_locked(None, None)
    assert not inference_lock.locked()


def test_detector_bounds_input_and_maps_coordinates_back(monkeypatch):
    monkeypatch.setenv('DETECTION_MAX_SIDE', '1280')
    detector = YuNetDetector.__new__(YuNetDetector)
    detector._detector = Mock()
    raw = np.array([[10, 20, 30, 40, 15, 25, 25, 25, 20, 30, 15, 35, 25, 35, .99]], dtype=np.float32)
    detector._detector.detect.return_value = (None, raw)
    faces = detector.detect(np.zeros((1280, 2560, 3), dtype=np.uint8))
    detector._detector.setInputSize.assert_called_once_with((1280, 640))
    assert (faces[0].x, faces[0].y, faces[0].width, faces[0].height) == (20, 40, 60, 80)
    assert faces[0].landmarks[0] == (30, 50)
