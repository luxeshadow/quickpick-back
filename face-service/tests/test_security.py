import hashlib
import hmac
import time

import pytest
from fastapi import HTTPException

from app.security import verify_hmac


def test_valid_signature_is_accepted() -> None:
    body = b'{"job_id":"test"}'
    timestamp = str(int(time.time()))
    secret = "a-secret-that-is-long-enough-for-tests"
    signature = hmac.new(secret.encode(), timestamp.encode() + b"." + body, hashlib.sha256).hexdigest()

    verify_hmac(body, signature, timestamp, secret)


def test_invalid_signature_is_rejected() -> None:
    with pytest.raises(HTTPException) as error:
        verify_hmac(b"{}", "invalid", str(int(time.time())), "a-secret-that-is-long-enough-for-tests")

    assert error.value.status_code == 401

