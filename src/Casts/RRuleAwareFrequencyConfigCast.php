<?php

namespace ZapRRule\Casts;

use Illuminate\Database\Eloquent\Model;
use Zap\Casts\SafeFrequencyConfigCast;
use ZapRRule\Data\RRuleFrequencyConfig;

/**
 * Extends the core cast to deserialise `frequency = 'rrule'` rows
 * back into RRuleFrequencyConfig objects. All other frequency types
 * are handled by the parent as usual.
 */
class RRuleAwareFrequencyConfigCast extends SafeFrequencyConfigCast
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        $frequency = $model->frequency;

        if ($frequency === 'rrule') {
            $configArray = json_decode($value, true);

            if ($configArray === null) {
                return null;
            }

            return RRuleFrequencyConfig::fromArray($configArray);
        }

        return parent::get($model, $key, $value, $attributes);
    }
}
