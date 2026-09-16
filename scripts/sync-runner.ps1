$source = Join-Path $PSScriptRoot '..\benchmark\runner.js'
Copy-Item -LiteralPath $source -Destination (Join-Path $PSScriptRoot '..\react-native\benchmark\runner.js') -Force
Copy-Item -LiteralPath $source -Destination (Join-Path $PSScriptRoot '..\nativescript\src\benchmark\runner.js') -Force
New-Item -ItemType Directory -Force (Join-Path $PSScriptRoot '..\nativephp\public\benchmark') | Out-Null
Copy-Item -LiteralPath $source -Destination (Join-Path $PSScriptRoot '..\nativephp\public\benchmark\runner.js') -Force
