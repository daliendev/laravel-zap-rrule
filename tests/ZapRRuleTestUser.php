<?php

namespace ZapRRule\Tests;

use Illuminate\Database\Eloquent\Model;
use Zap\Models\Concerns\HasSchedules;

class ZapRRuleTestUser extends Model
{
    use HasSchedules;

    protected $table = 'zap_rrule_test_users';

    protected $fillable = ['name', 'email'];
}
