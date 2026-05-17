<?php

namespace ZapRRule\Models\Builders;

use Carbon\Carbon;
use Zap\Enums\Frequency;
use Zap\Helper\DateHelper;
use Zap\Models\Builders\ScheduleBuilder as BaseScheduleBuilder;

/**
 * Extends the core Eloquent query builder to include `rrule` schedules
 * in the forDate() scope. The occurrence check itself is deferred to
 * RRuleFrequencyConfig::shouldCreateRecurringInstance() at the PHP level,
 * exactly like the core does for `monthly_ordinal_weekday`.
 */
class ScheduleBuilder extends BaseScheduleBuilder
{
    public function forDate(string $date): static
    {
        $checkDate            = Carbon::parse($date);
        $weekday              = strtolower($checkDate->format('l'));
        $dayOfMonth           = $checkDate->day;
        $isDateInEvenIsoWeek  = DateHelper::isDateInEvenIsoWeek($date);

        return $this
            // ── date range ────────────────────────────────────────────────
            ->where('start_date', '<=', $checkDate)
            ->where(function ($q) use ($checkDate) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $checkDate);
            })

            // ── recurrence logic ──────────────────────────────────────────
            ->where(function ($q) use ($checkDate, $weekday, $dayOfMonth, $isDateInEvenIsoWeek) {

                //
                // 1️⃣  NOT RECURRING — exact start_date (no end_date) or any date in range
                //
                $q->where(function ($nonRecurring) use ($checkDate) {
                    $nonRecurring->where('is_recurring', false)
                        ->where(function ($dateLogic) use ($checkDate) {
                            $dateLogic->whereNotNull('end_date')
                                ->orWhereDate('start_date', $checkDate);
                        });
                })

                    //
                    // 2️⃣  DAILY
                    //
                    ->orWhere(function ($daily) {
                        $daily->where('is_recurring', true)
                            ->where('frequency', Frequency::DAILY->value);
                    })

                    //
                    // 3️⃣  WEEKLY | BI-WEEKLY — weekday inside config
                    //
                    ->orWhere(function ($weekly) use ($weekday) {
                        $weekly->where('is_recurring', true)
                            ->whereIn(
                                'frequency',
                                array_map(
                                    fn (Frequency $f) => $f->value,
                                    Frequency::filteredByWeekday()
                                )
                            )
                            ->whereJsonContains('frequency_config->days', $weekday);
                    })

                    //
                    // 4️⃣  WEEKLY_EVEN | WEEKLY_ODD — weekday inside config
                    //
                    ->orWhere(function ($query) use ($weekday, $isDateInEvenIsoWeek) {
                        $query->where('is_recurring', true)
                            ->where(
                                'frequency',
                                $isDateInEvenIsoWeek
                                    ? Frequency::WEEKLY_EVEN->value
                                    : Frequency::WEEKLY_ODD->value
                            )
                            ->whereJsonContains('frequency_config->days', $weekday);
                    })

                    //
                    // 5️⃣  MONTHLY — day_of_month from config
                    //
                    ->orWhere(function ($monthly) use ($dayOfMonth) {
                        $monthly->where('is_recurring', true)
                            ->whereIn(
                                'frequency',
                                array_map(
                                    fn (Frequency $f) => $f->value,
                                    Frequency::filteredByDaysOfMonth()
                                )
                            )
                            ->where(function ($m) use ($dayOfMonth) {
                                $m->whereJsonContains('frequency_config->days_of_month', $dayOfMonth)
                                    ->orWhere('frequency_config->days_of_month', $dayOfMonth);
                            });
                    })

                    //
                    // 6️⃣  MONTHLY ORDINAL WEEKDAY — day_of_week only; ordinal filtered in PHP
                    //
                    ->orWhere(function ($ordinalWeekday) use ($checkDate) {
                        $ordinalWeekday->where('is_recurring', true)
                            ->where('frequency', 'monthly_ordinal_weekday')
                            ->where('frequency_config->day_of_week', $checkDate->dayOfWeek);
                    })

                    //
                    // 7️⃣  RRULE — all in range; occurrence check delegated to
                    //         RRuleFrequencyConfig::shouldCreateRecurringInstance()
                    //
                    ->orWhere(function ($rrule) {
                        $rrule->where('is_recurring', true)
                            ->where('frequency', 'rrule');
                    });
            });
    }
}
