<?php

use App\Models\SensorReading;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    SensorReading::where('created_at', '<', now()->subDays(30))->delete();
})->daily()->name('purge-old-readings');
