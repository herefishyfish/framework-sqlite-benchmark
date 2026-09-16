<?php

use App\Benchmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::view('/', 'benchmark');
Route::post('/run', function () {
    $report = (new Benchmark(DB::connection()->getPdo()))->run();
    file_put_contents(storage_path('app/sqlite-benchmark-results.json'), json_encode($report, JSON_THROW_ON_ERROR));
    return response()->json($report);
});

Route::post('/sql', function (Request $request) {
    $db = DB::connection()->getPdo();
    $action = $request->input('action');
    if ($action === 'begin') $db->beginTransaction();
    elseif ($action === 'commit') $db->commit();
    elseif ($action === 'rollback') $db->rollBack();
    elseif ($action === 'exec' || $action === 'rows') {
        $statement = $db->prepare($request->input('sql'));
        $statement->execute($request->input('params', []));
        $rows = $action === 'rows' ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
        $statement->closeCursor();
        return response()->json(['rows' => $rows]);
    } else abort(400, 'Unknown benchmark operation');
    return response()->json(['in_transaction' => $db->inTransaction()]);
});

Route::post('/report', function (Request $request) {
    $report = $request->all();
    file_put_contents(storage_path('app/sqlite-benchmark-ui-results.json'), json_encode($report, JSON_THROW_ON_ERROR));
    return response()->json(['saved' => true]);
});

Route::native('/native', App\NativeComponents\SqliteBenchmark::class);

// Shows the saved SuperNative report and logs it to logcat in the same chunk format as the WebView
// benchmark, so scripts/collect-release-logcat.ps1 can collect it from a non-debuggable release build.
Route::view('/native-report', 'native-report');
Route::get('/native-report.json', fn () => response()->file(storage_path('app/sqlite-benchmark-native-results.json')));
