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
    }

    public function boot(): void
    {
        // Rebind AFTER all providers have registered so we always win,
        // regardless of alphabetical ordering between vendor names.
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
