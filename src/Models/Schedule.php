<?php

namespace ZapRRule\Models;

use Zap\Models\Schedule as BaseSchedule;
use ZapRRule\Casts\RRuleAwareFrequencyConfigCast;
use ZapRRule\Models\Builders\ScheduleBuilder;

class Schedule extends BaseSchedule
{
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            'frequency_config' => RRuleAwareFrequencyConfigCast::class,
        ]);
    }

    public function newEloquentBuilder($query): ScheduleBuilder
    {
        return new ScheduleBuilder($query);
    }
}
