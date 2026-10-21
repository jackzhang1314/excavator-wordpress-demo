import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime } from '@php-wasm/node';

test('Fluent Forms calendar filters match SQLite dates without changing other routes or invalid inputs', async () => {
  const php = new PHP(await loadNodeRuntime('8.3', { emscriptenOptions: { processId: process.pid } }));
  try {
    php.writeFile('/plugin.php', await readFile(new URL('../site/mu-plugins/local-demo.php', import.meta.url)));
    for (const [route, dates, expected] of [
      ['/wp-json/fluentform/v1/submissions/all', ['2026/9/1', '2026/10/21'], ['2026-09-01', '2026-10-21']],
      ['/wp-json/fluentform/v1/report/submissions', ['2026/02/30', 'all'], ['2026/02/30', 'all']],
      ['/wp-json/wp/v2/posts', ['2026/9/1', '2026/10/21'], ['2026/9/1', '2026/10/21']],
    ]) {
      php.writeFile('/input.json', JSON.stringify({ route, dates }));
      const result = await php.run({ code: `<?php
        define('ABSPATH','/wordpress/');
        $actions=[];
        function add_filter(...$args) {}
        function add_action($name,$callback,...$args) {global $actions;$actions[$name][]=$callback;}
        $input=json_decode(file_get_contents('/input.json'),true);
        $_SERVER['REQUEST_URI']=$input['route'];
        $_GET=['date_range'=>$input['dates']];$_REQUEST=$_GET;
        require '/plugin.php';
        foreach($actions['muplugins_loaded'] as $callback)$callback();
        echo json_encode($_GET['date_range']);
      ` });
      assert.equal(result.exitCode, 0);
      assert.equal(result.errors, '');
      assert.deepEqual(JSON.parse(result.text), expected);
    }
  } finally { php.exit(); }
});
