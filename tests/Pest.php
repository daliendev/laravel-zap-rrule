<?php

use ZapRRule\Tests\TestCase;
use ZapRRule\Tests\ZapRRuleTestUser;

uses(TestCase::class)->in('Feature', 'Unit');

// ── Helpers ───────────────────────────────────────────────────────────────────

function createUser(array $attributes = []): ZapRRuleTestUser
{
    return ZapRRuleTestUser::create(array_merge(['name' => 'Test User', 'email' => 'test@example.com'], $attributes));
}
