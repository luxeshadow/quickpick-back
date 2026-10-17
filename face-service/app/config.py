from functools import lru_cache
from pathlib import Path

from pydantic import Field, SecretStr
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        extra="ignore",
    )

    app_env: str = "local"
    app_host: str = "0.0.0.0"
    app_port: int = 8001
    log_level: str = "INFO"

    laravel_hmac_secret: SecretStr = Field(min_length=32)
    supabase_url: str
    supabase_secret_key: SecretStr
    supabase_media_bucket: str = "event-media"

    model_version: str = "sface-2021dec-v1"
    match_threshold: float = Field(default=0.363, ge=0, le=1)
    match_limit: int = Field(default=100, ge=1, le=500)
    yunet_model_path: Path = Path("models/face_detection_yunet_2023mar.onnx")
    yunet_model_sha256: str = "8f2383e4dd3cfbb4553ea8718107fc0423210dc964f9f4280604804ed2552fa4"
    sface_model_path: Path = Path("models/face_recognition_sface_2021dec.onnx")
    sface_model_sha256: str = "0ba9fbfa01b5270c96627c4ef784da859931e02f04419c829e83484087c34e79"
    laravel_callback_url: str = "http://127.0.0.1:8000/api/internal/photo-processing/callback"


@lru_cache
def get_settings() -> Settings:
    return Settings()
