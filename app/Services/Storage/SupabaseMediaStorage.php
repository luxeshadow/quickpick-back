<?php

namespace App\Services\Storage;

use App\Contracts\Storage\MediaStorage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SupabaseMediaStorage implements MediaStorage
{
    public function uploadToSignedUrl(string $signedUrl, string $localPath, string $mimeType): void
    {
        $stream = fopen($localPath, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Impossible de lire le fichier à envoyer.');
        }

        try {
            Http::withBody($stream, $mimeType)
                ->withHeaders(['cache-control' => '3600'])
                ->timeout(300)
                ->put($signedUrl)
                ->throw();
        } finally {
            fclose($stream);
        }
    }

    public function createSignedDownloadUrl(string $path, int $expiresIn = 3600): string
    {
        $response = $this->client()->post(
            '/object/sign/'.$this->encodedBucketAndPath($path),
            ['expiresIn' => $expiresIn]
        )->throw()->json();

        $relativeUrl = $response['signedURL'] ?? $response['signedUrl'] ?? null;
        if (! is_string($relativeUrl)) {
            throw new RuntimeException('Supabase n’a pas retourné d’URL de téléchargement signée.');
        }

        return str_starts_with($relativeUrl, 'http')
            ? $relativeUrl
            : $this->storageUrl().$relativeUrl;
    }

    public function createSignedUploadUrl(string $path): array
    {
        $response = $this->client()->post(
            '/object/upload/sign/'.$this->encodedBucketAndPath($path),
            []
        )->throw()->json();

        $relativeUrl = $response['url'] ?? null;
        $token = $relativeUrl
            ? data_get(parse_url($relativeUrl), 'query')
            : null;
        parse_str((string) $token, $query);

        if (! is_string($relativeUrl) || ! isset($query['token'])) {
            throw new RuntimeException('Supabase n’a pas retourné de token d’upload signé.');
        }

        return [
            'signed_url' => $this->storageUrl().$relativeUrl,
            'token' => (string) $query['token'],
            'path' => $path,
        ];
    }

    public function objectInfo(string $path): array
    {
        return $this->client()
            ->get('/object/info/'.$this->encodedBucketAndPath($path))
            ->throw()
            ->json();
    }

    private function client(): PendingRequest
    {
        $secretKey = (string) config('supabase.secret_key');

        if ($this->storageUrl() === '' || $secretKey === '') {
            throw new RuntimeException('Supabase Storage n’est pas configuré.');
        }

        $request = Http::baseUrl($this->storageUrl())
            ->acceptJson()
            ->asJson()
            ->withHeader('apikey', $secretKey)
            ->timeout(15)
            ->retry(2, 250);

        // Les nouvelles clés sb_secret ne sont pas des JWT et doivent être
        // envoyées uniquement avec apikey. L'ancien service_role reste supporté.
        return str_starts_with($secretKey, 'sb_secret_')
            ? $request
            : $request->withToken($secretKey);
    }

    private function storageUrl(): string
    {
        $projectUrl = rtrim((string) config('supabase.url'), '/');
        return $projectUrl === '' ? '' : $projectUrl.'/storage/v1';
    }

    private function encodedBucketAndPath(string $path): string
    {
        $segments = array_merge([(string) config('supabase.media_bucket')], explode('/', $path));
        return implode('/', array_map('rawurlencode', $segments));
    }
}

