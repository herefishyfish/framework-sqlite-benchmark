param(
    [Parameter(Mandatory)][string]$Serial,
    [Parameter(Mandatory)][string]$Output
)
$lines = & adb -s $Serial logcat -d -v raw
if ($LASTEXITCODE -ne 0) { throw 'Could not read Android logcat.' }
$done = @($lines | Select-String 'SQLITE_BENCHMARK_DONE (\d+)' | ForEach-Object { $_.Matches[0].Groups[1].Value })
if ($done.Count -eq 0) { throw 'No completed benchmark in logcat.' }
$runId = $done[-1]
$parts = @{}
$total = 0
foreach ($line in $lines) {
    if ($line -match "SQLITE_BENCHMARK_PART $runId (\d+)/(\d+) (.*?)(?: -- From line \d+)?$") {
        $parts[[int]$Matches[1]] = $Matches[3]
        $total = [int]$Matches[2]
    }
}
if (-not $total -or $parts.Count -ne $total) { throw "Expected $total result chunks; found $($parts.Count)." }
$json = (1..$total | ForEach-Object { $parts[$_] }) -join ''
$report = $json | ConvertFrom-Json
if ($report.metadata.integrity_check -ne 'ok' -or $report.results.Count -ne 17 -or @($report.results | Where-Object status -ne 'ok').Count) { throw 'Incomplete or failed benchmark report.' }
$target = Join-Path (Get-Location) $Output
New-Item -ItemType Directory -Force (Split-Path $target) | Out-Null
[IO.File]::WriteAllText($target, $json, [Text.UTF8Encoding]::new($false))
Write-Output $target
