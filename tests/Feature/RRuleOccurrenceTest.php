<?php

use Carbon\Carbon;
use Zap\Facades\Zap;
use Zap\Data\DailyFrequencyConfig;
use Zap\Data\WeeklyFrequencyConfig\WeeklyFrequencyConfig;
use ZapRRule\Data\RRuleFrequencyConfig;
use ZapRRule\Models\Schedule;

describe('RRule — occurrence logic', function () {

    it('matches correct weekdays for BYDAY', function () {
        $config = RRuleFrequencyConfig::fromArray([
            'rrule'   => 'FREQ=WEEKLY;BYDAY=MO,WE,FR',
            'dtstart' => '20250101T000000',
        ]);

        expect($config->shouldCreateInstance(Carbon::parse('2025-01-06')))->toBeTrue()  // Mon
            ->and($config->shouldCreateInstance(Carbon::parse('2025-01-07')))->toBeFalse() // Tue
            ->and($config->shouldCreateInstance(Carbon::parse('2025-01-08')))->toBeTrue()  // Wed
            ->and($config->shouldCreateInstance(Carbon::parse('2025-01-09')))->toBeFalse() // Thu
            ->and($config->shouldCreateInstance(Carbon::parse('2025-01-10')))->toBeTrue(); // Fri
    });

    it('matches correct days for BYMONTHDAY', function () {
        $config = RRuleFrequencyConfig::fromArray([
            'rrule'   => 'FREQ=MONTHLY;BYMONTHDAY=1,15',
            'dtstart' => '20250101T000000',
        ]);

        expect($config->shouldCreateInstance(Carbon::parse('2025-01-01')))->toBeTrue()
            ->and($config->shouldCreateInstance(Carbon::parse('2025-01-15')))->toBeTrue()
            ->and($config->shouldCreateInstance(Carbon::parse('2025-01-10')))->toBeFalse()
            ->and($config->shouldCreateInstance(Carbon::parse('2025-02-01')))->toBeTrue();
    });

    it('matches first Monday of month', function () {
        $config = RRuleFrequencyConfig::fromArray([
            'rrule'   => 'FREQ=MONTHLY;BYDAY=1MO',
            'dtstart' => '20250101T000000',
        ]);

        expect($config->shouldCreateInstance(Carbon::parse('2025-01-06')))->toBeTrue()  // 1st Mon Jan
            ->and($config->shouldCreateInstance(Carbon::parse('2025-01-13')))->toBeFalse() // 2nd Mon Jan
            ->and($config->shouldCreateInstance(Carbon::parse('2025-02-03')))->toBeTrue(); // 1st Mon Feb
    });

    it('respects INTERVAL=2 for biweekly rules', function () {
        $config = RRuleFrequencyConfig::fromArray([
            'rrule'   => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=TU',
            'dtstart' => '20250107T000000', // Jan 7 is a Tuesday
        ]);

        expect($config->shouldCreateInstance(Carbon::parse('2025-01-07')))->toBeTrue()  // week 1
            ->and($config->shouldCreateInstance(Carbon::parse('2025-01-14')))->toBeFalse() // week 2
            ->and($config->shouldCreateInstance(Carbon::parse('2025-01-21')))->toBeTrue(); // week 3
    });

    it('getNextRecurrence returns the next matching date', function () {
        $config = RRuleFrequencyConfig::fromArray([
            'rrule'   => 'FREQ=WEEKLY;BYDAY=MO,FR',
            'dtstart' => '20250101T000000',
        ]);

        // From Monday, next should be Friday
        expect($config->getNextRecurrence(Carbon::parse('2025-01-06'))->toDateString())->toBe('2025-01-10');
        // From Friday, next should be Monday
        expect($config->getNextRecurrence(Carbon::parse('2025-01-10'))->toDateString())->toBe('2025-01-13');
    });

    it('shouldCreateRecurringInstance respects schedule date range', function () {
        $user = createUser();

        $schedule = Zap::for($user)
            ->named('Range test')
            ->from('2025-03-01')->to('2025-06-30')
            ->addPeriod('09:00', '10:00')
            ->rrule('FREQ=WEEKLY;BYDAY=MO')
            ->save();

        $config = $schedule->frequency_config;

        expect($config->shouldCreateRecurringInstance($schedule, Carbon::parse('2025-02-24')))->toBeFalse() // before
            ->and($config->shouldCreateRecurringInstance($schedule, Carbon::parse('2025-03-03')))->toBeTrue()  // in range, Monday
            ->and($config->shouldCreateRecurringInstance($schedule, Carbon::parse('2025-03-04')))->toBeFalse() // in range, Tuesday
            ->and($config->shouldCreateRecurringInstance($schedule, Carbon::parse('2025-07-07')))->toBeFalse(); // after
    });

    it('derives DTSTART from schedule start_date for interval rules', function () {
        $user = createUser();

        $schedule = Zap::for($user)
            ->named('Biweekly')
            ->from('2025-01-07')->to('2025-12-31')
            ->addPeriod('14:00', '15:00')
            ->rrule('FREQ=WEEKLY;INTERVAL=2;BYDAY=TU')
            ->save();

        $config = $schedule->frequency_config;

        expect($config->shouldCreateRecurringInstance($schedule, Carbon::parse('2025-01-07')))->toBeTrue()  // start Tue
            ->and($config->shouldCreateRecurringInstance($schedule, Carbon::parse('2025-01-14')))->toBeFalse() // skip
            ->and($config->shouldCreateRecurringInstance($schedule, Carbon::parse('2025-01-21')))->toBeTrue(); // every 2 weeks
    });

});

describe('RRule — native frequencies still work', function () {

    it('daily frequency_config is not replaced', function () {
        $user = createUser();

        $s = Zap::for($user)->named('D')->from('2025-01-01')
            ->addPeriod('08:00', '09:00')->daily()->save();

        expect($s->frequency_config)->toBeInstanceOf(DailyFrequencyConfig::class);
    });

    it('weekly frequency_config is not replaced', function () {
        $user = createUser();

        $s = Zap::for($user)->named('W')->from('2025-01-01')
            ->addPeriod('10:00', '11:00')->weekly(['monday'])->save();

        expect($s->frequency_config)->toBeInstanceOf(WeeklyFrequencyConfig::class);
    });

    it('native forDate scope still works for daily schedules', function () {
        $user = createUser();

        Zap::for($user)->named('Daily')->from('2025-01-01')->to('2025-12-31')
            ->addPeriod('08:00', '09:00')->daily()->save();

        expect(Schedule::forDate('2025-06-15')->count())->toBe(1);
    });

});
