# Pixel 7: NativeScript vs React Native

This page compares the two upgraded JavaScript apps head to head: NativeScript 9.1 with `@edusperoni/nativescript-sqlite` 0.0.7, and React Native 0.87.1 with `react-native-nitro-sqlite` 10.0.0. Both ran the same benchmark runner file, as signed release APKs, on the same connected Pixel 7 (`arm64-v8a`, Android 17/API 37) on 2026-09-30. Each app was run seven times in a row, one app at a time, with the phone at 30.8–31.1 °C battery temperature and thermal status 0 before every run. All 17 cases passed and `integrity_check=ok` in all 14 runs.

Each value is the median elapsed milliseconds for the **whole case**: the median of five samples after one warmup, then the median of those across the seven runs. The range columns show the fastest and slowest of the seven run medians. **RN ÷ NS** is how many times longer React Native took. **µs/op** divides the median by the number of operations in the case. **Ranges overlap** is "no" when React Native's fastest run was slower than NativeScript's slowest. Lower is faster.

| Case                                    | NativeScript |          NS range | React Native |              RN range | RN ÷ NS | NS µs/op | RN µs/op | Ranges overlap |
| --------------------------------------- | -----------: | ----------------: | -----------: | --------------------: | ------: | -------: | -------: | -------------- |
| Schema create/drop (30 cycles)          |       16.078 |    8.008 – 18.282 |       30.756 |       22.630 – 54.029 |    1.9× |      536 |    1,025 | no             |
| Autocommit insert (400)                 |       46.370 |   36.135 – 50.117 |       97.157 |      90.103 – 106.089 |    2.1× |      116 |      243 | no             |
| Transaction insert (400)                |        1.996 |     1.919 – 2.656 |      148.693 |     108.033 – 212.460 |   74.5× |        5 |      372 | no             |
| Point select (400)                      |        8.149 |     6.014 – 9.708 |      248.883 |     220.394 – 292.723 |   30.5× |       20 |      622 | no             |
| Indexed filter (100)                    |        3.655 |     3.223 – 5.836 |       82.506 |      58.313 – 112.785 |   22.6× |       37 |      825 | no             |
| Range scan (100)                        |        4.198 |     2.648 – 6.117 |       87.099 |      31.851 – 102.846 |   20.7× |       42 |      871 | no             |
| Full scan aggregate (40)                |        6.544 |     4.770 – 8.426 |       51.308 |       42.766 – 66.383 |    7.8× |      164 |    1,283 | no             |
| Order with limit (100)                  |        5.195 |     3.321 – 6.917 |       91.349 |      51.050 – 121.419 |   17.6× |       52 |      913 | no             |
| Join aggregate (100)                    |        4.318 |     2.839 – 5.203 |       74.740 |       36.353 – 87.739 |   17.3× |       43 |      747 | no             |
| LIKE search (50)                        |       15.126 |   14.225 – 21.105 |      140.529 |      60.814 – 165.250 |    9.3× |      303 |    2,811 | no             |
| JSON extract (100)                      |      150.288 | 129.316 – 170.156 |      653.972 |     449.763 – 913.488 |    4.4× |    1,503 |    6,540 | no             |
| Update by primary key (400)             |       47.993 |   43.055 – 55.890 |      122.732 |      89.868 – 150.798 |    2.6× |      120 |      307 | no             |
| Delete by primary key (200)             |       23.396 |   19.103 – 26.469 |       46.776 |       44.344 – 60.553 |    2.0× |      117 |      234 | no             |
| Upsert (400)                            |       49.409 |   32.905 – 53.035 |       94.989 |      90.742 – 102.825 |    1.9× |      124 |      237 | no             |
| Transaction rollback (50)               |        1.762 |     1.220 – 2.113 |       26.723 |       14.830 – 30.354 |   15.2× |       35 |      534 | no             |
| 4 KiB BLOB insert and length read (100) |       19.027 |   17.588 – 22.018 |       57.835 |       41.630 – 78.404 |    3.0× |      190 |      578 | no             |
| Index build (2,000 rows)                |        0.461 |     0.435 – 0.474 |        0.875 |         0.557 – 3.659 |    1.9× |      461 |      875 | no             |
| **Sum of case medians**                 |      403.965 | 358.602 – 433.653 |    2,056.922 | 1,849.010 – 2,430.707 |    5.1× |          |          | no             |

## Findings

- **React Native was slower in every case and every run.** No case has overlapping ranges, and React Native's fastest complete run (1,849 ms) took more than four times as long as NativeScript's slowest (434 ms). By sum of case medians, React Native took 5.1× as long.
- **Durable writes differ least, about 2×.** Autocommit insert, update, delete, and upsert each commit to disk with `synchronous=FULL`, so storage time dominates: about 115–125 µs per operation in NativeScript against 235–305 µs in React Native. Schema create/drop and the index build are also about 2×.
- **Short reads differ most, 17–31×.** Point select costs 20 µs per call in NativeScript and 622 µs in React Native. Indexed filter, range scan, order with limit, and join aggregate are 17–23×. Each of these queries does little SQLite work, so the time per awaited call dominates, and that is where the two drivers differ.
- **Work inside a transaction shows the largest gap.** Transaction insert costs 5 µs per insert in NativeScript and 372 µs in React Native (74.5×), and transaction rollback is 15.2×. NativeScript's per-call cost inside an open transaction is close to SQLite's own cost; React Native still pays roughly its full per-call cost.
- **Heavier queries narrow the gap.** JSON extract (4.4×), BLOB insert and read (3.0×), LIKE search (9.3×), and full scan aggregate (7.8×) spend more time inside SQLite per call, so the per-call difference matters less.
- **React Native varies more between runs.** For example, JSON extract ranged from 450 ms to 913 ms in React Native and from 129 ms to 170 ms in NativeScript.

## What the comparison includes

Both apps await one native call per SQL operation and check query results in JavaScript, so each timing includes the driver's call and scheduling cost, not just SQLite. The two apps bundle different SQLite versions (NativeScript 3.53.1, React Native 3.49.0), and both report WAL mode, `synchronous=2` (`FULL`), and 2,000 fixture rows. React Native 10.0.0 is also slower than 9.7.0 on most read cases; see [RESULTS-PIXEL7-UPGRADE.md](RESULTS-PIXEL7-UPGRADE.md). The comparison with NativePHP is in [RESULTS-PIXEL7.md](RESULTS-PIXEL7.md).

## Raw reports

- [NativeScript seven-run median](results/pixel7/nativescript-release-0.0.7-median7.json), built from `results/pixel7/nativescript-release-0.0.7.json` and `nativescript-release-0.0.7-2.json` to `-7.json`
- [React Native seven-run median](results/pixel7/react-native-release-nitro10-median7.json), built from `results/pixel7/react-native-release-nitro10.json` and `react-native-release-nitro10-2.json` to `-7.json`
