<?php

use Carbon\Carbon;
use Zap\Facades\Zap;
use ZapRRule\Data\RRuleFrequencyConfig;
use ZapRRule\Models\Schedule;

describe('RRule — Persistence', function () {

    it('saves and retrieves a weekly rrule schedule', function () {
        $user = createUser();

        $schedule = Zap::for($user)
            ->named('MWF Standup')
            ->from('2025-01-06')->to('2025-12-31')
            ->addPeriod('09:00', '09:30')
            ->rrule('FREQ=WEEKLY;BYDAY=MO,WE,FR')
            ->save();

        expect($schedule)->toBeInstanceOf(Schedule::class)
            ->and($schedule->is_recurring)->toBeTrue()
            ->and($schedule->frequency)->toBe('rrule')
            ->and($schedule->frequency_config)->toBeInstanceOf(RRuleFrequencyConfig::class)
            ->and($schedule->frequency_config->rrule)->toBe('FREQ=WEEKLY;BYDAY=MO,WE,FR');

        $fresh = Schedule::find($schedule->id);
        expect($fresh->frequency_config)->toBeInstanceOf(RRuleFrequencyConfig::class)
            ->and($fresh->frequency_config->rrule)->toBe('FREQ=WEEKLY;BYDAY=MO,WE,FR');
    });

    it('saves and retrieves a monthly rrule schedule', function () {
        $user = createUser();

        $schedule = Zap::for($user)
            ->named('Billing run')
            ->from('2025-01-01')->to('2025-12-31')
            ->addPeriod('10:00', '11:00')
            ->rrule('FREQ=MONTHLY;BYMONTHDAY=1,15')
            ->save();

        $fresh = Schedule::find($schedule->id);
        expect($fresh->frequency_config)->toBeInstanceOf(RRuleFrequencyConfig::class)
            ->and($fresh->frequency_config->rrule)->toBe('FREQ=MONTHLY;BYMONTHDAY=1,15');
    });

    it('stores the extended Schedule model', function () {
        $user = createUser();

        $schedule = Zap::for($user)
            ->named('Test')
            ->from('2025-01-01')
            ->addPeriod('09:00', '10:00')
            ->rrule('FREQ=DAILY;INTERVAL=3')
            ->save();

        expect($schedule)->toBeInstanceOf(\ZapRRule\Models\Schedule::class);
    });

});
