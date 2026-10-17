<?php

namespace App\Actions\AccessLink;

use App\Models\AccessLink;

class DeleteAccessLinkAction
{
    public function execute(AccessLink $accessLink): void
    {
        $accessLink->delete();
    }
}
