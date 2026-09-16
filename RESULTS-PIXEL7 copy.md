# Pixel 7 release comparison

One complete run of each signed Android release APK on a connected Pixel 7 on 2026-09-16. The device reported Android 17 (API 37) and `arm64-v8a`. Apps ran one at a time, with no build running during a benchmark. Each value is the median elapsed milliseconds for the **whole case** from five samples after one warmup. Lower is faster.

NativePHP is shown in **SuperNative** mode, the native-UI mode (added 2026-09-17, same device and build type) where every SQL call is one native runloop round-trip inside the persistent PHP runtime. Its PHP loop mode, which has no per-call boundary, is excluded. All 17 cases passed in every app and mode, and each report ended with `integrity_check=ok`.

Delta is the percentage difference from NativeScript, calculated from the unrounded medians: `(other / NativeScript - 1) × 100`. Positive means slower; negative means faster. Times are milliseconds.

| Case | NativeScript | React Native | RN Δ | NativePHP SuperNative | SN Δ |
| --- | ---: | ---: | ---: | ---: | ---: |
| Schema create/drop (30 cycles) | 17.805 | 37.792 | +112.3% | 126.035 | +607.9% |
| Autocommit insert (400) | 42.165 | 147.898 | +250.8% | 869.593 | +1,962% |
| Transaction insert (400) | 9.387 | 160.029 | +1,604.8% | 1,502.930 | +15,911% |
| Point select (400) | 20.695 | 98.108 | +374.1% | 1,539.299 | +7,338% |
| Indexed filter (100) | 7.342 | 44.797 | +510.1% | 356.343 | +4,753% |
| Range scan (100) | 7.058 | 50.232 | +611.7% | 367.475 | +5,106% |
| Full scan aggregate (40) | 8.620 | 36.260 | +320.6% | 160.535 | +1,762% |
| Order with limit (100) | 7.995 | 59.588 | +645.3% | 400.069 | +4,904% |
| Join aggregate (100) | 9.007 | 28.223 | +213.3% | 408.874 | +4,440% |
| LIKE search (50) | 14.425 | 62.169 | +331.0% | 203.173 | +1,308% |
| JSON extract (100) | 335.680 | 294.888 | -12.2% | 458.598 | +36.6% |
| Update by primary key (400) | 52.872 | 96.917 | +83.3% | 1,301.780 | +2,362% |
| Delete by primary key (200) | 23.591 | 46.578 | +97.4% | 491.124 | +1,982% |
| Upsert (400) | 52.688 | 100.056 | +89.9% | 1,029.045 | +1,853% |
| Transaction rollback (50) | 4.257 | 22.884 | +437.6% | 658.243 | +15,363% |
| 4 KiB BLOB insert and length read (100) | 19.174 | 40.336 | +110.4% | 778.651 | +3,961% |
| Index build (2,000 rows) | 0.458 | 0.796 | +73.9% | 4.326 | +844.6% |

## Packages used

| App          | Framework packages                                             | SQLite package or path                                                                                                                                                |
| ------------ | -------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| NativeScript | `@nativescript/core` 8.9.9; `@nativescript/android` 8.9.2  | `@edusperoni/nativescript-sqlite` 0.0.3, pinned [PR 7 fork](https://github.com/edusperoni/nativescript-plugins/pull/7) at `4c2e152e31edbdf289be1eeef0bf67ace44cfcc5` |
| React Native | `react-native` 0.87.1; `react-native-nitro-modules` 0.37.1 | `react-native-nitro-sqlite` 9.7.0                                                                                                                                   |
| NativePHP    | `nativephp/mobile` 4.4.1; `nativephp/mobile-ui` 0.4.0; `laravel/framework` 13.32.0 | Bundled PHP `PDO_SQLITE` through Laravel's default SQLite connection                                                                                                |

The raw reports contain all samples, p95, throughput, and metadata: [NativeScript](results/pixel7/nativescript-release.json), [React Native](results/pixel7/react-native-release.json), [NativePHP per-query](results/pixel7/nativephp-ui-release.json), and [NativePHP SuperNative](results/pixel7/nativephp-native-release.json). Regenerate the values and deltas with:

```powershell
python scripts/compare.py results/pixel7/nativescript-release.json results/pixel7/react-native-release.json results/pixel7/nativephp-ui-release.json results/pixel7/nativephp-native-release.json
```

All three reported WAL mode, `synchronous=2` (`FULL`), and 2,000 fixture rows. SQLite versions were 3.53.1 in NativeScript, 3.49.0 in React Native, and 3.44.2 in NativePHP. NativePHP per-query awaits an HTTP request to Laravel/PDO for **each** SQL call and processes query results before issuing the next one. NativePHP SuperNative has no JavaScript and no HTTP: each SQL call runs on its own native runloop tick (poll wake with the runtime's 1 ms floor, one PDO call, Blade re-render, frame publish), about 3.8 ms per call on this device (point select: 1,539 ms for 400 calls) versus about 0.05 ms for NativeScript and 0.25 ms for React Native. These numbers measure the complete app paths and include different call boundaries; they are not SQLite engine-only timings. This is one full run per app, so repeat runs before treating small differences as stable.
