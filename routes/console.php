<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:reset-daily-occupancy')->dailyAt('06:00');
Schedule::command('app:cleanup-unconfirmed-guest-conventions')->dailyAt('03:00');
