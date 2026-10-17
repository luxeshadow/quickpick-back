<?php

namespace App\Actions\Event;

use App\Models\Event;

class DeleteEventAction
{
    public function execute(Event $event): void
    {
        $event->delete();
    }
}
