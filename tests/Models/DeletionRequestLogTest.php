<?php

namespace Cerpus\Gdpr\Test\Models;

use Cerpus\Gdpr\Test\TestCase;
use Cerpus\Gdpr\Models\GdprLog;
use Cerpus\Gdpr\Models\GdprDeletionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeletionRequestLogTest extends TestCase
{
    use RefreshDatabase;

    public function testACollectionOfRequestLogsIsSortedByCreatedAtAscending()
    {
        $request = factory(GdprDeletionRequest::class)->create();
        $request->logs()->saveMany(factory(GdprLog::class, 25)->states('random-time')->make());

        $request = $request->fresh();

        $previousOrder = $request->logs->first()->order;

        foreach ($request->logs as $log) {
            $currentOrder = $log->order;
            $this->assertTrue($currentOrder >= $previousOrder);
            $previousOrder = $currentOrder;
        }
    }
}
