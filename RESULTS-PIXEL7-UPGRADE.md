# Pixel 7 upgrade comparison

This page compares the NativeScript and React Native builds before and after the 2026-09-30 upgrade. Every run used a signed release APK on the same connected Pixel 7 (`arm64-v8a`, Android 17/API 37), one app at a time, with no build running during each benchmark. Each value is the median elapsed milliseconds for the **whole case** from five samples after one warmup. Lower is faster. All 17 cases passed and `integrity_check=ok` in every run. The current cross-framework comparison, including NativePHP, is in [RESULTS-PIXEL7.md](RESULTS-PIXEL7.md).

## Version changes

| App          | Component                         | Before                                                               | After                                                                  |
| ------------ | --------------------------------- | -------------------------------------------------------------------- | ---------------------------------------------------------------------- |
| NativeScript | `@nativescript/core`              | 8.9.9                                                                | 9.1.2                                                                  |
| NativeScript | `@nativescript/android` runtime   | 8.9.2 (V8 bindings of the fork did not link with 9.1)                | 9.1.1                                                                  |
| NativeScript | `@nativescript/types`             | 9.0.x                                                                | 9.1.2                                                                  |
| NativeScript | `@nativescript/webpack`           | 5.0.x                                                                | 5.0.38                                                                 |
| NativeScript | SQLite plugin                     | `@edusperoni/nativescript-sqlite` 0.0.3 from the [PR 7 fork](https://github.com/edusperoni/nativescript-plugins/pull/7) at `4c2e152` | `@edusperoni/nativescript-sqlite` 0.0.7 from npm                       |
| NativeScript | Plugin JS binding                 | Raw V8 API                                                           | Node-API (0.0.7 default; needs `@nativescript/android` 9.1)            |
| NativeScript | Local plugin changes              | [Android read cache patch](scripts/native-sqlite-read-cache.patch)   | Two Android build fixes in `nativescript/patches/`; no read cache      |
| NativeScript | Bundled SQLite                    | 3.53.1                                                               | 3.53.1                                                                 |
| React Native | `react-native-nitro-sqlite`       | 9.7.0                                                                | 10.0.0                                                                 |
| React Native | `react-native-nitro-modules`      | 0.37.1                                                               | 0.37.1                                                                 |
| React Native | `react-native`                    | 0.87.1                                                               | 0.87.1                                                                 |
| React Native | SQLite compile flags              | Library defaults                                                     | 10.0.0 adds default flags: `SQLITE_THREADSAFE=1` and a performance set (`SQLITE_DQS=0`, `SQLITE_DEFAULT_MEMSTATUS=0`, `SQLITE_OMIT_SHARED_CACHE=1`, and others) |
| React Native | Bundled SQLite                    | 3.49.0                                                               | 3.49.0                                                                 |

The NativeScript build fixes make CMake path arguments use forward slashes, so Windows paths are not read as escape sequences, and link the `libNativeScript.so` stub by name so the Android Gradle plugin does not package it over the runtime library. Neither changes query execution. The app code and the shared benchmark runner did not change for either framework.

## Results

The previous NativeScript run is the PR 7 build with the read cache patch (2026-09-17); the previous React Native run used 9.7.0 (2026-09-16). Each "before" value comes from one complete run. The upgraded builds were each run seven times in a row on 2026-09-30, with the phone at 30.8–31.1 °C battery temperature and thermal status 0 before every run. The upgraded column is the median of the seven run medians for each case, and the range column shows the fastest and slowest of those seven. Delta compares the upgraded median with the previous run, calculated from the unrounded medians: `(after / before - 1) × 100`. Positive means slower; negative means faster. Times are milliseconds. The sum row adds the case medians; its range is the fastest and slowest single-run sum.

### NativeScript

| Case                                    | PR 7 + read cache | 0.0.7 median |   Range of 7 runs |      Δ |
| --------------------------------------- | ----------------: | -----------: | ----------------: | -----: |
| Schema create/drop (30 cycles)          |            13.278 |       16.078 |    8.008 – 18.282 | +21.1% |
| Autocommit insert (400)                 |            40.396 |       46.370 |   36.135 – 50.117 | +14.8% |
| Transaction insert (400)                |             9.555 |        1.996 |     1.919 – 2.656 | -79.1% |
| Point select (400)                      |            17.772 |        8.149 |     6.014 – 9.708 | -54.1% |
| Indexed filter (100)                    |             6.584 |        3.655 |     3.223 – 5.836 | -44.5% |
| Range scan (100)                        |             7.238 |        4.198 |     2.648 – 6.117 | -42.0% |
| Full scan aggregate (40)                |             9.203 |        6.544 |     4.770 – 8.426 | -28.9% |
| Order with limit (100)                  |             7.595 |        5.195 |     3.321 – 6.917 | -31.6% |
| Join aggregate (100)                    |             5.552 |        4.318 |     2.839 – 5.203 | -22.2% |
| LIKE search (50)                        |            15.955 |       15.126 |   14.225 – 21.105 |  -5.2% |
| JSON extract (100)                      |           160.186 |      150.288 | 129.316 – 170.156 |  -6.2% |
| Update by primary key (400)             |            51.963 |       47.993 |   43.055 – 55.890 |  -7.6% |
| Delete by primary key (200)             |            22.051 |       23.396 |   19.103 – 26.469 |  +6.1% |
| Upsert (400)                            |            51.696 |       49.409 |   32.905 – 53.035 |  -4.4% |
| Transaction rollback (50)               |             4.315 |        1.762 |     1.220 – 2.113 | -59.2% |
| 4 KiB BLOB insert and length read (100) |            18.609 |       19.027 |   17.588 – 22.018 |  +2.2% |
| Index build (2,000 rows)                |             0.466 |        0.461 |     0.435 – 0.474 |  -1.1% |
| **Sum of case medians**                 |           442.414 |      403.965 | 358.602 – 433.653 |  -8.7% |

### React Native

| Case                                    |     9.7.0 | 10.0.0 median |       Range of 7 runs |       Δ |
| --------------------------------------- | --------: | ------------: | --------------------: | ------: |
| Schema create/drop (30 cycles)          |    37.792 |        30.756 |       22.630 – 54.029 |  -18.6% |
| Autocommit insert (400)                 |   147.898 |        97.157 |      90.103 – 106.089 |  -34.3% |
| Transaction insert (400)                |   160.029 |       148.693 |     108.033 – 212.460 |   -7.1% |
| Point select (400)                      |    98.108 |       248.883 |     220.394 – 292.723 | +153.7% |
| Indexed filter (100)                    |    44.797 |        82.506 |      58.313 – 112.785 |  +84.2% |
| Range scan (100)                        |    50.232 |        87.099 |      31.851 – 102.846 |  +73.4% |
| Full scan aggregate (40)                |    36.260 |        51.308 |       42.766 – 66.383 |  +41.5% |
| Order with limit (100)                  |    59.588 |        91.349 |      51.050 – 121.419 |  +53.3% |
| Join aggregate (100)                    |    28.223 |        74.740 |       36.353 – 87.739 | +164.8% |
| LIKE search (50)                        |    62.169 |       140.529 |      60.814 – 165.250 | +126.0% |
| JSON extract (100)                      |   294.888 |       653.972 |     449.763 – 913.488 | +121.8% |
| Update by primary key (400)             |    96.917 |       122.732 |      89.868 – 150.798 |  +26.6% |
| Delete by primary key (200)             |    46.578 |        46.776 |       44.344 – 60.553 |   +0.4% |
| Upsert (400)                            |   100.056 |        94.989 |      90.742 – 102.825 |   -5.1% |
| Transaction rollback (50)               |    22.884 |        26.723 |       14.830 – 30.354 |  +16.8% |
| 4 KiB BLOB insert and length read (100) |    40.336 |        57.835 |       41.630 – 78.404 |  +43.4% |
| Index build (2,000 rows)                |     0.796 |         0.875 |         0.557 – 3.659 |   +9.9% |
| **Sum of case medians**                 | 1,327.552 |     2,056.922 | 1,849.010 – 2,430.707 |  +54.9% |

## Findings

- **NativeScript:** 0.0.7 on NativeScript 9.1 is 8.7% faster than the patched PR 7 build by sum of case medians (404.0 ms against 442.4 ms), and each of the seven runs was faster (358.6–433.7 ms). It does not include the read cache patch, yet JSON extract is 6.2% faster (150 ms against 160 ms). Cases dominated by per-call overhead improved most: transaction insert (−79%), transaction rollback (−59%), point select (−54%), indexed filter (−45%), and range scan (−42%). Schema create/drop (+21%) and autocommit insert (+15%) are slower, but both previous values fall inside the seven-run range. Against the unpatched PR 7 build ([report](results/pixel7/nativescript-release-before-read-cache.json)), JSON extract fell from 425 ms to 150 ms.
- **React Native:** 10.0.0 is 54.9% slower than 9.7.0 by sum of case medians (2,056.9 ms against 1,327.6 ms), and each of the seven runs was slower (1,849.0–2,430.7 ms). Point select was 220–293 ms in every run against 98 ms, about 2.5× slower. Join aggregate (+165%), LIKE search (+126%), JSON extract (+122%), and indexed filter (+84%) are also slower. Autocommit insert is 34% faster and upsert 5% faster. React Native varies more between runs than NativeScript: JSON extract ranged from 450 ms to 913 ms. The SQLite version is unchanged, and the new default compile flags are expected to help rather than hurt, so the cause has not been identified. The 9.7.0 value is one run from a different day; running 9.7.0 and 10.0.0 back to back would rule out device drift.
- **Run-to-run variance:** cases that take only a few milliseconds per sample vary by 40–80% between runs, even for NativeScript, because their five samples already differ widely within a run. Treat differences smaller than the range for that case as noise.

## Raw reports

- NativeScript before: [PR 7 with read cache](results/pixel7/nativescript-release-read-cache-1.json), [its repeat](results/pixel7/nativescript-release-read-cache-2.json), [PR 7 before the read cache](results/pixel7/nativescript-release-before-read-cache.json)
- NativeScript after: [seven-run median](results/pixel7/nativescript-release-0.0.7-median7.json), built from `results/pixel7/nativescript-release-0.0.7.json` and `nativescript-release-0.0.7-2.json` to `-7.json`
- React Native before: [9.7.0](results/pixel7/react-native-release.json)
- React Native after: [seven-run median](results/pixel7/react-native-release-nitro10-median7.json), built from `results/pixel7/react-native-release-nitro10.json` and `react-native-release-nitro10-2.json` to `-7.json`

Rebuild the seven-run reports with `scripts/aggregate-runs.mjs`; the command is in [RESULTS-PIXEL7.md](RESULTS-PIXEL7.md).
