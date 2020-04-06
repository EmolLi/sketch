<?php

use Faker\Generator as Faker;

$factory->define(App\Models\HistoricalUserLogin::class, function (Faker $faker) {
    // only top 5 user has historicalPsswordResetRecord
    return [
        'user_id' => rand(1,5),
        'created_at' => Carbon::now()->subDays(rand(0, 10)),
        'ip' => $faker->ipv4,
        'device' => $faker->word,
    ];
});
