<?php

namespace App\Services\Face;

use App\Models\Media;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FaceServiceClient
{
    public function searchFaces(int $eventId, string $image): array
    {
        $body = json_encode([
            'event_id' => $eventId,
            'model_version' => (string) config('face_service.model_version'),
            'image_base64' => base64_encode($image),
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $response = $this->client()
            ->timeout((int) config('face_service.search_timeout', 60))
            ->withBody($body, 'application/json')
            ->withHeaders([
                'X-QuickPick-Timestamp' => $timestamp,
                'X-QuickPick-Signature' => 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, $this->secret()),
            ])->post('/v1/faces/search');
        if ($response->status() === 422) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'selfie' => ['Le selfie doit contenir exactement un visage bien visible, dans une image lisible.'],
            ]);
        }
        if (! $response->successful()) {
            throw new RuntimeException('La recherche faciale est temporairement indisponible.');
        }
        return \Illuminate\Support\Facades\Validator::make($response->json() ?? [], [
            'event_id' => ['required', 'integer', 'in:'.$eventId],
            'model_version' => ['required', 'in:'.config('face_service.model_version')],
            'matches' => ['present', 'array', 'max:500'],
            'matches.*.photo_id' => ['required', 'integer', 'min:1', 'distinct'],
            'matches.*.face_index' => ['required', 'integer', 'min:0'],
            'matches.*.similarity' => ['required', 'numeric', 'between:-1,1'],
        ])->validate();
    }

    public function processPhoto(Media $photo, string $jobId, int $attempt): void
    {
        $payload = [
            'job_id' => $jobId,
            'photo_id' => $photo->id,
            'event_id' => $photo->event_id,
            'storage_path' => $photo->storage_path,
            'model_version' => (string) config('face_service.model_version'),
            'attempt' => $attempt,
            'timestamp' => now()->toIso8601String(),
        ];
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $this->secret());

        $this->client()
            ->withBody($body, 'application/json')
            ->withHeaders([
                'X-QuickPick-Timestamp' => $timestamp,
                'X-QuickPick-Signature' => 'sha256='.$signature,
            ])
            ->post('/v1/photos/process')
            ->throw();
    }

    private function client(): PendingRequest
    {
        $url = (string) config('face_service.url');
        if ($url === '') {
            throw new RuntimeException('Le microservice facial n’est pas configuré.');
        }

        return Http::baseUrl($url)
            ->acceptJson()
            ->timeout((int) config('face_service.timeout', 15));
    }

    private function secret(): string
    {
        $secret = (string) config('face_service.hmac_secret');
        if (strlen($secret) < 32) {
            throw new RuntimeException('FACE_SERVICE_HMAC_SECRET doit contenir au moins 32 caractères.');
        }
        return $secret;
    }
}


