<?php

namespace App\Actions\AccessLink;

use App\Models\Event;
use Illuminate\Database\Eloquent\Collection;

class GetEventAccessLinksAction
{
    /** @return Collection<int, \App\Models\AccessLink> */
    public function execute(Event $event): Collection
    {
        return $event->accessLinks()->with('creator')->latest()->get();
    }
}
