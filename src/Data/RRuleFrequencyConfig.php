<?php

namespace ZapRRule\Data;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use RRule\RRule;
use Zap\Data\FrequencyConfig;
use Zap\Models\Schedule;

class RRuleFrequencyConfig extends FrequencyConfig
{
    private ?RRule $rruleInstance = null;

    public function __construct(
        public readonly string $rrule,
        public ?string $dtstart = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            rrule: $data['rrule'],
            dtstart: $data['dtstart'] ?? null,
        );
    }

    /**
     * Derive DTSTART from the schedule's start_date so interval-based rules
     * (e.g. FREQ=WEEKLY;INTERVAL=2) are anchored to the right origin.
     */
    public function setStartFromStartDate(CarbonInterface $startDate): self
    {
        $this->dtstart = $startDate->format('Ymd\THis');
        $this->rruleInstance = null; // invalidate cached instance

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'rrule'   => $this->rrule,
            'dtstart' => $this->dtstart,
        ]);
    }

    public function getNextRecurrence(CarbonInterface $current): CarbonInterface
    {
        foreach ($this->getRRule() as $occurrence) {
            $date = Carbon::instance($occurrence);

            if ($date->greaterThan($current)) {
                return $date;
            }
        }

        // Finite rule exhausted — return next day as safe fallback.
        return $current->copy()->addDay();
    }

    public function shouldCreateInstance(CarbonInterface $date): bool
    {
        return $this->getRRule()->occursAt($date->toDateString());
    }

    public function shouldCreateRecurringInstance(Schedule $schedule, CarbonInterface $date): bool
    {
        if ($date->lt($schedule->start_date)) {
            return false;
        }

        if ($schedule->end_date && $date->gt($schedule->end_date)) {
            return false;
        }

        return $this->shouldCreateInstance($date);
    }

    private function getRRule(): RRule
    {
        if ($this->rruleInstance === null) {
            $rruleString = $this->rrule;

            if ($this->dtstart && ! str_contains(strtoupper($rruleString), 'DTSTART')) {
                $rruleString = "DTSTART:{$this->dtstart}\nRRULE:{$rruleString}";
            }

            $this->rruleInstance = new RRule($rruleString);
        }

        return $this->rruleInstance;
    }
}
