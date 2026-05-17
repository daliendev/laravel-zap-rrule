<?php

use Carbon\Carbon;
use Zap\Facades\Zap;
use ZapRRule\Models\Schedule;

describe('RRule — forDate query scope', function () {

    it('forDate is a SQL pre-filter for RRule (all in-range returned)', function () {
        $user = createUser();

        Zap::for($user)
            ->named('MWF')
            ->from('2025-01-01')->to('2025-12-31')
            ->addPeriod('09:00', '10:00')
            ->rrule('FREQ=WEEKLY;BYDAY=MO,WE,FR')
            ->save();

        // All three days are within the range → forDate returns the schedule
        expect(Schedule::forDate('2025-01-06')->count())->toBe(1); // Monday
        expect(Schedule::forDate('2025-01-07')->count())->toBe(1); // Tuesday — in range
        expect(Schedule::forDate('2025-01-08')->count())->toBe(1); // Wednesday
    });

    it('PHP-level filter gives exact occurrences from forDate candidates', function () {
        $user = createUser();

        Zap::for($user)
            ->named('MWF')
            ->from('2025-01-01')->to('2025-12-31')
            ->addPeriod('09:00', '10:00')
            ->rrule('FREQ=WEEKLY;BYDAY=MO,WE,FR')
            ->save();

        $monday = \Carbon\Carbon::parse('2025-01-06');
        $tuesday = \Carbon\Carbon::parse('2025-01-07');

        $onMonday = Schedule::forDate($monday)
            ->get()
            ->filter(fn ($s) => $s->frequency_config->shouldCreateRecurringInstance($s, $monday));

        $onTuesday = Schedule::forDate($tuesday)
            ->get()
            ->filter(fn ($s) => $s->frequency_config->shouldCreateRecurringInstance($s, $tuesday));

        expect($onMonday)->toHaveCount(1);
        expect($onTuesday)->toHaveCount(0);
    });

    it('excludes rrule schedules outside their date range', function () {
        $user = createUser();

        Zap::for($user)
            ->named('Limited')
            ->from('2025-03-01')->to('2025-06-30')
            ->addPeriod('09:00', '10:00')
            ->rrule('FREQ=WEEKLY;BYDAY=MO')
            ->save();

        // Before range
        expect(Schedule::forDate('2025-02-24')->count())->toBe(0);
        // In range on a Monday
        expect(Schedule::forDate('2025-03-03')->count())->toBe(1);
        // After range
        expect(Schedule::forDate('2025-07-07')->count())->toBe(0);
    });

    it('returns RRule and native schedules together on the same date', function () {
        $user = createUser();

        Zap::for($user)->named('RRule')->from('2025-01-01')->to('2025-12-31')
            ->addPeriod('08:00', '09:00')->rrule('FREQ=WEEKLY;BYDAY=MO')->save();

        Zap::for($user)->named('Daily')->from('2025-01-01')->to('2025-12-31')
            ->addPeriod('10:00', '11:00')->daily()->save();

        // Monday — both should appear
        $names = Schedule::forDate('2025-01-06')->pluck('name');
        expect($names)->toContain('RRule')->toContain('Daily');
    });

});
