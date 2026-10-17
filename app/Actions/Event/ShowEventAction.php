<?php

namespace App\Actions\Event;

use App\Models\Event;

class ShowEventAction
{
    public function execute(Event $event): Event
    {
        return $event;
    }
}
