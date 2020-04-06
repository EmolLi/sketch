<?php

use Faker\Generator as Faker;

$factory->define(App\Models\DonationRecord::class, function (Faker $faker) {
    // only top 5 user has historicalPsswordResetRecord
    return [
        'user_id' => rand(1,5),
        'donation_email' => $faker->email,
        'donated_at' => Carbon::now()->subDays(rand(0, 60)),
        'donation_amount' => rand(1,500),
        'show_amount' => true,
        'is_anonymous' => false,
        'donation_message' => $faker->text,
        'donation_kind' => 'patreon',
        'is_claimed' => false,
    ];
});
