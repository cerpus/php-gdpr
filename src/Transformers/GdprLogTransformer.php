<?php

namespace Cerpus\Gdpr\Transformers;

use Cerpus\Gdpr\Models\GdprLog;
use League\Fractal\TransformerAbstract;

class GdprLogTransformer extends TransformerAbstract
{
    public function transform(GdprLog $log)
    {
        return [
            'status' => $log->status,
            'message' => $log->message,
            'startTs' => (int)$log->created_at->format('U'),
            'endTs' => (int)$log->updated_at->format('U'),
        ];
    }
}
