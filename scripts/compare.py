import csv
import json
import sys
from pathlib import Path


def main(paths):
    reports = [json.loads(Path(path).read_text(encoding="utf-8-sig")) for path in paths]
    labels = {"NativeScript SolidJS": "NativeScript", "NativePHP WebView": "NativePHP per-query"}
    names = [labels.get(report["framework"], report["framework"] + (" SuperNative" if "SuperNative" in report["driver"] else " PHP loop" if report["framework"] == "NativePHP" else "")) for report in reports]
    expected = [row["name"] for row in reports[0]["results"]]
    for report in reports:
        assert report["benchmark_version"] == 1
        assert report["metadata"]["integrity_check"] == "ok"
        assert [row["name"] for row in report["results"]] == expected
        assert all(row["status"] == "ok" for row in report["results"])
    print("SQLite versions:", ", ".join(f"{name} {report['metadata']['sqlite_version']}" for name, report in zip(names, reports)))
    print("Journal / synchronous:", ", ".join(f"{name} {report['metadata']['journal_mode']}/{report['metadata']['synchronous']}" for name, report in zip(names, reports)))
    writer = csv.writer(sys.stdout, lineterminator="\n")
    header = ["case", "unit", f"{names[0]} median ms"]
    for name in names[1:]:
        header.extend([f"{name} median ms", f"{name} delta vs {names[0]}"])
    writer.writerow(header)
    for index, case in enumerate(expected):
        base = reports[0]["results"][index]["median_ms"]
        row = [case, "index builds" if case == "index_create" else "operations", f"{base:.3f}"]
        for report in reports[1:]:
            value = report["results"][index]["median_ms"]
            row.extend([f"{value:.3f}", f"{(value / base - 1) * 100:+.1f}%"])
        writer.writerow(row)


if __name__ == "__main__":
    if len(sys.argv) < 3:
        raise SystemExit("usage: python scripts/compare.py results/nativescript.json results/react-native.json results/nativephp-ui.json")
    main(sys.argv[1:])
