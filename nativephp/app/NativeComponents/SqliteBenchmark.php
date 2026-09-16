<?php

namespace App\NativeComponents;

use App\Benchmark;
use Generator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;
use Throwable;

/**
 * SuperNative benchmark screen: the same App\Benchmark cases as the /run
 * route, driven from the native UI's runloop instead of a WebView request.
 *
 * Each SQL call of a timed body is one runloop round-trip: the loop wakes
 * (nativephp_element_wait_event with the 1 ms poll floor), PHP runs one
 * call, the screen re-renders and publishes, then the loop waits again.
 * The case timer spans every round-trip, which matches how the JavaScript
 * apps await each native SQLite call.
 */
class SqliteBenchmark extends NativeComponent
{
    public string $status = 'ready';

    public array $results = [];

    public array $metadata = [];

    public int $caseIndex = 0;

    public ?string $error = null;

    private ?Benchmark $benchmark = null;

    private array $cases = [];

    private ?Generator $steps = null;

    public function start(): void
    {
        if ($this->status === 'running') return;
        $this->results = [];
        $this->metadata = [];
        $this->caseIndex = 0;
        $this->error = null;
        $this->steps = null;
        $this->status = 'running';
    }

    public function step(): void
    {
        if ($this->status !== 'running') return;
        try {
            if ($this->caseIndex === 0 && $this->steps === null) {
                $this->benchmark = new Benchmark(DB::connection()->getPdo());
                $this->cases = $this->benchmark->cases();
                $this->metadata = $this->benchmark->prepare();
            }
            if ($this->steps === null) {
                $this->steps = $this->benchmark->steps($this->cases[$this->caseIndex]);
                $this->steps->current();
            } else {
                $this->steps->next();
            }
            if ($this->steps->valid()) return;
            $this->results[] = $this->steps->getReturn();
            $this->steps = null;
            $this->caseIndex++;
            if ($this->caseIndex < count($this->cases)) return;
            $report = $this->benchmark->report($this->metadata, $this->results, 'Laravel SQLite PDO (SuperNative, one runloop round-trip per SQL call)');
            $this->metadata = $report['metadata'];
            file_put_contents(storage_path('app/sqlite-benchmark-native-results.json'), json_encode($report, JSON_THROW_ON_ERROR));
            $this->status = 'complete';
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
            $this->status = 'error';
        }
    }

    public function openWebView(): void
    {
        if ($this->status === 'running') return;
        $this->exitToWeb('/');
    }

    /** Opens the WebView report page, which logs the saved report to logcat for release-build collection. */
    public function exportReport(): void
    {
        if ($this->status !== 'complete') return;
        $this->exitToWeb('/native-report');
    }

    public function render(): View
    {
        if ($this->status === 'running') $this->step();
        return view('native.sqlite-benchmark');
    }
}
