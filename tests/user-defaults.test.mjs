import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime } from '@php-wasm/node';

test('legacy demo toolbar defaults migrate once without overriding later preferences or unrelated users', async () => {
  const php = new PHP(await loadNodeRuntime('8.3', { emscriptenOptions: { processId: process.pid } }));
  try {
    php.writeFile('/defaults.php', await readFile(new URL('../site/user-defaults.php', import.meta.url)));
    const result = await php.run({ code: `<?php
      $options=[];$users=[1=>'demo_admin',2=>'demo_author',3=>'another_user'];
      $meta=[1=>'false',2=>'false',3=>'false'];$writes=[];
      function get_option($key){global $options;return $options[$key]??false;}
      function get_user_by($key,$id){global $users;return isset($users[$id])?(object)['user_login'=>$users[$id]]:false;}
      function update_user_meta($id,$key,$value){global $meta,$writes;if($key!=='show_admin_bar_front')throw new RuntimeException('Unexpected metadata');$meta[$id]=$value;$writes[]=$id;}
      function update_option($key,$value,...$args){global $options;$options[$key]=$value;}
      require '/defaults.php';
      excavator_demo_migrate_toolbar_default();
      $first=[$meta,$writes,$options];
      $meta[1]='false';$writes=[];
      excavator_demo_migrate_toolbar_default();
      $second=[$meta,$writes];
      $options=[];$users[1]='renamed_owner';$writes=[];
      excavator_demo_migrate_toolbar_default();
      echo json_encode([$first,$second,[$meta,$writes]]);
    ` });
    assert.equal(result.exitCode, 0);
    assert.equal(result.errors, '');
    const [first, second, renamed] = JSON.parse(result.text);
    assert.deepEqual(first, [{ 1: 'true', 2: 'true', 3: 'false' }, [1, 2], { excavator_demo_toolbar_schema: '1' }]);
    assert.deepEqual(second, [{ 1: 'false', 2: 'true', 3: 'false' }, []]);
    assert.deepEqual(renamed, [{ 1: 'false', 2: 'true', 3: 'false' }, [2]]);
  } finally { php.exit(); }
});

test('required profile nicknames are filled only for bundled accounts with missing values', async () => {
  const php = new PHP(await loadNodeRuntime('8.3', { emscriptenOptions: { processId: process.pid } }));
  try {
    php.writeFile('/defaults.php', await readFile(new URL('../site/user-defaults.php', import.meta.url)));
    const result = await php.run({ code: `<?php
      $options=[];$users=[1=>'demo_admin',2=>'demo_author',3=>'another_user'];
      $meta=[1=>'',2=>'Custom author',3=>''];$writes=[];
      function get_option($key){global $options;return $options[$key]??false;}
      function get_user_by($key,$id){global $users;return isset($users[$id])?(object)['user_login'=>$users[$id],'display_name'=>$id===1?'admin':'Alex Morgan']:false;}
      function get_user_meta($id,$key,...$args){global $meta;return $meta[$id]??'';}
      function update_user_meta($id,$key,$value){global $meta,$writes;if($key!=='nickname')throw new RuntimeException('Unexpected metadata');$meta[$id]=$value;$writes[]=$id;}
      function update_option($key,$value,...$args){global $options;$options[$key]=$value;}
      require '/defaults.php';
      excavator_demo_migrate_profile_defaults();
      $first=[$meta,$writes];$meta[1]='Custom admin';$writes=[];
      excavator_demo_migrate_profile_defaults();
      echo json_encode([$first,[$meta,$writes]]);
    ` });
    assert.equal(result.exitCode, 0);
    assert.equal(result.errors, '');
    const [first, repeated] = JSON.parse(result.text);
    assert.deepEqual(first, [{ 1: 'admin', 2: 'Custom author', 3: '' }, [1]]);
    assert.deepEqual(repeated, [{ 1: 'Custom admin', 2: 'Custom author', 3: '' }, []]);
  } finally { php.exit(); }
});
