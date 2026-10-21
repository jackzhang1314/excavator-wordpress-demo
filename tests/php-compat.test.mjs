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

test('authorized draft previews recover missing ACF snapshots without overriding revision values or other posts', async () => {
  const php = new PHP(await loadNodeRuntime('8.3', { emscriptenOptions: { processId: process.pid } }));
  try {
    php.writeFile('/plugin.php', await readFile(new URL('../site/mu-plugins/local-demo.php', import.meta.url)));
    const base = { id: 441, preview: true, query: 440, canEdit: true, status: 'draft', type: 'starter_product', revisionMeta: {}, parentMeta: { product_details_body: ['saved draft'], _product_details_body: ['field_body'] } };
    const cases = [
      [base, 440],
      [{ ...base, revisionMeta: { product_details_body: [''], _product_details_body: ['field_body'] } }, 441],
      [{ ...base, canEdit: false }, 441],
      [{ ...base, status: 'publish' }, 441],
      [{ ...base, query: 999 }, 441],
      [{ ...base, preview: false }, 441],
      [{ ...base, parentMeta: {} }, 441],
      [{ ...base, type: 'unrelated_cpt' }, 441],
      [{ ...base, id: 'option' }, 'option'],
    ];
    for (const [input, expected] of cases) {
      php.writeFile('/input.json', JSON.stringify(input));
      const result = await php.run({ code: `<?php
        define('ABSPATH','/wordpress/');
        $input=json_decode(file_get_contents('/input.json'),true);
        $filters=[];
        function add_filter($name,$callback,...$args) {global $filters;$filters[$name][]=$callback;}
        function add_action(...$args) {}
        function is_preview() {global $input;return $input['preview'];}
        function wp_is_post_revision($id) {return $id===441 ? 440 : false;}
        function get_queried_object_id() {global $input;return $input['query'];}
        function current_user_can(...$args) {global $input;return $input['canEdit'];}
        function get_post_status($id) {global $input;return $input['status'];}
        function get_post_type($id) {global $input;return $input['type'];}
        function acf_get_meta($id) {global $input;return (int)$id===441 ? $input['revisionMeta'] : $input['parentMeta'];}
        $_GET=$input['preview'] ? ['preview'=>'true'] : [];
        require '/plugin.php';
        $id=$input['id'];
        foreach($filters['acf/validate_post_id'] as $callback)$id=$callback($id);
        echo json_encode($id);
      ` });
      assert.equal(result.exitCode, 0);
      assert.equal(result.errors, '');
      assert.equal(JSON.parse(result.text), expected);
    }
  } finally { php.exit(); }
});
