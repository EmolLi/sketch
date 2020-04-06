<?php

use Faker\Generator as Faker;

$factory->define(App\Models\HistoricalPasswordReset::class, function (Faker $faker) {
    // only top 5 user has historicalPsswordResetRecord
    return [
        'user_id' => rand(1,5),
        'ip_address' => $faker->ipv4,
        'created_at' => Carbon::now()->subDays(rand(0, 60)),
        'old_password' => bcrypt('oldsecret'),
    ];
});
