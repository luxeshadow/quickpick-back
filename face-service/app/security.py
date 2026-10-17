import hashlib
import hmac
import time

from fastapi import HTTPException, status


MAX_CLOCK_SKEW_SECONDS = 300


def verify_hmac(body: bytes, signature: str | None, timestamp: str | None, secret: str) -> None:
    if not signature or not timestamp:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Signature interne manquante.")

    try:
        sent_at = int(timestamp)
    except ValueError as exc:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Timestamp interne invalide.") from exc

    if abs(int(time.time()) - sent_at) > MAX_CLOCK_SKEW_SECONDS:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Signature interne expirée.")

    signed_payload = timestamp.encode("utf-8") + b"." + body
    expected = hmac.new(secret.encode("utf-8"), signed_payload, hashlib.sha256).hexdigest()
    provided = signature.removeprefix("sha256=")

    if not hmac.compare_digest(expected, provided):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Signature interne invalide.")

