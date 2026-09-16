param([string]$Output = 'results/react-native.json')
$lines = & adb logcat -d -v raw
$done = @($lines | Select-String 'SQLITE_BENCHMARK_DONE (\d+)' | ForEach-Object { $_.Matches[0].Groups[1].Value })
if ($done.Count -eq 0) { throw 'No completed React Native benchmark in logcat.' }
$runId = $done[-1]
$parts = @{}
$total = 0
foreach ($line in $lines) {
    if ($line -match "SQLITE_BENCHMARK_PART $runId (\d+)/(\d+) (.*)") {
        $parts[[int]$Matches[1]] = $Matches[3]
        $total = [int]$Matches[2]
    }
}
if ($parts.Count -ne $total) { throw "Expected $total result chunks; found $($parts.Count)." }
$json = (1..$total | ForEach-Object { $parts[$_] }) -join ''
$report = $json | ConvertFrom-Json
if ($report.metadata.integrity_check -ne 'ok' -or $report.results.Count -ne 17) { throw 'Incomplete benchmark report.' }
$target = Join-Path (Get-Location) $Output
New-Item -ItemType Directory -Force (Split-Path $target) | Out-Null
Set-Content -LiteralPath $target -Value $json -Encoding utf8
Write-Output $target
