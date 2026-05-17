<?php

namespace ZapRRule;

use Illuminate\Support\ServiceProvider;
use Zap\Models\Schedule as BaseSchedule;
use Zap\Services\ScheduleService as BaseScheduleService;
use ZapRRule\Models\Schedule;
use ZapRRule\Services\ScheduleService;

class ZapRRuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Only override when the user hasn't set a custom model already.
        if (config('zap.models.schedule', BaseSchedule::class) === BaseSchedule::class) {
            config(['zap.models.schedule' => Schedule::class]);
        }

        // Rebind so Zap::for() returns our fluent builder (with rrule()).
        // ZapServiceProvider registers first (alphabetically earlier), our rebind wins.
        $this->app->singleton('zap', ScheduleService::class);
        $this->app->singleton(BaseScheduleService::class, ScheduleService::class);
    }

    public function provides(): array
    {
        return [
            'zap',
            BaseScheduleService::class,
            ScheduleService::class,
        ];
    }
}
