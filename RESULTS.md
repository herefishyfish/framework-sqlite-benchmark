# Android emulator comparison

These are sequential runs on 2026-09-16 using the `Medium_Phone_API_36.1` emulator and Android debug builds. Each app receives and processes each query result before issuing the next query. Each cell is the median elapsed time in milliseconds for the full case, from five timed samples after one warmup. Lower is faster. All 17 cases passed in every app and mode, and every report ended with `integrity_check=ok`. The **NativePHP per-query** column is the WebView mode that crosses from JavaScript to Laravel for every operation; the **NativePHP SuperNative** column is the native-UI mode, where every SQL call is one native runloop round-trip (poll wake, one PDO call, re-render and frame publish) inside the persistent PHP runtime.

Delta is the percentage difference from NativeScript, calculated from the unrounded medians: `(other / NativeScript - 1) × 100`. Positive means slower; negative means faster. Times are milliseconds.

| Case | NativeScript | React Native | RN Δ | NativePHP per-query | PHP Δ | NativePHP SuperNative | SN Δ |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Schema create/drop (30 cycles) | 131.683 | 166.904 | +26.7% | 1,996.500 | +1,416% | 389.939 | +196.1% |
| Autocommit insert (400) | 543.464 | 724.539 | +33.3% | 13,051.500 | +2,302% | 2,365.687 | +335.3% |
| Transaction insert (400) | 21.442 | 153.195 | +614.5% | 11,900.300 | +55,400% | 1,427.470 | +6,557% |
| Point select (400) | 24.627 | 191.851 | +679.0% | 11,835.000 | +47,957% | 1,453.803 | +5,803% |
| Indexed filter (100) | 16.568 | 49.073 | +196.2% | 3,041.000 | +18,255% | 385.649 | +2,228% |
| Range scan (100) | 12.598 | 56.049 | +344.9% | 3,013.800 | +23,823% | 404.098 | +3,108% |
| Full scan aggregate (40) | 18.935 | 15.825 | -16.4% | 1,219.400 | +6,340% | 167.214 | +783.1% |
| Order with limit (100) | 12.663 | 48.850 | +285.8% | 2,982.300 | +23,451% | 417.811 | +3,199% |
| Join aggregate (100) | 23.158 | 54.955 | +137.3% | 2,919.700 | +12,508% | 439.964 | +1,800% |
| LIKE search (50) | 31.772 | 29.649 | -6.7% | 1,534.400 | +4,729% | 238.388 | +650.3% |
| JSON extract (100) | 154.538 | 95.365 | -38.3% | 3,064.000 | +1,883% | 593.916 | +284.3% |
| Update by primary key (400) | 398.111 | 717.816 | +80.3% | 12,993.500 | +3,164% | 2,778.996 | +598.0% |
| Delete by primary key (200) | 267.851 | 350.416 | +30.8% | 6,811.300 | +2,443% | 1,407.313 | +425.4% |
| Upsert (400) | 536.690 | 925.488 | +72.4% | 13,020.200 | +2,326% | 2,958.123 | +451.2% |
| Transaction rollback (50) | 7.637 | 39.791 | +421.0% | 4,467.900 | +58,403% | 752.853 | +9,758% |
| 4 KiB BLOB insert and length read (100) | 189.807 | 269.282 | +41.9% | 6,227.600 | +3,181% | 1,262.603 | +565.2% |
| Index build (2,000 rows) | 3.345 | 3.123 | -6.6% | 33.800 | +910% | 8.407 | +151.3% |

## Packages used

| App | Framework packages | SQLite package or path |
| --- | --- | --- |
| NativeScript | `@nativescript/core` 8.9.9; `@nativescript/android` 8.9.2 | `@edusperoni/nativescript-sqlite` 0.0.3, pinned [PR 7 fork](https://github.com/edusperoni/nativescript-plugins/pull/7) at `4c2e152e31edbdf289be1eeef0bf67ace44cfcc5` |
| React Native | `react-native` 0.87.1; `react-native-nitro-modules` 0.37.1 | `react-native-nitro-sqlite` 9.7.0 |
| NativePHP | `nativephp/mobile` 4.4.1; `nativephp/mobile-ui` 0.4.0; `laravel/framework` 13.32.0 | Bundled PHP `PDO_SQLITE` through Laravel's default SQLite connection |

Every app reported `journal_mode=wal` and `synchronous=2` (`FULL`). Their SQLite versions differ: NativeScript 3.53.1, React Native 3.49.0, and NativePHP 3.44.2. NativeScript and React Native time each awaited native SQLite call. NativePHP per-query uses the same JavaScript runner but sends every SQL call to Laravel via `fetch`, receives the result, and validates it before the next call. The 400 point selects average about 30 ms per request. This includes Laravel request handling, so that column compares app paths rather than SQLite engine speed. The SuperNative round-trip costs about 3.6 ms per call on this emulator (point select: 1,454 ms for 400 calls) versus about 0.06 ms for NativeScript and 0.48 ms for React Native. NativePHP SuperNative has no JavaScript and no HTTP: a Jetpack Compose button tap starts the PHP port of the suite in the warm PHP runtime, and each SQL call runs on its own runloop tick, so the case time includes one native wake-up (the runtime's 1 ms poll floor), one Blade re-render, and one frame publish per call. That is NativePHP's per-operation framework cost, comparable to the awaited native call in the two JavaScript apps. The WebView **Run PHP loop** mode (`results/nativephp.json`) runs the same PHP without round-trips and is the SQLite floor for NativePHP. Repeat with release builds and multiple complete runs before making performance claims.

The complete reports, including per-sample times, p95, throughput, and metadata, are `results/nativescript.json`, `results/react-native.json`, `results/nativephp-ui.json`, and `results/nativephp-native.json`. Run `python scripts/compare.py results/nativescript.json results/react-native.json results/nativephp-ui.json results/nativephp-native.json` to regenerate the values and deltas.
