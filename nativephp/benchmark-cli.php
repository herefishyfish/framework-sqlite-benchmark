<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo json_encode((new App\Benchmark(Illuminate\Support\Facades\DB::connection()->getPdo()))->run(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), PHP_EOL;
