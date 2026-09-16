<?php

namespace Tests\Feature;

use App\NativeComponents\SqliteBenchmark;
use Native\Mobile\Testing\Native;
use Tests\TestCase;

class SqliteBenchmarkNativeTest extends TestCase
{
    public function test_native_screen_runs_the_full_suite_one_call_per_render(): void
    {
        $component = Native::test(SqliteBenchmark::class)
            ->assertSee('Run SuperNative benchmark')
            ->assertSee('Ready')
            ->tap('Run SuperNative benchmark')
            ->assertSet('status', 'running');

        // Every render advances one SQL call, as the device runloop does on each poll tick:
        // 100 renders is fewer than the first case's 6 x 60 calls, so it must still be in progress.
        for ($i = 0; $i < 100; $i++) $component->call('step');
        $component->assertSet('status', 'running')->assertSet('caseIndex', 0)->assertSee('Running 0/17');

        // The harness keeps a snapshot per render, so finish the remaining calls on the instance directly.
        $instance = $component->instance();
        for ($i = 0; $instance->status === 'running' && $i < 100000; $i++) $instance->step();

        $component->call('step')
            ->assertSet('status', 'complete')
            ->assertSee('integrity_check=ok')
            ->assertSee('index_create');
        $this->assertCount(17, $component->get('results'));
        $this->assertSame([], array_filter($component->get('results'), fn ($r) => $r['status'] !== 'ok'));
    }
}
