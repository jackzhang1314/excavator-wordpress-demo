import { createServer } from 'node:http';
import { cp, mkdir, open, readFile, rm, stat, writeFile } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import { dirname, join, resolve, sep } from 'node:path';
import { fileURLToPath } from 'node:url';
import { rootCertificates } from 'node:tls';
import { unzipSync } from 'fflate';
import { loadNodeRuntime, createNodeFsMountHandler } from '@php-wasm/node';
import { bootRequestHandler, bootWordPress } from '@wp-playground/wordpress';

export const projectRoot = resolve(dirname(fileURLToPath(import.meta.url)), '..');
export const defaultPort = 9400;

export function parsePort(value = defaultPort) {
  if (!/^\d+$/.test(String(value)) || Number(value) < 1024 || Number(value) > 65535) throw new Error('Port must be an integer between 1024 and 65535.');
  return Number(value);
}

export function archiveTarget(root, name) {
  if (name.includes('\\') || name.startsWith('/') || /^[a-z]:/i.test(name)) throw new Error('Unsafe archive path');
  const target = resolve(root, name);
  if (!target.startsWith(resolve(root) + sep)) throw new Error('Unsafe archive path');
  return target;
}

export function isPrivatePath(input) {
  let path = new URL(input, 'http://127.0.0.1').pathname;
  for (let i = 0; i < 4; i++) {
    const decoded = decodeURIComponent(path);
    if (decoded === path) break;
    path = decoded;
  }
  path = path.replaceAll('\\', '/');
  return path.split('/').some(part => part.startsWith('.')) || /^\/(?:demo-private|demo-source)(?:\/|$)/i.test(path) || /^\/wp-content\/(?:database(?:\/|$)|debug\.log(?:$|\/))/i.test(path) || /^\/wp-config(?:-sample)?\.php(?:$|\/)/i.test(path);
}

export async function unpackArchive(bytes, destination) {
  for (const [name, contents] of Object.entries(unzipSync(bytes))) {
    const target = archiveTarget(destination, name);
    if (name.endsWith('/')) await mkdir(target, { recursive: true });
    else { await mkdir(dirname(target), { recursive: true }); await writeFile(target, contents); }
  }
}

export async function checkPackages() {
  const manifest = JSON.parse(await readFile(join(projectRoot, 'packages/manifest.json'), 'utf8'));
  for (const item of manifest.files) {
    const bytes = await readFile(join(projectRoot, 'packages', item.file));
    if (createHash('sha256').update(bytes).digest('hex') !== item.sha256) throw new Error('Package checksum mismatch: ' + item.file);
  }
}

async function acquireLock(runtimeRoot) {
  const path = join(runtimeRoot, 'server.lock');
  try {
    const existing = JSON.parse(await readFile(path, 'utf8'));
    if (!Number.isInteger(existing.pid) || existing.pid < 1) throw new Error('Invalid local runtime lock.');
    let alive = true;
    try { process.kill(existing.pid, 0); } catch (error) { if (error.code === 'ESRCH') alive = false; else throw error; }
    if (alive) throw new Error(`This local copy is already running (PID ${existing.pid}). Stop it before starting another process.`);
    await rm(path);
  } catch (error) { if (error.code !== 'ENOENT') throw error; }
  const handle = await open(path, 'wx', 0o600);
  await handle.writeFile(JSON.stringify({ pid: process.pid }));
  await handle.close();
  return async () => { await rm(path, { force: true }); };
}

export async function startDemo({ port = defaultPort, runtimeRoot = join(projectRoot, '.runtime') } = {}) {
  port = parsePort(port);
  runtimeRoot = resolve(runtimeRoot);
  if (runtimeRoot === projectRoot || runtimeRoot === resolve(projectRoot, 'site')) throw new Error('Runtime storage must be separate from source files.');
  await mkdir(runtimeRoot, { recursive: true });
  const releaseLock = await acquireLock(runtimeRoot);
  const origin = `http://127.0.0.1:${port}`;
  const wordpressRoot = join(runtimeRoot, 'wordpress');
  const privateRoot = join(runtimeRoot, 'private');
  let handler;
  let server;
  try {
    await checkPackages();
    await mkdir(wordpressRoot, { recursive: true });
    await mkdir(privateRoot, { recursive: true, mode: 0o700 });
    const installed = await stat(join(wordpressRoot, 'wp-load.php')).then(() => true, error => { if (error.code === 'ENOENT') return false; throw error; });
    if (!installed) {
      for (const file of ['advanced-custom-fields-pro.zip', 'classic-editor.zip', 'fluentform.zip', 'seo-by-rank-math.zip']) {
        await unpackArchive(await readFile(join(projectRoot, 'packages', file)), join(wordpressRoot, 'wp-content/plugins'));
      }
      await cp(join(projectRoot, 'site/uploads'), join(wordpressRoot, 'wp-content/uploads'), { recursive: true });
    }
    await mkdir(join(wordpressRoot, 'wp-content/mu-plugins'), { recursive: true });
    await cp(join(projectRoot, 'site/mu-plugins/local-demo.php'), join(wordpressRoot, 'wp-content/mu-plugins/local-demo.php'));
    const mounts = [
      [wordpressRoot, '/wordpress'],
      [join(projectRoot, 'site'), '/demo-source'],
      [join(projectRoot, 'site/theme'), '/wordpress/wp-content/themes/b2b-starter-theme'],
      [join(projectRoot, 'site/plugin'), '/wordpress/wp-content/plugins/starter-model'],
      [privateRoot, '/demo-private'],
    ];
    handler = await bootRequestHandler({
      siteUrl: origin, documentRoot: '/wordpress', phpVersion: '8.3', maxPhpInstances: 1, cookieStore: false,
      createPhpRuntime: () => loadNodeRuntime('8.3', { extensions: ['intl'], emscriptenOptions: { processId: process.pid } }),
      phpIniEntries: { max_input_vars: '10000', memory_limit: '512M', upload_max_filesize: '20M', post_max_size: '24M', 'openssl.cafile': '/internal/shared/ca.pem', 'curl.cainfo': '/internal/shared/ca.pem' },
      createFiles: { '/internal/shared/ca.pem': rootCertificates.join('\n') },
      constants: { AUTOMATIC_UPDATER_DISABLED: true, DISABLE_WP_CRON: true, WP_ENVIRONMENT_TYPE: 'local', WORDPRESS_BUILDER_INQUIRY_STORAGE: '/demo-private/inquiries' },
      onPHPInstanceCreated: async php => {
        for (const [local, virtual] of mounts) {
          php.mkdirTree(virtual);
          await php.mount(virtual, createNodeFsMountHandler(local));
        }
      },
    });
    await bootWordPress(handler, {
      siteUrl: origin, phpVersion: '8.3',
      wordpressInstallMode: installed ? 'do-not-attempt-installing' : 'download-and-install',
      wordPressZip: installed ? undefined : new File([await readFile(join(projectRoot, 'packages/wordpress-6.9.10.zip'))], 'wordpress.zip'),
      sqliteIntegrationPluginZip: new File([await readFile(join(projectRoot, 'packages/sqlite-database-integration.zip'))], 'sqlite.zip'),
    });
    const php = await handler.getPrimaryPhp();
    const initialized = await php.run({ scriptPath: '/demo-source/bootstrap.php' });
    if (initialized.exitCode !== 0 || initialized.errors) throw new Error('Local content initialization failed: ' + initialized.errors + initialized.text);
    server = createServer(async (request, response) => {
      try {
        // Only this loopback origin can use the public demo credentials.
        if (request.headers.host !== `127.0.0.1:${port}` && request.headers.host !== `localhost:${port}`) {
          response.writeHead(403); response.end('Open the local URL printed in the terminal.'); return;
        }
        if (request.headers.origin && ![origin, `http://localhost:${port}`].includes(request.headers.origin)) {
          response.writeHead(403); response.end('Use this demo from its local origin.'); return;
        }
        if (isPrivatePath(request.url ?? '/')) {
          response.writeHead(404); response.end('Not found'); return;
        }
        const chunks = []; let length = 0;
        for await (const chunk of request) {
          length += chunk.length;
          if (length > 24 * 1024 * 1024) { response.writeHead(413); response.end('Request too large'); return; }
          chunks.push(chunk);
        }
        const headers = Object.fromEntries(Object.entries(request.headers).map(([key, value]) => [key, Array.isArray(value) ? value.join(', ') : value ?? '']));
        const result = await handler.request({ url: request.url ?? '/', method: request.method, headers, body: chunks.length ? Buffer.concat(chunks) : undefined });
        for (const [key, value] of Object.entries(result.headers)) response.setHeader(key, value);
        response.writeHead(result.httpStatusCode);
        response.end(result.bytes);
        if (result.errors) console.error(result.errors);
      } catch (error) {
        console.error(error);
        if (!response.headersSent) response.writeHead(500);
        response.end('Local WordPress request failed. See the terminal for details.');
      }
    });
    await new Promise((yes, no) => { server.once('error', no); server.listen(port, '127.0.0.1', yes); });
    await writeFile(join(runtimeRoot, 'running.json'), JSON.stringify({ origin, pid: process.pid }, null, 2) + '\n');
    let disposed = false;
    return { origin, handler, server, runtimeRoot, async dispose() {
      if (disposed) return;
      disposed = true;
      await new Promise(done => { server.close(done); server.closeAllConnections(); });
      await handler[Symbol.asyncDispose]();
      await rm(join(runtimeRoot, 'running.json'), { force: true });
      await releaseLock();
    } };
  } catch (error) {
    server?.close();
    if (handler) await handler[Symbol.asyncDispose]();
    await releaseLock();
    throw error;
  }
}
