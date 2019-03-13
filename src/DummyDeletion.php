<?php

namespace Cerpus\Gdpr;

use Cerpus\Gdpr\Models\GdprDeletionRequest;
use Cerpus\Gdpr\Contracts\GdprDeletionContract;

class DummyDeletion implements GdprDeletionContract
{
    public function delete(GdprDeletionRequest $deletionRequest)
    {
        $deletionRequest->log('processing', "Doing the deletion of request: " . $deletionRequest->id);
        $deletionRequest->log('processing', "I'm just the dummy, and I do nothing, so I'm already done!");
    }
}
