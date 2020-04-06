<?php

use Faker\Generator as Faker;

$factory->define(App\Models\HistoricalEmailModification::class, function (Faker $faker) {
    // only top 5 user has historicalPsswordResetRecord
    return [
        'token' => str_random(30),
        'user_id' => rand(1,5),
        'old_email' => $faker->email,
        'new_email' => $faker->email,
        'ip_address' => $faker->ipv4,
        'created_at' => Carbon::now()->subDays(rand(0, 10)),
        'old_email_verified_at' => Carbon::now()->subDays(rand(11, 20)),
        'email_changed_at' => Carbon::now()->subDays(rand(0, 5)),
    ];
});
