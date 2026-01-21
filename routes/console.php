<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Schedule the social media post processor to run every minute
Schedule::command('social-media:process-scheduled-posts')->everyMinute();

// You can add more scheduled commands here as needed
// For example, cleanup jobs, sync jobs, etc.
// Schedule::command('some:cleanup-command')->daily();
// Schedule::command('another:sync-command')->hourly();