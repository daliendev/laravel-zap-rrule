<?php

namespace ZapRRule\Services;

use Illuminate\Database\Eloquent\Model;
use Zap\Services\ScheduleService as BaseScheduleService;
use ZapRRule\Builders\ScheduleBuilder;

/**
 * Overrides the two factory methods so that Zap::for() and Zap::schedule()
 * return our extended fluent builder (which has the rrule() method).
 */
class ScheduleService extends BaseScheduleService
{
    public function for(Model $schedulable): ScheduleBuilder
    {
        return (new ScheduleBuilder)->for($schedulable);
    }

    public function schedule(): ScheduleBuilder
    {
        return new ScheduleBuilder;
    }
}
