<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('cms:send --type=all')
    ->everyMinute()
    ->when(function () {
        if (\App\Models\Setting::get('auto_send_enabled') !== '1') {
            return false;
        }
        $configuredTime = \App\Models\Setting::get('auto_send_time', '00:05');
        return now()->format('H:i') === $configuredTime;
    })
    ->name('auto-send-all')
    ->withoutOverlapping();
