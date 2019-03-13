<?php

namespace Cerpus\Gdpr\Controllers;

use League\Fractal\Manager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use League\Fractal\Resource\Item;
use Illuminate\Routing\Controller;
use League\Fractal\Resource\Collection;
use Cerpus\Gdpr\Requests\DeletionRequest;
use Cerpus\Gdpr\Serializers\GdprSerializer;
use Cerpus\Gdpr\Models\GdprDeletionRequest;
use Cerpus\Gdpr\Jobs\ProcessGdprDeletionRequestJob;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Cerpus\Gdpr\Transformers\GdprDeletionRequestTransformer;

class GdprDeleteController extends Controller
{
    use ValidatesRequests, AuthorizesRequests;

    protected $fractal;

    public function __construct()
    {
        $this->fractal = new Manager();
        $this->fractal->setSerializer(new GdprSerializer());
    }

    public function index(Request $request)
    {
        $deletionRequests = GdprDeletionRequest::with('logs')->get();

        $gdprDeletionRequestItems = new Collection($deletionRequests, new GdprDeletionRequestTransformer);
        $response = $this->fractal->createData($gdprDeletionRequestItems)->toArray();

        return response()->json($response, Response::HTTP_OK);
    }

    public function store(DeletionRequest $request)
    {
        $payload = (object)$request->json()->all();

        $gdprDeletionRequest = new GdprDeletionRequest();
        $gdprDeletionRequest->id = $payload->deletionRequestId;
        $gdprDeletionRequest->payload = $payload;
        $gdprDeletionRequest->save();
        $gdprDeletionRequest->log('received', "Received Gdpr deletion request: " . $gdprDeletionRequest->id);

        $deletionJob = (new ProcessGdprDeletionRequestJob($gdprDeletionRequest))
            ->onQueue(config('gdpr.queue-driver'));
        dispatch($deletionJob);

        // TODO:
        // Detect if the job is using the sync driver or really queued and adjust response based on this

        $gdprDeletionRequest = $gdprDeletionRequest->fresh('logs');
        $gdprDeletionRequestItem = new Item($gdprDeletionRequest, new GdprDeletionRequestTransformer);
        $response = $this->fractal->createData($gdprDeletionRequestItem)->toArray();

        return response()->json($response, Response::HTTP_ACCEPTED);
    }

    public function show(Request $request, $id)
    {
        $deletionRequest = GdprDeletionRequest::with('logs')->find($id);

        if (!$deletionRequest) {
            return response()->json([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "Deletion request with id '$id' not found."
            ], Response::HTTP_NOT_FOUND);
        }

        $gdprDeletionRequestItem = new Item($deletionRequest, new GdprDeletionRequestTransformer);
        $response = $this->fractal->createData($gdprDeletionRequestItem)->toArray();

        return response()->json($response, Response::HTTP_OK);
    }
}
