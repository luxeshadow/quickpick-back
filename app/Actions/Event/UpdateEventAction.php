<?php

namespace App\Actions\Event;

use App\Models\Event;

class UpdateEventAction
{
    /** @param array<string, mixed> $data */
    public function execute(Event $event, array $data): Event
    {
        $event->update($data);

        return $event->refresh();
    }
}
