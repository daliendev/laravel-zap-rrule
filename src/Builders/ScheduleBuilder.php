<?php

namespace ZapRRule\Builders;

use Closure;
use Zap\Builders\ScheduleBuilder as BaseScheduleBuilder;
use ZapRRule\Data\RRuleFrequencyConfig;

/**
 * Extends the core fluent builder with the rrule() method.
 *
 * BaseScheduleBuilder::$attributes is private, so we use Closure::bind
 * scoped to the parent class to write into it.
 */
class ScheduleBuilder extends BaseScheduleBuilder
{
    public function rrule(string $rrule): static
    {
        $config = new RRuleFrequencyConfig($rrule);

        Closure::bind(function () use ($rrule, $config) {
            $this->attributes['is_recurring']     = true;
            $this->attributes['frequency']        = 'rrule';
            $this->attributes['frequency_config'] = $config;
        }, $this, BaseScheduleBuilder::class)();

        return $this;
    }
}
