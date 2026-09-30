// Combines complete runs of one app into a report whose median_ms per case is
// the median of the runs' medians. The output has the same shape as an app
// report, so scripts/compare.py accepts it.
// Usage: node scripts/aggregate-runs.mjs OUTPUT.json RUN1.json RUN2.json ...
import { readFileSync, writeFileSync } from 'node:fs';

const [output, ...inputs] = process.argv.slice(2);
if (!output || inputs.length < 2) throw new Error('usage: node scripts/aggregate-runs.mjs OUTPUT.json RUN1.json RUN2.json ...');

const runs = inputs.map(path => JSON.parse(readFileSync(path, 'utf8').replace(/^﻿/, '')));
const median = values => {
  const sorted = [...values].sort((a, b) => a - b);
  const mid = sorted.length >> 1;
  return sorted.length % 2 ? sorted[mid] : (sorted[mid - 1] + sorted[mid]) / 2;
};

const names = runs[0].results.map(row => row.name);
for (const run of runs) {
  if (run.metadata.integrity_check !== 'ok') throw new Error('integrity_check failed in a run');
  if (run.results.map(row => row.name).join() !== names.join()) throw new Error('runs have different cases');
  if (run.results.some(row => row.status !== 'ok')) throw new Error('a case failed in a run');
}

const results = names.map((name, index) => {
  const rows = runs.map(run => run.results[index]);
  const runMedians = rows.map(row => row.median_ms);
  const medianMs = median(runMedians);
  return {
    name,
    ops: rows[0].ops,
    run_medians_ms: runMedians,
    median_ms: medianMs,
    min_run_median_ms: Math.min(...runMedians),
    max_run_median_ms: Math.max(...runMedians),
    ops_per_second: Math.round(rows[0].ops / (medianMs / 1000)),
    status: 'ok',
  };
});

writeFileSync(output, JSON.stringify({ ...runs[0], runs: inputs.length, run_files: inputs, results }, null, 2));
console.log(output);
