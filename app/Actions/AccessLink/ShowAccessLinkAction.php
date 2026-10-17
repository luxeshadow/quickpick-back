<?php

namespace App\Actions\AccessLink;

use App\Models\AccessLink;

class ShowAccessLinkAction
{
    public function execute(AccessLink $accessLink): AccessLink
    {
        return $accessLink->load(['event', 'creator']);
    }
}
