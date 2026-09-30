# Pixel 7 release comparison

Signed Android release APKs were run on the same connected Pixel 7 (`arm64-v8a`, Android 17/API 37). NativeScript and React Native were measured on 2026-09-30 after upgrading to NativeScript 9.1 with `@edusperoni/nativescript-sqlite` 0.0.7 and `react-native-nitro-sqlite` 10.0.0. NativePHP per-query was measured on 2026-09-16 and NativePHP SuperNative on 2026-09-17. Apps ran one at a time, with no build running during each benchmark. Each value is the median elapsed milliseconds for the **whole case** from five samples after one warmup. Lower is faster.

NativeScript and React Native were each run seven times in a row; their columns show the median of the seven run medians for each case, so one noisy run cannot move a value. NativePHP columns use one complete run per mode. All 17 cases passed and `integrity_check=ok` in every run. The phone reported a battery temperature of 30.8–31.1 °C and thermal status 0 (no throttling) before each NativeScript and React Native run.

Delta is the percentage difference from NativeScript, calculated from the unrounded medians: `(other / NativeScript - 1) × 100`. Positive means slower; negative means faster. Times are milliseconds.

| Case                                    | NativeScript | React Native |    RN Δ | NativePHP per-query |       PHP Δ | NativePHP SuperNative |     SN Δ |
| --------------------------------------- | -----------: | -----------: | ------: | ------------------: | ----------: | --------------------: | -------: |
| Schema create/drop (30 cycles)          |       16.078 |       30.756 |  +91.3% |           3,857.400 |    +23,892% |               126.035 |  +683.9% |
| Autocommit insert (400)                 |       46.370 |       97.157 | +109.5% |          30,637.300 |    +65,971% |               869.593 |  +1,775% |
| Transaction insert (400)                |        1.996 |      148.693 | +7,350% |          26,278.200 | +1,316,443% |             1,502.930 | +75,197% |
| Point select (400)                      |        8.149 |      248.883 | +2,954% |          25,822.300 |   +316,777% |             1,539.299 | +18,789% |
| Indexed filter (100)                    |        3.655 |       82.506 | +2,157% |           6,742.100 |   +184,362% |               356.343 |  +9,649% |
| Range scan (100)                        |        4.198 |       87.099 | +1,975% |           6,649.900 |   +158,306% |               367.475 |  +8,654% |
| Full scan aggregate (40)                |        6.544 |       51.308 | +684.0% |           2,675.400 |    +40,783% |               160.535 |  +2,353% |
| Order with limit (100)                  |        5.195 |       91.349 | +1,658% |           6,677.000 |   +128,427% |               400.069 |  +7,601% |
| Join aggregate (100)                    |        4.318 |       74.740 | +1,631% |           6,598.900 |   +152,723% |               408.874 |  +9,369% |
| LIKE search (50)                        |       15.126 |      140.529 | +829.1% |           3,389.200 |    +22,306% |               203.173 |  +1,243% |
| JSON extract (100)                      |      150.288 |      653.972 | +335.1% |           7,622.200 |     +4,972% |               458.598 |  +205.1% |
| Update by primary key (400)             |       47.993 |      122.732 | +155.7% |          27,161.600 |    +56,495% |             1,301.780 |  +2,612% |
| Delete by primary key (200)             |       23.396 |       46.776 |  +99.9% |          13,440.000 |    +57,346% |               491.124 |  +1,999% |
| Upsert (400)                            |       49.409 |       94.989 |  +92.3% |          28,866.900 |    +58,324% |             1,029.045 |  +1,983% |
| Transaction rollback (50)               |        1.762 |       26.723 | +1,417% |           9,826.000 |   +557,562% |               658.243 | +37,258% |
| 4 KiB BLOB insert and length read (100) |       19.027 |       57.835 | +204.0% |          14,017.200 |    +73,570% |               778.651 |  +3,992% |
| Index build (2,000 rows)                |        0.461 |        0.875 |  +89.9% |              45.000 |     +9,661% |                 4.326 |  +838.4% |

The previous NativeScript and React Native builds, and how these runs compare with them, are in [RESULTS-PIXEL7-UPGRADE.md](RESULTS-PIXEL7-UPGRADE.md). A head-to-head comparison of the upgraded NativeScript and React Native builds, with ranges and per-operation costs, is in [RESULTS-PIXEL7-NS-VS-RN.md](RESULTS-PIXEL7-NS-VS-RN.md).

## Packages used

| App          | Framework packages                                                                       | SQLite package or path                                                                            |
| ------------ | ---------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------- |
| NativeScript | `@nativescript/core` 9.1.2; `@nativescript/android` 9.1.1                            | `@edusperoni/nativescript-sqlite` 0.0.7, Node-API backend, with the Android build fixes in `nativescript/patches/` |
| React Native | `react-native` 0.87.1; `react-native-nitro-modules` 0.37.1                           | `react-native-nitro-sqlite` 10.0.0                                                               |
| NativePHP    | `nativephp/mobile` 4.4.1; `nativephp/mobile-ui` 0.4.0; `laravel/framework` 13.32.0 | Bundled PHP `PDO_SQLITE` through Laravel's default SQLite connection                             |

The seven-run reports are [NativeScript](results/pixel7/nativescript-release-0.0.7-median7.json) and [React Native](results/pixel7/react-native-release-nitro10-median7.json); each lists every run median per case and the run files it was built from. The individual runs, with all samples, p95, throughput, and metadata, are `results/pixel7/nativescript-release-0.0.7.json`, `nativescript-release-0.0.7-2.json` to `-7.json`, `react-native-release-nitro10.json`, and `react-native-release-nitro10-2.json` to `-7.json`. NativePHP: [per-query](results/pixel7/nativephp-ui-release.json) and [SuperNative](results/pixel7/nativephp-native-release.json). Rebuild the seven-run reports and regenerate the main table with:

```powershell
$ns = @("results/pixel7/nativescript-release-0.0.7.json") + (2..7 | ForEach-Object { "results/pixel7/nativescript-release-0.0.7-$_.json" })
$rn = @("results/pixel7/react-native-release-nitro10.json") + (2..7 | ForEach-Object { "results/pixel7/react-native-release-nitro10-$_.json" })
node scripts/aggregate-runs.mjs results/pixel7/nativescript-release-0.0.7-median7.json @ns
node scripts/aggregate-runs.mjs results/pixel7/react-native-release-nitro10-median7.json @rn
python scripts/compare.py results/pixel7/nativescript-release-0.0.7-median7.json results/pixel7/react-native-release-nitro10-median7.json results/pixel7/nativephp-ui-release.json results/pixel7/nativephp-native-release.json
```

All reports show WAL mode, `synchronous=2` (`FULL`), and 2,000 fixture rows. SQLite versions were 3.53.1 in NativeScript, 3.49.0 in React Native, and 3.44.2 in NativePHP. NativePHP per-query awaits an HTTP request to Laravel/PDO for **each** SQL call and processes query results before issuing the next one. NativePHP SuperNative has no JavaScript and no HTTP: each SQL call runs on its own native runloop tick (poll wake with the runtime's 1 ms floor, one PDO call, Blade re-render, frame publish), about 3.8 ms per call on this device (point select: 1,539 ms for 400 calls) versus about 0.02 ms for NativeScript and 0.62 ms for React Native. These numbers measure the complete app paths and include different call boundaries; they are not SQLite engine-only timings. Cases that take only a few milliseconds per sample vary by 40–80% between runs even for NativeScript, and React Native varies more (JSON extract 450–913 ms across its seven runs); the upgrade page lists each case’s range. Run dates differ between the JavaScript apps and NativePHP.
