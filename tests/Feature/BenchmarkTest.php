<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BenchmarkTest — আর্কিটেকচার বেঞ্চমার্ক কমান্ড টেস্ট
 */
class BenchmarkTest extends TestCase
{
    use RefreshDatabase;

    /**
     * বেঞ্চমার্ক কমান্ড নির্বিঘ্নে সম্পন্ন হওয়া যাচাই।
     */
    public function test_benchmark_command_executes_successfully(): void
    {
        $this->artisan('jugajug:benchmark', [
            '--iterations' => 10,
            '--type' => 'all',
        ])
            ->expectsOutputToContain('JUGAJUG HIGH-SCALE ARCHITECTURE BENCHMARK TOOL')
            ->expectsOutputToContain('বেঞ্চমার্ক ফলাফল রিপোর্ট:')
            ->expectsOutputToContain('Redis Cache')
            ->expectsOutputToContain('Database (SQL)')
            ->expectsOutputToContain('Feed Generation')
            ->assertExitCode(0);
    }
}
