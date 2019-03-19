<?php

namespace Cerpus\Gdpr\Test;

use Illuminate\Http\Response;
use Cerpus\Gdpr\Models\GdprDeletionRequest;
use Illuminate\Foundation\Testing\TestResponse;
use Cerpus\Gdpr\Jobs\ProcessGdprDeletionRequestJob;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class AddGdprDeletionTest extends TestCase
{
    use WithoutMiddleware;

    public function testRouteExist()
    {
        $this->withoutMiddleware();
        $route = route('gdpr.store');
        $path = parse_url($route, PHP_URL_PATH);
        $this->assertEquals('/api/gdpr/delete', $path);
    }

    public function testYouCanStoreANewRequest()
    {
        $this->withoutMiddleware();
        $this->expectsJobs(ProcessGdprDeletionRequestJob::class);

        $payload = [
            'deletionRequestId' => $this->faker->uuid,
            'userId' => $this->faker->uuid,
            'name' => 'Delete Me',
            'emails' => [],
            'phone' => [],
        ];

        /** @var TestResponse $response */
        $response = $this->json('POST', route('gdpr.store'), $payload);
        $payload = (object)$payload;
        $response->assertStatus(202);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJson([
            'deletionRequestId' => $payload->deletionRequestId,
            'status' => 'QUEUED',
            'events' => [],
        ]);


        $deletionRequest = GdprDeletionRequest::find($payload->deletionRequestId);
        $deletionJson = $deletionRequest->payload;
        $this->assertEquals(json_encode($deletionJson), json_encode($payload));
    }

    public function testYouMustUseAnUniqueIdWhenCreatingADeletionRequest()
    {
        $this->withoutMiddleware();
        $this->doesntExpectJobs(ProcessGdprDeletionRequestJob::class);
        $firstEvent = factory(GdprDeletionRequest::class)->create();

        $payload = [
            'deletionRequestId' => $firstEvent->id,
            'userId' => $this->faker->uuid,
        ];

        $this->assertCount(1, GdprDeletionRequest::all());

        /** @var TestResponse $response */
        $response = $this->json('POST', route('gdpr.store'), $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertCount(1, GdprDeletionRequest::all());
    }

    public function testTheRequestMustContainRequestIdAndUserId()
    {
        $this->withoutMiddleware();
        $this->doesntExpectJobs(ProcessGdprDeletionRequestJob::class);

        $payload = [
            'deletionRequestId' => $this->faker->uuid,
        ];

        $response = $this->json('POST', route('gdpr.store'), $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $payload = [
            'userId' => $this->faker->uuid,
        ];
        $response = $this->json('POST', route('gdpr.store'), $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testFormatOfResponseNoRequests()
    {
        $this->withoutMiddleware();
        $response = $this->get(route('gdpr.index'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonStructure([]);
    }

    public function testFormatOfResponseOneResponse()
    {
        $this->withoutMiddleware();
        $payload = [
            'deletionRequestId' => $this->faker->uuid,
            'userId' => $this->faker->uuid,
        ];
        $this->json('POST', route('gdpr.store'), $payload);
        $response = $this->get(route('gdpr.index'));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'application/json');

        $response->assertJsonStructure([
            'deletionRequestId',
            'status',
            'since',
            'events' => [
                '*' => [
                    'status',
                    'message',
                    'startTs',
                    'endTs',
                ]
            ]
        ]);
    }

    public function testFormatOfResponseMultipleResponses()
    {
        $this->withoutMiddleware();
        for ($i = 0; $i < 2; $i++) {
            $payload = [
                'deletionRequestId' => $this->faker->uuid,
                'userId' => $this->faker->uuid,
            ];
            $this->json('POST', route('gdpr.store'), $payload);
        }

        $response = $this->get(route('gdpr.index'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonCount(2);
        $response->assertJsonStructure([
            '*' => [
                'deletionRequestId',
                'status',
                'since',
                'events' => [
                    '*' => [
                        'status',
                        'message',
                        'startTs',
                        'endTs',
                    ]
                ]
            ]
        ]);
    }

    public function testResponseOfOneDeletionRequest()
    {
        $this->withoutMiddleware();
        $payload = [
            'deletionRequestId' => $this->faker->uuid,
            'userId' => $this->faker->uuid,
        ];
        $this->json('POST', route('gdpr.store'), $payload);

        $response = $this->get(route('gdpr.show', $payload['deletionRequestId']));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonStructure([
            'deletionRequestId',
            'status',
            'since',
            'events' => [
                '*' => [
                    'status',
                    'message',
                    'startTs',
                    'endTs',
                ]
            ]
        ]);
    }

    public function testResponseOfOneDeletionRequestDoesNotExist()
    {
        $this->withoutMiddleware();
        $response = $this->get(route('gdpr.show', 1));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJson([
            'code' => Response::HTTP_NOT_FOUND,
            'message' => "Deletion request with id '1' not found."
        ]);
    }
}
