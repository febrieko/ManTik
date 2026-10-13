<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Automatically check router online/offline status and track uptime/downtime every minute
Schedule::command('mantik:check-routers')->everyMinute();

// Record mikrotik traffic stats every minute
Schedule::command('mantik:record-stats')->everyMinute();

// Aggregate traffic logs into hourly summaries (runs every hour, aggregates previous hour)
Schedule::command('mantik:aggregate-traffic --mode=hourly')->hourly();

// Aggregate hourly summaries into daily summaries (runs at midnight)
Schedule::command('mantik:aggregate-traffic --mode=daily')->dailyAt('00:05');

// Check customer expiring vouchers and low balances
Schedule::command('mantik:check-customer-notifications')->everyFifteenMinutes();
