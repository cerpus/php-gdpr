<?php

namespace Cerpus\Gdpr\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Cerpus\Gdpr\Models\GdprDeletionRequest;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessGdprDeletionRequestJob implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, Queueable;

    public $deletionRequest = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GdprDeletionRequest $deletionRequest)
    {
        $this->deletionRequest = $deletionRequest;
        $this->deletionRequest->log('queued', "Deletion request added to processing queue.");
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        set_time_limit(600); // This can take some time, but ten minutes should be more than enough!
        try {
            $this->deletionRequest->log('processing', "Deletion request is starting processing.");
            $className = config('gdpr.deletion-class');
            $processor = new $className();
            $processor->delete($this->deletionRequest);
            $this->deletionRequest->log('finished', "Processing of deletion request finished.");
        } catch (\Throwable $t) {
            $this->deletionRequest->log('error', "Error processing request: (" . $t->getCode() . ") " . $t->getMessage());
            throw $t;
        }
    }
}
