import hashlib
from pathlib import Path

from fastapi import BackgroundTasks, Depends, FastAPI, Header, HTTPException, Request, status

from app.config import Settings, get_settings
from app.schemas import AcceptedJob, HealthResponse, ProcessPhotoCommand
from app.security import verify_hmac
from app.processing import process_photo as process_photo_in_background
from app.schemas import SearchFacesCommand, SearchFacesResult
from app.search import search_faces
from pydantic import ValidationError
from starlette.concurrency import run_in_threadpool


app = FastAPI(title="QuickPick Face Service", version="0.1.0")


def checksum_matches(path: Path, expected: str) -> bool:
    if not expected or not path.is_file():
        return False

    digest = hashlib.sha256()
    with path.open("rb") as model_file:
        for chunk in iter(lambda: model_file.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest().lower() == expected.lower()


def models_are_ready(settings: Settings) -> bool:
    return (
        checksum_matches(settings.yunet_model_path, settings.yunet_model_sha256)
        and checksum_matches(settings.sface_model_path, settings.sface_model_sha256)
    )


@app.get("/health", response_model=HealthResponse)
def health(settings: Settings = Depends(get_settings)) -> HealthResponse:
    ready = models_are_ready(settings)
    return HealthResponse(
        status="ready" if ready else "configuration_required",
        service="quickpick-face-service",
        model_version=settings.model_version,
        models_ready=ready,
    )


@app.post("/v1/photos/process", response_model=AcceptedJob, status_code=status.HTTP_202_ACCEPTED)
async def process_photo(
    request: Request,
    background_tasks: BackgroundTasks,
    x_quickpick_signature: str | None = Header(default=None),
    x_quickpick_timestamp: str | None = Header(default=None),
    settings: Settings = Depends(get_settings),
) -> AcceptedJob:
    body = await request.body()
    verify_hmac(
        body,
        x_quickpick_signature,
        x_quickpick_timestamp,
        settings.laravel_hmac_secret.get_secret_value(),
    )

    command = ProcessPhotoCommand.model_validate_json(body)
    if command.model_version != settings.model_version:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="La version du modèle demandée ne correspond pas à celle du service.",
        )
    if not models_are_ready(settings):
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="Les modèles faciaux ne sont pas encore installés ou leurs checksums sont invalides.",
        )

    background_tasks.add_task(process_photo_in_background, command, settings)
    return AcceptedJob(job_id=command.job_id)


@app.post('/v1/faces/search', response_model=SearchFacesResult)
async def search_photo(
    request: Request,
    x_quickpick_signature: str | None = Header(default=None),
    x_quickpick_timestamp: str | None = Header(default=None),
    settings: Settings = Depends(get_settings),
) -> SearchFacesResult:
    body = await request.body()
    verify_hmac(body, x_quickpick_signature, x_quickpick_timestamp,
                settings.laravel_hmac_secret.get_secret_value())
    if len(body) > 7_100_000:
        raise HTTPException(413, 'Selfie trop volumineux.')
    try:
        command = SearchFacesCommand.model_validate_json(body)
    except ValidationError:
        raise HTTPException(422, 'Commande de recherche invalide.') from None
    if command.model_version != settings.model_version:
        raise HTTPException(409, 'Version du modèle incompatible.')
    if not models_are_ready(settings):
        raise HTTPException(503, 'Les modèles faciaux ne sont pas prêts.')
    return await run_in_threadpool(search_faces, command, settings)
