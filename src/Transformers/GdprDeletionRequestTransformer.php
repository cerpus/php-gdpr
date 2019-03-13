<?php

namespace Cerpus\Gdpr\Transformers;

use League\Fractal\TransformerAbstract;
use Cerpus\Gdpr\Models\GdprDeletionRequest;

class GdprDeletionRequestTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [
        'events'
    ];

    public function transform(GdprDeletionRequest $request)
    {
        $mostRecentEvent = $request->getMostRecentEvent();

        return [
            'deletionRequestId' => (string)$request->id,
            'status' => $mostRecentEvent->status ?? null,
            'since' => ($mostRecentEvent->created_at ?? null) ? $mostRecentEvent->created_at->toIso8601ZuluString() : null,
        ];
    }

    public function includeEvents(GdprDeletionRequest $request)
    {
        return $this->collection($request->logs, new GdprLogTransformer);
    }
}
