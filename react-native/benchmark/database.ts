import { open } from 'react-native-nitro-sqlite';

export function openBenchmarkDatabase() {
  const db = open({ name: 'sqlite-benchmark.sqlite' });
  const rows = async (sql: string, params: any[] = []): Promise<any[]> => {
    const result: any = await db.executeAsync(sql, params);
    return result.results ?? result.rows?._array ?? [];
  };
  return {
    exec: async (sql: string, params: any[] = []) => { await db.executeAsync(sql, params); },
    rows,
    transaction: (fn: (tx: { exec: (sql: string, params?: any[]) => Promise<void> }) => Promise<void>) =>
      db.transaction(async tx => fn({ exec: async (sql, params = []) => { await tx.executeAsync(sql, params); } })),
    close: () => db.close(),
  };
}
