<?php

namespace App\Actions\AccessLink;

use App\Models\AccessLink;

class UpdateAccessLinkAction
{
    /** @param array<string, mixed> $data */
    public function execute(AccessLink $accessLink, array $data): AccessLink
    {
        $accessLink->update($data);
        return $accessLink->refresh();
    }
}
