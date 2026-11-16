<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ServiceCheckTest extends TestCase
{
    public function test_service_check_reads_database_and_pings_redis_without_mutations(): void
    {
        DB::shouldReceive('select')->once()->with('SELECT 1 AS connection_check')->andReturn([(object) ['connection_check' => 1]]);
        Redis::shouldReceive('connection->ping')->once()->andReturn('PONG');
        $this->assertSame(0, Artisan::call('app:check-services'));
        $output = Artisan::output();
        $this->assertStringContainsString('Database: OK', $output);
        $this->assertStringContainsString('Redis: OK', $output);
    }

    public function test_service_failures_return_failure_without_exposing_exception_details(): void
    {
        DB::shouldReceive('select')->once()->andThrow(new \RuntimeException('sensitive-database-password'));
        Redis::shouldReceive('connection->ping')->once()->andReturn(false);
        $this->assertSame(1, Artisan::call('app:check-services'));
        $output = Artisan::output();
        $this->assertStringContainsString('Database: FAILED', $output);
        $this->assertStringContainsString('Redis: FAILED', $output);
        $this->assertStringNotContainsString('sensitive-database-password', $output);
    }
}
