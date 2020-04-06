<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\DonationRecord;
use App\Models\FirewallEmail;
use App\Models\HistoricalEmailModification;
use App\Models\HistoricalPasswordReset;
use App\Models\HistoricalUserLogin;
use App\Models\HistoricalUserSession;


class AdminSystemSeeder extends Seeder
{
    /**
    * Run the database seeds.
    *
    * @return void
    */
    public function run()
    {
        $donationRecords = factory(DonationRecord::class)->times(10)->create();
        $firewallEmails = factory(FirewallEmail::class)->times(10)->create();
        $emailModifications = factory(HistoricalEmailModification::class)->times(10)->create();
        $passwordResets = factory(HistoricalPasswordReset::class)->times(10)->create();
        $userLogins = factory(HistoricalUserLogin::class)->times(10)->create();
        $userSessions = factory(HistoricalUserSession::class)->times(10)->create();
    }
}
