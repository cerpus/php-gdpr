<?php
return [
    'deletion-class' => Cerpus\Gdpr\DummyDeletion::class,
    'queue' => env('GDPR_QUEUE', 'default'),
    'connection' => env('GDPR_QUEUE_CONNECTION', 'default')
];
