param([string]$Output = 'results/nativescript.json')
$json = (& adb exec-out run-as com.sqlitebenchmark.nativescript cat files/sqlite-benchmark-results.json) -join "`n"
if ($LASTEXITCODE -ne 0) { throw 'Could not read the NativeScript app result file.' }
$report = $json | ConvertFrom-Json
if ($report.metadata.integrity_check -ne 'ok' -or $report.results.Count -ne 17) { throw 'Incomplete benchmark report.' }
$target = Join-Path (Get-Location) $Output
New-Item -ItemType Directory -Force (Split-Path $target) | Out-Null
Set-Content -LiteralPath $target -Value $json -Encoding utf8
Write-Output $target
