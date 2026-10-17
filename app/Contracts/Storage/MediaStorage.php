<?php

namespace App\Contracts\Storage;

interface MediaStorage
{
    /** @return array{signed_url: string, token: string, path: string} */
    public function createSignedUploadUrl(string $path): array;

    public function uploadToSignedUrl(string $signedUrl, string $localPath, string $mimeType): void;

    public function createSignedDownloadUrl(string $path, int $expiresIn = 3600): string;

    /** @return array<string, mixed> */
    public function objectInfo(string $path): array;
}

