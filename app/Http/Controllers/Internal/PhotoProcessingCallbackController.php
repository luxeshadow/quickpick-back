<?php

namespace App\Http\Controllers\Internal;

use App\Enums\PhotoProcessingStatusEnum;
use App\Http\Controllers\Controller;
use App\Jobs\FinalizeEventMediaJob;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PhotoProcessingCallbackController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->verifySignature($request);
        $data = Validator::make($request->json()->all(), [
            'job_id' => ['required', 'uuid'],
            'photo_id' => ['required', 'integer', 'min:1'],
            'event_id' => ['required', 'integer', 'min:1'],
            'model_version' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:processed,failed'],
            'faces_count' => ['nullable', 'integer', 'min:0'],
            'error' => ['nullable', 'string', 'max:2000'],
            'timestamp' => ['required', 'date'],
        ], [
            'job_id.required' => 'Le job_id du callback est obligatoire.',
            'status.in' => 'Le statut du callback doit être processed ou failed.',
        ])->validate();

        $photo = Media::query()->whereKey($data['photo_id'])->where('event_id', $data['event_id'])->firstOrFail();
        if (! hash_equals((string) $photo->processing_job_id, (string) $data['job_id'])
            || ! hash_equals((string) $photo->model_version, (string) $data['model_version'])) {
            throw new AccessDeniedHttpException('Ce callback ne correspond pas au traitement lancé par Laravel.');
        }

        if (! in_array($photo->processing_status, [PhotoProcessingStatusEnum::PROCESSED, PhotoProcessingStatusEnum::FAILED], true)) {
            $photo->update([
                'processing_status' => $data['status'],
                'faces_count' => $data['status'] === 'processed' ? ($data['faces_count'] ?? 0) : 0,
                'processing_error' => $data['status'] === 'failed' ? ($data['error'] ?? 'Traitement facial échoué.') : null,
                'processed_at' => now(),
            ]);
            FinalizeEventMediaJob::dispatch($photo->event_id);
        }

        return response()->json(['message' => 'Callback de traitement enregistré.']);
    }

    private function verifySignature(Request $request): void
    {
        $timestamp = $request->header('X-QuickPick-Timestamp');
        $provided = $request->header('X-QuickPick-Signature');
        $secret = (string) config('face_service.hmac_secret');
        if (! ctype_digit((string) $timestamp) || abs(now()->timestamp - (int) $timestamp) > 300 || strlen($secret) < 32) {
            throw new AccessDeniedHttpException('Signature interne absente ou expirée.');
        }
        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        if (! hash_equals($expected, str_replace('sha256=', '', (string) $provided))) {
            throw new AccessDeniedHttpException('Signature interne invalide.');
        }
    }
}


