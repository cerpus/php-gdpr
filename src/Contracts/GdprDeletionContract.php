<?php

namespace Cerpus\Gdpr\Contracts;

use Cerpus\Gdpr\Models\GdprDeletionRequest;

interface GdprDeletionContract
{
    public function delete(GdprDeletionRequest $deletionRequest);
}
