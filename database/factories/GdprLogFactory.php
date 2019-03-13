<?php

use Faker\Generator as Faker;

$factory->define(\Cerpus\Gdpr\Models\GdprLog::class, function (Faker $faker) {
    return [
        'gdpr_deletion_request_id' => $faker->uuid,
        'order' => $faker->randomDigit,
        'status' => $faker->randomElement(['created', 'queued', 'processing', 'error', 'finished']),
        'message' => $faker->sentence,
    ];
});

$factory->state(\Cerpus\Gdpr\Models\GdprLog::class, 'random-time', function (Faker $faker) {
    $created = $faker->dateTimeBetween('-10 years', '-5 days');
    $updated = $faker->dateTimeInInterval($created, '+5 days');

    return [
        'created_at' => $created,
        'updated_at' => $updated,
    ];
});
