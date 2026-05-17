<?php

namespace ZapRRule\Tests;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use Zap\ZapServiceProvider;
use ZapRRule\Models\Schedule;
use ZapRRule\Models\SchedulePeriod;
use ZapRRule\ZapRRuleServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::enforceMorphMap(
            map: ['users' => ZapRRuleTestUser::class],
            merge: true,
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            ZapServiceProvider::class,
            ZapRRuleServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('zap', [
            'conflict_detection' => [
                'enabled'        => true,
                'buffer_minutes' => 0,
            ],
            'validation' => [
                'require_future_dates'     => false,
                'max_date_range'           => 3650,
                'min_period_duration'      => 1,
                'max_period_duration'      => 1440,
                'max_periods_per_schedule' => 100,
                'allow_overlapping_periods' => true,
            ],
            'default_rules' => [
                'no_overlap' => [
                    'enabled'    => true,
                    'applies_to' => ['appointment', 'blocked'],
                ],
                'working_hours' => ['enabled' => false, 'start' => '09:00', 'end' => '17:00'],
                'max_duration'  => ['enabled' => false, 'minutes' => 480],
                'no_weekends'   => ['enabled' => false, 'saturday' => true, 'sunday' => true],
            ],
            'cache'  => ['enabled' => false],
            // ZapRRuleServiceProvider will override this, but we set it here for clarity
            'models' => [
                'schedule'        => Schedule::class,
                'schedule_period' => \Zap\Models\SchedulePeriod::class,
            ],
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        include_once __DIR__.'/database/migrations/create_zap_rrule_test_users_table.php';
        (new \CreateZapRRuleTestUsersTable)->up();

        $this->loadMigrationsFrom(__DIR__.'/../vendor/laraveljutsu/zap/database/migrations');
    }
}
