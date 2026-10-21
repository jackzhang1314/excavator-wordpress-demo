import { readFile } from 'node:fs/promises';
import { join } from 'node:path';
import { projectRoot } from './runtime.mjs';

const args = process.argv.slice(2);
let origin = 'http://127.0.0.1:9400';
if (args.length) {
  if (args.length !== 2 || args[0] !== '--url') throw new Error('Usage: npm run verify -- --url http://127.0.0.1:9400');
  origin = new URL(args[1]).origin;
}
if (!/^http:\/\/127\.0\.0\.1:\d+$/.test(origin)) throw new Error('Verify only a local loopback demo URL.');
const seed = JSON.parse(await readFile(join(projectRoot, 'site/content.json'), 'utf8'));
const assets = new Set(); const failures = []; let pages = 0;
for (const path of seed.routes) {
  const response = await fetch(origin + path, { signal: AbortSignal.timeout(30000) });
  const html = await response.text();
  if (!response.ok || !/<h1\b/i.test(html) || /Fatal error|Critical error/i.test(html)) failures.push({ path, status: response.status });
  else pages++;
  for (const match of html.matchAll(/\b(?:src|href)=["']([^"']+)["']/gi)) {
    const url = new URL(match[1].replaceAll('&amp;', '&'), origin + path);
    if (url.origin === origin && /\.(?:css|js|png|jpe?g|webp|svg|woff2?|mp4|pdf)$/i.test(url.pathname)) assets.add(url.href);
  }
}
for (const url of assets) {
  const response = await fetch(url, { signal: AbortSignal.timeout(30000) });
  await response.arrayBuffer();
  if (!response.ok) failures.push({ asset: new URL(url).pathname, status: response.status });
}
for (const path of ['/demo-private/inquiries/', '/demo-source/content.json', '/wp-content/database/.ht.sqlite', '/definitely-no-such-page/']) {
  const response = await fetch(origin + path, { redirect: 'manual' });
  await response.arrayBuffer();
  if (![403, 404].includes(response.status)) failures.push({ privatePath: path, status: response.status });
}
console.log(JSON.stringify({ status: failures.length ? 'FAIL' : 'PASS', origin, pages, expectedPages: seed.routes.length, resources: assets.size, failures }, null, 2));
if (failures.length) process.exitCode = 1;
