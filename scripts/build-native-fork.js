const fs = require('fs');
const path = require('path');
const ts = require('../react-native/node_modules/typescript');

const dir = path.resolve(__dirname, '../native-sqlite-fork/packages/nativescript-sqlite');
for (const name of ['common.ts', 'index.android.ts', 'index.ios.ts']) {
  const source = fs.readFileSync(path.join(dir, name), 'utf8');
  const output = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2020 } }).outputText;
  fs.writeFileSync(path.join(dir, name.replace(/\.ts$/, '.js')), output);
}
