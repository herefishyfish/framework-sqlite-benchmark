param(
    [ValidateSet('php', 'ui', 'native')][string]$Mode = 'php',
    [string]$Output = '',
    [string]$Serial = ''
)
$suffix = if ($Mode -eq 'php') { '' } else { "-$Mode" }
if (-not $Output) { $Output = "results/nativephp$suffix.json" }
$file = "sqlite-benchmark$suffix-results.json"
$adbArgs = if ($Serial) { @('-s', $Serial) } else { @() }
$json = (& adb @adbArgs exec-out run-as com.sqlitebenchmark.nativephp cat "app_storage/persisted_data/storage/app/$file") -join "`n"
if ($LASTEXITCODE -ne 0) { throw 'Could not read the NativePHP app result file.' }
$report = $json | ConvertFrom-Json
if ($report.metadata.integrity_check -ne 'ok' -or $report.results.Count -ne 17) { throw 'Incomplete benchmark report.' }
$target = Join-Path (Get-Location) $Output
New-Item -ItemType Directory -Force (Split-Path $target) | Out-Null
Set-Content -LiteralPath $target -Value $json -Encoding utf8
Write-Output $target
