import test from 'node:test';
import assert from 'node:assert/strict';
import { resolve, join } from 'node:path';
import { mkdtemp, readFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { zipSync } from 'fflate';
import { parsePort, archiveTarget, unpackArchive, isPrivatePath } from '../scripts/runtime.mjs';

test('valid ports and rejected ambiguous or reserved values', () => {
  assert.equal(parsePort(), 9400);
  assert.equal(parsePort('9401'), 9401);
  for (const value of ['', '9400abc', 'NaN', -1, 80, 65536, '1.2']) assert.throws(() => parsePort(value));
});

test('archive writes cannot escape the local runtime directory', () => {
  const root = resolve('fixture');
  assert.equal(archiveTarget(root, 'plugin/file.php'), join(root, 'plugin/file.php'));
  for (const name of ['../secret', '/etc/passwd', 'C:/secret', '..\\secret', 'plugin/../../secret']) assert.throws(() => archiveTarget(root, name));
});

test('bundled plugin extraction preserves bytes and nested directories', async () => {
  const dir = await mkdtemp(join(tmpdir(), 'excavator-archive-test-'));
  try {
    const bytes = Uint8Array.from([0, 1, 2, 254, 255]);
    await unpackArchive(zipSync({ 'plugin/nested/file.bin': bytes }), dir);
    assert.deepEqual(new Uint8Array(await readFile(join(dir, 'plugin/nested/file.bin'))), bytes);
  } finally { await rm(dir, { recursive: true, force: true }); }
});

test('private SQLite, configuration, source and encoded hidden paths never reach the file server', () => {
  for (const path of ['/wp-content/database/.ht.sqlite', '/wp-content/database/anything', '/wp-content/%64atabase/.ht.sqlite', '/wp-content/database/%252eht.sqlite', '/.git/config', '/demo-source/content.json', '/demo-private/inquiries/a.pdf', '/wp-config.php', '/wp-content/debug.log']) assert.equal(isPrivatePath(path), true, path);
  for (const path of ['/', '/wp-admin/', '/products/model-general-08/', '/wp-content/uploads/2026/10/image.png', '/wp-login.php', '/wp-json/']) assert.equal(isPrivatePath(path), false, path);
});
