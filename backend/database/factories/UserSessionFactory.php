<?php

use Faker\Generator as Faker;

$factory->define(App\Models\HistoricalUserSession::class, function (Faker $faker) {
    // only top 5 user has historicalPsswordResetRecord
    return [
        'user_id' => rand(1,5),
        'created_at' => Carbon::now()->subDays(rand(0, 10)),
        'session_count' => rand(1,5),
        'ip_count' => rand(1,5),
        'ip_band_count' => rand(1,5),
        'device_count' => rand(1,5),
        'mobile_count' => rand(1,5),
        'session_data' => $faker->text,
    ];
});
