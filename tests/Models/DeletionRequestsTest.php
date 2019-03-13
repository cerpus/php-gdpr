<?php

namespace Cerpus\Gdpr\Test\Models;

use Cerpus\Gdpr\Test\TestCase;
use Cerpus\Gdpr\Models\GdprLog;
use Cerpus\Gdpr\Models\GdprDeletionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeletionRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function testACollectionOfDeletionRequestsIsSortedByUpdatedAtDescending()
    {
        factory(GdprDeletionRequest::class, 5)->states('random-time')->create();

        $deletionRequests = GdprDeletionRequest::all();

        $previousTime = $deletionRequests->first()->updated_at->timestamp;
        foreach ($deletionRequests as $deletionRequest) {
            $currentTime = $deletionRequest->updated_at->timestamp;
            $this->assertTrue($currentTime <= $previousTime);
            $previousTime = $currentTime;
        }
    }

    public function testLogsAreDeletedWhenRequestIsDeleted()
    {
        $request = factory(GdprDeletionRequest::class)->create();
        $request->logs()->saveMany(factory(GdprLog::class, 2)->states('random-time')->make());
        factory(GdprLog::class)->states('random-time')->create();

        $this->assertCount(3, GdprLog::all());

        $request->delete();

        $this->assertCount(1, GdprLog::all());
    }


    public function testYouCanLogEvents()
    {
        $request = factory(GdprDeletionRequest::class)->create();

        $this->assertCount(0, $request->logs);

        $log = $request->log('received', 'This is my message');
        $this->assertTrue(is_a($log, GdprLog::class));
        $this->assertDatabaseHas('gdpr_logs', ['gdpr_deletion_request_id' => $request->id, 'status' => 'RECEIVED', 'message' => 'This is my message']);
        $request = $request->fresh();
        $this->assertCount(1, $request->logs);
    }

    public function testLoggedEventsGetAnAutoIncrementValueSet()
    {
        /** @var GdprDeletionRequest $request */
        $request = factory(GdprDeletionRequest::class)->create();
        for ($i = 1; $i <= 3; $i++) {
            $request->log('test', "Log #" . $i);
        }

        $lastLog = $request->getMostRecentEvent();

        $this->assertEquals(3, $lastLog->order);
        $this->assertEquals("Log #3", $lastLog->message);
    }

    public function testYouCanSetAnObjectAsPayload()
    {
        $deletionRequest = new GdprDeletionRequest();
        $deletionRequest->id = 1;
        $payload = new \stdClass();
        $payload->id = 1;
        $deletionRequest->payload = $payload;
        $deletionRequest->save();

        $dbRequest = GdprDeletionRequest::find(1);
        $this->assertTrue($dbRequest->payload->id == 1);
    }

    public function testYouCanSetAnArrayAsPayload()
    {
        $deletionRequest = new GdprDeletionRequest();
        $deletionRequest->id = 1;
        $deletionRequest->payload = ['id' => 1];
        $deletionRequest->save();

        $dbRequest = GdprDeletionRequest::find(1);
        $this->assertTrue($dbRequest->payload->id == 1);
    }

    public function testYouCanSetAUnserializableStringAsPayload()
    {
        $deletionRequest = new GdprDeletionRequest();
        $deletionRequest->id = 1;
        $deletionRequest->payload = '{"id": 1}';
        $deletionRequest->save();

        $dbRequest = GdprDeletionRequest::find(1);
        $this->assertTrue($dbRequest->payload->id == 1);
    }

    /**
     * @expectedException Cerpus\Gdpr\Exceptions\GdprPayloadException
     */
    public function testSettingAnUnserializableStringAsPayloadThrowsAnException()
    {
        $deletionRequest = new GdprDeletionRequest();
        $deletionRequest->id = 1;
        $deletionRequest->payload = '"id": 1';
    }

    /**
     * @expectedException Cerpus\Gdpr\Exceptions\GdprPayloadException
     * @expectedExceptionMessage Payload value is not an object, an array or a serializable string.
     */
    public function testSettingAnIntAsPayloadThrowsAnException()
    {
        $deletionRequest = new GdprDeletionRequest();
        $deletionRequest->id = 1;
        $deletionRequest->payload = (int)1;
        $deletionRequest->save();
    }

    public function testAddingALogUpdatesTheRequestUpdatedAtField()
    {
        $request = factory(GdprDeletionRequest::class)->states('random-time')->create();

        $originalUpdatedTs = $request->updated_at->timestamp;

        $request->log('test', 'Message');

        $updatedTs = $request->fresh()->updated_at->timestamp;

        $this->assertTrue($originalUpdatedTs < $updatedTs);
    }
}
