<?php

namespace Cerpus\Gdpr\Test;


use Cerpus\Gdpr\Models\GdprDeletionRequest;

class MigrationTest extends TestCase
{
    public function testMigrationsAreRun()
    {
        $requestId = '1';
        $request = new GdprDeletionRequest();
        $request->id = $requestId;
        $request->payload = (object)[];
        $request->save();

        $newRequest = GdprDeletionRequest::find($requestId);
        $this->assertTrue(is_object($newRequest));
        $this->assertCount(1, GdprDeletionRequest::all());
    }
}
