<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requires the server cron entry: * * * * * php artisan schedule:run
// The overlap lock expires after 10 minutes so a killed run cannot block retries for a day.
Schedule::command('contact-requests:dispatch')->everyMinute()->withoutOverlapping(10);
Schedule::command('contact-requests:monitor')->hourly();

// CRM delivery is independent of the email: its own claims, retries and lock. The command does
// nothing until STARTENTREPRISE_LEADS_ENABLED=true.
Schedule::command('contact-requests:crm-sync')->everyMinute()->withoutOverlapping(10);
Schedule::command('contact-requests:crm-monitor')->hourly();
