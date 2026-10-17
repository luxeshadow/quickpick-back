from datetime import datetime
from uuid import UUID

from pydantic import BaseModel, Field


class ProcessPhotoCommand(BaseModel):
    job_id: UUID
    photo_id: int = Field(gt=0)
    event_id: int = Field(gt=0)
    storage_path: str = Field(min_length=1, max_length=1024)
    model_version: str = Field(min_length=1, max_length=100)
    attempt: int = Field(ge=1)
    timestamp: datetime


class AcceptedJob(BaseModel):
    job_id: UUID
    status: str = "accepted"


class HealthResponse(BaseModel):
    status: str
    service: str
    model_version: str
    models_ready: bool


class SearchFacesCommand(BaseModel):
    event_id: int = Field(gt=0)
    model_version: str = Field(min_length=1, max_length=100)
    image_base64: str = Field(min_length=1, max_length=7_000_000)


class FaceMatch(BaseModel):
    photo_id: int = Field(gt=0)
    face_index: int = Field(ge=0)
    similarity: float = Field(ge=-1, le=1)


class SearchFacesResult(BaseModel):
    event_id: int
    model_version: str
    matches: list[FaceMatch]

