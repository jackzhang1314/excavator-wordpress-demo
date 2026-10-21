import { readFile, readdir, stat } from 'node:fs/promises';
import { join, relative } from 'node:path';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';
import { unzipSync } from 'fflate';
import { checkPackages, projectRoot, archiveTarget } from './runtime.mjs';

await checkPackages();
const seed = JSON.parse(await readFile(join(projectRoot, 'site/content.json'), 'utf8'));
assert.deepEqual(Object.keys(seed.tables).sort(), ['posts', 'postmeta', 'terms', 'termmeta', 'term_taxonomy', 'term_relationships', 'options', 'fluentform_forms', 'fluentform_form_meta'].sort());
for (const row of seed.tables.options) assert(!/smtp|fluentmail|license|token|secret|hostinger|auth_key|auth_salt|recovery|password/i.test(row.option_name), 'Private option in seed: ' + row.option_name);
const seedText = JSON.stringify(seed);
assert(!/hostingersite\.com|-----BEGIN [A-Z ]*PRIVATE KEY-----/.test(seedText));
assert(!seedText.includes(String.fromCharCode(47) + 'Users' + String.fromCharCode(47)));
for (const email of seedText.match(/[\w.%+-]+@[\w.-]+\.[a-z]{2,}/gi) ?? []) assert(email.endsWith('@example.invalid'), 'Non-demo email in content fixture');

const counts = { starter_product: 20, starter_category: 15, starter_industry: 11, post: 9 };
for (const [type, count] of Object.entries(counts)) assert.equal(seed.tables.posts.filter(p => p.post_type === type && p.post_status === 'publish').length, count, type);
const descriptions = [];
for (const [type, key] of [['starter_product', 'product_details_body'], ['starter_category', 'category_long_description']]) {
  for (const post of seed.tables.posts.filter(p => p.post_type === type && p.post_status === 'publish')) {
    const meta = seed.tables.postmeta.find(m => Number(m.post_id) === Number(post.ID) && m.meta_key === key);
    assert(meta && meta.meta_value.length > 1000, 'Missing full description: ' + post.post_name);
    descriptions.push(post.ID);
  }
}
const media = JSON.parse(await readFile(join(projectRoot, 'site/media-manifest.json'), 'utf8'));
for (const item of media) {
  const bytes = await readFile(archiveTarget(join(projectRoot, 'site/uploads'), item.path));
  assert.equal(bytes.length, item.bytes, item.path);
  assert.equal(createHash('sha256').update(bytes).digest('hex'), item.sha256, item.path);
}
let archiveFiles = 0;
for (const name of (await readdir(join(projectRoot, 'packages'))).filter(n => n.endsWith('.zip'))) {
  for (const [path, bytes] of Object.entries(unzipSync(await readFile(join(projectRoot, 'packages', name))))) {
    archiveTarget('/audit-root', path);
    assert(!/(^|\/)(?:wp-config\.php|auth\.json|\.env|SKILL\.md)$/.test(path), 'Unexpected private/tooling archive entry: ' + path);
    assert(!/\.wordpress-builder\/|wordpress-builder-skill\//.test(path));
    assert(!/-----BEGIN (?:OPENSSH|RSA|EC) PRIVATE KEY-----\s+[A-Za-z0-9+/]{30}/.test(Buffer.from(bytes).toString('utf8')));
    archiveFiles++;
  }
}
async function walk(dir) {
  const result = [];
  for (const entry of await readdir(dir, { withFileTypes: true })) {
    if (['.git', '.runtime', '.tools', '.backups', 'node_modules'].includes(entry.name) || entry.name.startsWith('.test-runtime')) continue;
    const path = join(dir, entry.name);
    if (entry.isSymbolicLink()) throw new Error('Unexpected symlink: ' + relative(projectRoot, path));
    if (entry.isDirectory()) result.push(...await walk(path));
    else if (entry.isFile()) result.push(path);
  }
  return result;
}
const files = await walk(projectRoot);
for (const file of files) assert((await stat(file)).size < 100 * 1024 * 1024, 'File exceeds normal GitHub size limit');
console.log(JSON.stringify({ status: 'PASS', publishedCounts: counts, fullDescriptions: descriptions.length, mediaFiles: media.length, archiveFiles, distributableFiles: files.length }, null, 2));
