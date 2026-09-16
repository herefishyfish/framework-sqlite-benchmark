<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SuperNative report</title>
    <style>body { font-family: sans-serif; margin: 1rem; padding-top: env(safe-area-inset-top); } pre { white-space: pre-wrap; word-break: break-all; font-size: .75rem; }</style>
</head>
<body>
    <h1>SuperNative benchmark report</h1>
    <p id="status">Loading…</p>
    <pre id="json"></pre>
    <script type="module">
        const response = await fetch('/native-report.json');
        if (!response.ok) { document.getElementById('status').textContent = 'No SuperNative report saved yet.'; }
        else {
            const result = await response.json();
            document.getElementById('status').textContent = `${result.driver} · SQLite ${result.metadata.sqlite_version} · integrity ${result.metadata.integrity_check} · logged to logcat`;
            document.getElementById('json').textContent = JSON.stringify(result, null, 2);
            const json = JSON.stringify(result);
            const runId = Date.now();
            const chunks = Math.ceil(json.length / 900);
            for (let i = 0; i < chunks; i++) console.log(`SQLITE_BENCHMARK_PART ${runId} ${i + 1}/${chunks} ${json.slice(i * 900, (i + 1) * 900)}`);
            console.log(`SQLITE_BENCHMARK_DONE ${runId}`);
        }
    </script>
</body>
</html>
