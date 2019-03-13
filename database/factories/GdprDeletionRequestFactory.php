<?php

use Faker\Generator as Faker;

$factory->define(\Cerpus\Gdpr\Models\GdprDeletionRequest::class, function (Faker $faker) {
    $userId = $faker->uuid;
    $requestId = $faker->uuid;

    return [
        'id' => $requestId,
        'payload' => (object)['deletionRequestId' => $requestId, 'userId' => $userId],
    ];
});

$factory->state(\Cerpus\Gdpr\Models\GdprDeletionRequest::class, 'random-time', function (Faker $faker) {
    $created = $faker->dateTimeBetween('-10 years', '-5 days');
    $updated = $faker->dateTimeInInterval($created, '+5 days');

    return [
        'created_at' => $created,
        'updated_at' => $updated,
    ];
});
