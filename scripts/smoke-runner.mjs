import { DatabaseSync } from 'node:sqlite';
import { runBenchmark } from '../benchmark/runner.js';

const connection = new DatabaseSync(':memory:');
const adapter = {
  exec: async (sql, params = []) => { connection.prepare(sql).run(...params); },
  rows: async (sql, params = []) => connection.prepare(sql).all(...params),
  transaction: async fn => {
    connection.exec('BEGIN');
    try { await fn(adapter); connection.exec('COMMIT'); }
    catch (error) { connection.exec('ROLLBACK'); throw error; }
  },
};

try {
  const report = await runBenchmark(adapter, () => {}, 1);
  const failures = report.results.filter(result => result.status !== 'ok');
  if (failures.length || report.metadata.integrity_check !== 'ok') throw new Error(JSON.stringify(failures));
  console.log(`${report.results.length} JS cases passed; SQLite ${report.metadata.sqlite_version}`);
} finally {
  connection.close();
}
