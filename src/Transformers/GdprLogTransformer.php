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
            'startTs' => $log->created_at->toIso8601ZuluString(),
            'endTs' => $log->updated_at->toIso8601ZuluString(),
        ];
    }
}
