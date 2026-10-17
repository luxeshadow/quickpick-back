<?php

namespace App\Jobs;

use App\Contracts\Storage\MediaStorage;
use App\Models\MediaDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendManualMediaDeliveryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $deliveryId) {}

    public function handle(MediaStorage $storage): void
    {
        $delivery = MediaDelivery::with(['media', 'accessLink.event'])->find($this->deliveryId);
        if (! $delivery || $delivery->status === 'sent') {
            return;
        }

        $delivery->update(['status' => 'processing', 'error_message' => null]);

        try {
            $items = $delivery->media->map(function ($medium) use ($storage): string {
                $name = e($medium->original_filename);
                $url = e($storage->createSignedDownloadUrl($medium->storage_path, 86400));
                return "<li><a href=\"{$url}\">{$name}</a></li>";
            })->implode('');
            $eventName = e($delivery->accessLink?->event?->name ?? 'votre événement');
            $html = "<p>Voici les photos sélectionnées pour {$eventName}.</p><ul>{$items}</ul><p>Ces liens expirent dans 24 heures.</p>";

            Mail::html($html, function (Message $message) use ($delivery, $eventName): void {
                $message->to($delivery->recipient)
                    ->subject("Vos photos – {$eventName}");
            });

            $delivery->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $exception) {
            $delivery->update(['status' => 'failed', 'error_message' => $exception->getMessage()]);
            throw $exception;
        }
    }
}


