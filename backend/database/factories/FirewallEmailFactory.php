<?php

use Faker\Generator as Faker;

$factory->define(App\Models\FirewallEmail::class, function (Faker $faker) {
    // only top 5 user has historicalPsswordResetRecord
    return [
        'email' => $faker->email,
    ];
});
