import { readdir } from 'node:fs/promises';
import { join } from 'node:path';
import { spawnSync } from 'node:child_process';
import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime, createNodeFsMountHandler } from '@php-wasm/node';
import { projectRoot } from './runtime.mjs';

async function walk(dir) {
  const files = [];
  for (const entry of await readdir(dir, { withFileTypes: true })) {
    const file = join(dir, entry.name);
    if (entry.isDirectory()) files.push(...await walk(file));
    else if (entry.isFile()) files.push(file);
  }
  return files;
}
let javascriptFiles = 0;
for (const directory of ['scripts', 'tests', 'site/theme', 'site/plugin']) {
  for (const file of await walk(join(projectRoot, directory))) {
    if (!/\.m?js$/.test(file)) continue;
    const result = spawnSync(process.execPath, ['--check', file], { encoding: 'utf8' });
    if (result.status !== 0) throw new Error(result.stderr || 'JavaScript syntax check failed');
    javascriptFiles++;
  }
}
const php = new PHP(await loadNodeRuntime('8.3', { emscriptenOptions: { processId: process.pid } }));
try {
  php.mkdir('/source');
  await php.mount('/source', createNodeFsMountHandler(join(projectRoot, 'site')));
  const result = await php.run({ code: `<?php
    $count=0;$failures=[];
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator('/source')) as $file) {
      if(!$file->isFile() || $file->getExtension()!=='php')continue;
      try {token_get_all(file_get_contents($file->getPathname()),TOKEN_PARSE);$count++;}
      catch(ParseError $error){$failures[]=$file->getPathname().': '.$error->getMessage();}
    }
    echo json_encode(['phpFiles'=>$count,'failures'=>$failures]);
  ` });
  if (result.exitCode !== 0 || result.errors) throw new Error(result.errors + result.text);
  const parsed = JSON.parse(result.text);
  if (parsed.failures.length) throw new Error(parsed.failures.join('\n'));
  console.log(JSON.stringify({ status: 'PASS', javascriptFiles, ...parsed }, null, 2));
} finally { php.exit(); }
