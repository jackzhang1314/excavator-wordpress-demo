import assert from 'node:assert/strict';
import { mkdtemp, readFile, writeFile } from 'node:fs/promises';
import { createServer } from 'node:net';
import { join } from 'node:path';
import { projectRoot, startDemo } from './runtime.mjs';

// A complete, separate WordPress database. Never target a user's saved .runtime.
const runtimeRoot = await mkdtemp(join(projectRoot, '.test-runtime-categories-'));
const probe = createServer();
await new Promise(resolve => probe.listen(0, '127.0.0.1', resolve));
const port = probe.address().port;
await new Promise(resolve => probe.close(resolve));
const demo = await startDemo({ port, runtimeRoot });
const results = [];
const release = JSON.parse(await readFile(join(projectRoot, 'package.json'), 'utf8'));
const migration = (await readFile(join(projectRoot, 'site/category-layouts.php'), 'utf8')).replace(/^<\?php/, '');
try {
  await demo.handler.request({ url: '/', method: 'GET' });
  const php = await demo.handler.getPrimaryPhp();
  async function run(code, admin = false) {
    const r = await php.run({ code: `<?php ${admin ? "define('WP_ADMIN',true);" : ''} require '/wordpress/wp-load.php'; wp_set_current_user(1); ${admin ? "require_once '/wordpress/wp-admin/includes/admin.php';set_current_screen('edit-product_collection');" : ''} ${code}` });
    if (r.exitCode || r.errors) throw new Error(r.errors + r.text);
    return JSON.parse(r.text);
  }
  function check(name, actual, expected) {
    assert.deepEqual(actual, expected, name);
    results.push({ name, status: 'PASS' });
  }
  check('One-time upgrade preserves old nested layout in trash and later user choices', await run(`
    if (!function_exists('excavator_demo_migrate_category_layouts')) { ${migration} }
    $root=wp_insert_term('QA migration root','product_collection');$child=wp_insert_term('QA migration child','product_collection',['parent'=>(int)$root['term_id']]);
    $id=\\Starter\\Model\\category_ensure_page((int)$child['term_id']);wp_trash_post($id);
    delete_option('excavator_demo_category_layout_schema');excavator_demo_migrate_category_layouts();$saved=get_post_meta($id,'_wp_page_template',true);
    update_post_meta($id,'_wp_page_template','default');excavator_demo_migrate_category_layouts();
    echo wp_json_encode([$saved,get_post_meta($id,'_wp_page_template',true),get_post_status($id)]);
  `),['page-templates/category-selection.php','default','trash']);
  const tree = await run(`
    $nodes=[];
    foreach ([['root',0],['branch',1],['leaf',2],['fourth',3],['sibling',1],['root-leaf',0]] as [$name,$parent]) {
      $r=wp_insert_term('QA '.$name,'product_collection',['slug'=>'qa-'.$name,'parent'=>$parent?$nodes[$parent-1]['term']:-1]);
      if(is_wp_error($r))throw new RuntimeException($r->get_error_message());
      $term=(int)$r['term_id'];$id=\\Starter\\Model\\category_ensure_page($term);
      if(is_wp_error($id))throw new RuntimeException($id->get_error_message());
      $nodes[]=['name'=>$name,'term'=>$term,'page'=>$id,'depth'=>\\Starter\\Model\\category_tree_context(get_term($term,'product_collection'))['depth'],'status'=>get_post_status($id)];
    }
    update_option('qa_category_nodes',$nodes,false);echo wp_json_encode($nodes);
  `);
  check('Core taxonomy depth 1/2/3/4 and independent root leaf', tree.map(x => x.depth), [1, 2, 3, 4, 2, 1]);
  check('Created pages are drafts', tree.map(x => x.status), Array(6).fill('draft'));
  const ids = tree.map(x => x.page), terms = tree.map(x => x.term);
  check('A stale option cache cannot overwrite another owner or release another lease',await run(`
    $key='starter_category_probe_atomic';$a=\\Starter\\Model\\category_insert_claim($key,17);
    wp_cache_delete($key,'options');wp_cache_set('notoptions',[$key=>true],'options');
    $b=\\Starter\\Model\\category_insert_claim($key,99);$owner=(int)get_option($key);$wrong=\\Starter\\Model\\category_release_claim($key,99);$same=(int)get_option($key);$right=\\Starter\\Model\\category_release_claim($key,17);
    echo wp_json_encode([$a,$b,$owner,$wrong,$same,$right]);
  `),[true,false,17,false,17,true]);
  check('Native admin depth filters preserve all parent choices and preselect in server HTML', await run(`
    $_GET=['starter_depth'=>3,'starter_parent'=>${terms[1]}];$_REQUEST=$_GET;
    $list=get_terms(['taxonomy'=>'product_collection','hide_empty'=>false,'page'=>1]);
    $all=get_terms(['taxonomy'=>'product_collection','hide_empty'=>false]);
    $args=apply_filters('taxonomy_parent_dropdown_args',['taxonomy'=>'product_collection','hide_empty'=>false,'echo'=>false,'name'=>'parent','show_option_none'=>'None'],'product_collection','new');
    $html=wp_dropdown_categories($args);
    $selected_ids=array_values(array_map(static fn($t)=>$t->term_id,$list));sort($selected_ids);
    echo wp_json_encode([$selected_ids,count($all)>count($list),str_contains($html,'value="${terms[1]}" selected=')]);
  `, true), [[terms[2],terms[3]], true, true]);
  check('Repeated creation reuses page IDs', await run(`echo wp_json_encode(array_map('Starter\\Model\\category_ensure_page',${JSON.stringify(terms)}));`), ids);
  check('A partial create resumes its reserved draft after an expired lock', await run(`
    $term=wp_insert_term('QA partial','product_collection');$term=(int)$term['term_id'];
    $id=wp_insert_post(['post_type'=>'starter_category','post_status'=>'draft','post_title'=>'QA partial preserved']);
    add_option('starter_category_owner_'.$term,$id,'',false);add_option('starter_category_create_'.$term,['token'=>'interrupted','time'=>time()-600],'',false);
    $resumed=\\Starter\\Model\\category_ensure_page($term);
    echo wp_json_encode([$resumed===$id,(int)get_post_meta($id,'category_collection',true)===$term,get_the_title($id)]);
  `), [true,true,'QA partial preserved']);
  check('Trash retains category identity', await run(`wp_trash_post(${ids[2]});$id=\\Starter\\Model\\category_ensure_page(${terms[2]});$state=get_post_status($id);wp_untrash_post($id);echo wp_json_encode([$id,$state,count(\\Starter\\Model\\category_pages_for_term(${terms[2]}))]);`), [ids[2], 'trash', 1]);
  check('All lifecycle states reuse same page', await run(`
    $out=[];foreach(['pending','private','future','publish','draft'] as $status){
      $date=gmdate('Y-m-d H:i:s',time()+($status==='future'?86400:-60));
      wp_update_post(['ID'=>${ids[0]},'post_status'=>$status,'post_date'=>$date,'post_date_gmt'=>$date]);
      $out[]=[\\Starter\\Model\\category_ensure_page(${terms[0]}),get_post_status(${ids[0]})];
    }echo wp_json_encode($out);
  `), ['pending', 'private', 'future', 'publish', 'draft'].map(s => [ids[0], s]));
  check('Metadata imports cannot create duplicate association', await run(`
    $id=wp_insert_post(['post_type'=>'starter_category','post_status'=>'draft','post_title'=>'QA duplicate','meta_input'=>['category_collection'=>${terms[0]}]]);
    echo wp_json_encode([(int)get_post_meta($id,'category_collection',true),count(\\Starter\\Model\\category_pages_for_term(${terms[0]}))]);
  `), [0, 1]);
  check('REST creation uses idempotent category endpoint; generic create rejected', await run(`
    $request=new WP_REST_Request('POST','/starter/v1/product-categories/${terms[0]}/page');$a=rest_do_request($request);
    $request=new WP_REST_Request('POST','/wp/v2/product-category-pages');$request->set_body_params(['title'=>'QA duplicate REST','acf'=>['category_collection'=>${terms[0]}]]);$b=rest_do_request($request);
    echo wp_json_encode([$a->get_status(),$a->get_data()['id'],$b->get_status(),$b->get_data()['code']]);
  `), [200, ids[0], 400, 'starter_category_create_route']);
  check('REST and service enforce permissions', await run(`wp_set_current_user(0);$r=rest_do_request(new WP_REST_Request('POST','/starter/v1/product-categories/${terms[0]}/page'));$x=\\Starter\\Model\\category_ensure_page(${terms[0]});echo wp_json_encode([$r->get_status(),$x->get_error_code()]);`), [401, 'starter_category_forbidden']);
  check('Invalid cycles and wrong-taxonomy parents rejected', await run(`
    $other=wp_insert_term('QA unrelated','category');$checks=[];
    foreach([[${terms[0]},${terms[0]}],[${terms[3]},${terms[0]}],[(int)$other['term_id'],${terms[0]}]] as [$parent,$id])$checks[]=is_wp_error(\\Starter\\Model\\category_validate_parent($parent,$id));
    $r=new WP_REST_Request('POST','/wp/v2/product-categories/${terms[0]}');$r->set_body_params(['parent'=>${terms[3]}]);$response=rest_do_request($r);
    echo wp_json_encode([$checks,$response->get_status(),(int)get_term(${terms[0]},'product_collection')->parent]);
  `), [[true, true, true], 400, 0]);
  check('Moving a node preserves template, page ID and flat URL', await run(`
    update_post_meta(${ids[2]},'_wp_page_template','page-templates/category-selection.php');$url=get_permalink(${ids[2]});
    wp_update_term(${terms[2]},'product_collection',['parent'=>0]);$depth=\\Starter\\Model\\category_tree_context(get_term(${terms[2]},'product_collection'))['depth'];
    $template=get_post_meta(${ids[2]},'_wp_page_template',true);$same=$url===get_permalink(${ids[2]});
    wp_update_term(${terms[2]},'product_collection',['parent'=>${terms[1]}]);
    echo wp_json_encode([$depth,$template,$same,\\Starter\\Model\\category_page_for_term(${terms[2]},false),(int)get_post_field('post_parent',${ids[2]})]);
  `), [1, 'page-templates/category-selection.php', true, ids[2], 0]);
  const data = await run(`
    $image=14;$body='<h2>QA complete guide</h2>'.str_repeat('<p>Long buyer guidance with original text.</p>',100).'<figure><img src="'.esc_url(wp_get_attachment_url($image)).'" alt="QA media"></figure><table><tr><th>Zero</th><td>0</td></tr></table>';
    update_field('field_tc_long_description',$body,${ids[2]});update_field('field_tc_hero_image',$image,${ids[2]});
    update_field('field_tc_spec_items',[['label'=>'Zero','value'=>'0']],${ids[2]});
    update_field('field_tc_content_sections',[['title'=>'QA module','content_source'=>'custom','media_type'=>'image','image'=>$image,'body'=>'<p>QA module body</p>','image_position'=>'left']],${ids[2]});
    foreach(${JSON.stringify(ids)} as $id)wp_update_post(['ID'=>$id,'post_status'=>'publish','post_date'=>current_time('mysql'),'post_date_gmt'=>current_time('mysql',true)]);
    $a=wp_insert_post(['post_type'=>'starter_product','post_status'=>'publish','post_title'=>'QA cross branch product']);wp_set_object_terms($a,[${terms[2]},${terms[4]}],'product_collection');
    $b=wp_insert_post(['post_type'=>'starter_product','post_status'=>'publish','post_title'=>'QA deep product']);wp_set_object_terms($b,[${terms[3]}],'product_collection');
    update_field('field_p_quick_specs_rows',[['quick_label'=>'Operating weight','quick_value'=>'2 t'],['quick_label'=>'Engine power','quick_value'=>'18 kW']],$a);
    update_field('field_p_quick_specs_rows',[['quick_label'=>'Operating weight','quick_value'=>'4 t'],['quick_label'=>'Engine power','quick_value'=>'25 kW']],$b);
    $query=new WP_Query(['post_type'=>'starter_product','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids','tax_query'=>[['taxonomy'=>'product_collection','terms'=>[${terms[0]}],'include_children'=>true]]]);
    echo wp_json_encode(['products'=>[$a,$b],'queried'=>array_values($query->posts),'found'=>(int)$query->found_posts,'bodyLength'=>strlen(get_field('category_long_description',${ids[2]})),'zero'=>get_field('category_spec_items',${ids[2]})[0]['value'],'url'=>get_permalink(${ids[2]})]);
  `);
  check('Parent aggregation deduplicates products assigned to two descendant branches', [...data.queried].sort(), [...data.products].sort());
  check('Aggregated count is distinct product count', data.found, 2);
  check('Long guide, image and zero value saved', [data.bodyLength > 4000, data.zero], [true, '0']);
  check('Native Core REST autosave remains available without changing publication',await run(`
    $request=new WP_REST_Request('POST','/wp/v2/product-category-pages/${ids[2]}/autosaves');
    $request->set_body_params(['title'=>'QA REST autosave title']);$response=rest_do_request($request);
    $data=$response->get_data();
    echo wp_json_encode([$response->get_status()>=200&&$response->get_status()<300,(int)wp_is_post_autosave((int)($data['id']??0))===${ids[2]},get_post_status(${ids[2]}),get_the_title(${ids[2]})]);
  `),[true,true,'publish','QA leaf']);
  for (const i of [0, 1, 2, 3, 5]) {
    const response = await fetch(demo.origin + '/product-category/qa-' + tree[i].name + '/');
    const html = await response.text();
    check('Published level ' + tree[i].depth + ' page renders: ' + tree[i].name, [response.status, html.includes(`data-category-depth="${tree[i].depth}"`)], [200, true]);
    if (i === 2) check('Published guide includes complete text, image and module', [html.includes('QA complete guide'), html.includes('QA media'), html.includes('QA module body')], [true, true, true]);
  }
  check('Nested category can use overview, root leaf can use product selection', await run(`
    update_post_meta(${ids[1]},'_wp_page_template','default');update_post_meta(${ids[5]},'_wp_page_template','page-templates/category-selection.php');
    $out=[];foreach([${ids[1]},${ids[5]}] as $id)$out[]=get_page_template_slug($id);echo wp_json_encode($out);
  `), ['', 'page-templates/category-selection.php']);
  check('Native template field groups are mutually exclusive', await run(`
    $a=acf_get_field_group('group_category_page_standard');$b=acf_get_field_group('group_category_page_selection');
    echo wp_json_encode([$a['location'][0][1]['value'],$b['location'][0][1]['value']]);
  `), ['default', 'page-templates/category-selection.php']);
  for (const [index, style] of [[1,'is-primary'],[5,'is-secondary']]) {
    const r=await fetch(demo.origin+'/product-category/qa-'+tree[index].name+'/'); const html=await r.text();
    check('Actual layout is selected by template at level '+tree[index].depth,[r.status,html.includes('category-standard '+style)],[200,true]);
  }
  const filtered = await fetch(demo.origin + '/product-category/qa-root/?eq_spec%5Boperating-weight%5D=2%20t');
  const filteredHtml = await filtered.text();
  check('Actual specification filter selects one deduplicated product', [filtered.status,filteredHtml.includes('1 model'),filteredHtml.includes('QA cross branch product'),filteredHtml.includes('QA deep product')],[200,true,true,false]);
  check('Same category names in separate branches keep unique stable URLs', await run(`
    $urls=[];foreach([${terms[1]},${terms[4]}] as $parent){$term=wp_insert_term('QA shared name','product_collection',['slug'=>'qa-shared-name','parent'=>$parent]);if(is_wp_error($term))throw new RuntimeException($term->get_error_message());$id=\\Starter\\Model\\category_ensure_page((int)$term['term_id']);wp_update_post(['ID'=>$id,'post_status'=>'publish']);$urls[]=get_permalink($id);}
    echo wp_json_encode(count(array_unique($urls))===2);
  `),true);
  await run(`update_option('posts_per_page',1);echo '{}';`);
  const page2 = await fetch(demo.origin + '/product-category/qa-root/page/2/');
  const html2 = await page2.text();
  check('Real product page 2 has its own canonical', [page2.status, html2.includes(demo.origin + '/product-category/qa-root/page/2/'), /<title>[^<]*Page 2/.test(html2)], [200, true, true]);
  const page3 = await fetch(demo.origin + '/product-category/qa-root/page/3/'); await page3.text();
  check('Out-of-range product page is 404', page3.status, 404);
  await run(`update_option('posts_per_page',10);update_term_meta(${terms[2]},'category_long_description','QA OLD TAXONOMY LEAK');wp_update_post(['ID'=>${ids[2]},'post_status'=>'draft']);echo '{}';`);
  for (const path of ['/product-category/qa-leaf/', '/?product_collection=qa-leaf']) {
    const r = await fetch(demo.origin + path, { redirect: 'manual' }); const html = await r.text();
    check('Withdrawn content and old taxonomy archive isolated: ' + path, [r.status, html.includes('QA OLD TAXONOMY LEAK'), html.includes('QA complete guide')], [404, false, false]);
  }
  check('Withdrawn category has no public card or taxonomy link', await run(`echo wp_json_encode([\\Starter\\Model\\category_public_url(${terms[2]}),\\Starter\\Theme\\equipment_category_card(get_term(${terms[2]},'product_collection')),is_wp_error(get_term_link(${terms[2]},'product_collection'))]);`), ['', [], true]);
  check('Cleared content does not revive old term data', await run(`
    update_field('field_tc_long_description','',${ids[2]});update_field('field_tc_hero_image','',${ids[2]});update_field('field_tc_content_sections',[],${ids[2]});
    echo wp_json_encode([get_post_meta(${ids[2]},'category_long_description',true),get_post_meta(${ids[2]},'category_hero_image',true),get_field('category_content_sections',${ids[2]})?:[]]);
  `), ['', '', []]);
  check('Deleting a classification withdraws its page without deleting content; explicit orphan repair reuses it',await run(`
    $term=wp_insert_term('QA removed classification','product_collection');$term=(int)$term['term_id'];$id=\\Starter\\Model\\category_ensure_page($term);
    update_field('field_tc_long_description','<p>Recoverable deletion guide</p>',$id);wp_update_post(['ID'=>$id,'post_status'=>'publish']);wp_delete_term($term,'product_collection');
    $withdrawn=get_post_status($id)==='draft';$body=get_post_meta($id,'category_long_description',true);
    $new=wp_insert_term('QA recovered classification','product_collection');$new=(int)$new['term_id'];update_field('field_category_collection',$new,$id);
    echo wp_json_encode([$withdrawn,$body,\\Starter\\Model\\category_ensure_page($new)===$id,count(\\Starter\\Model\\category_pages_for_term($new))]);
  `),[true,'<p>Recoverable deletion guide</p>',true,1]);
  const report = { status: 'PASS', verifiedAt: new Date().toISOString(), version: release.version, upgradeBaseline: '1.0.3', scope: 'actual WordPress in an isolated newly seeded database; API and HTTP checks, not native browser acceptance', checks: results };
  await writeFile(join(runtimeRoot, 'category-checks.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify({ status: report.status, checks: results.length, report: join(runtimeRoot, 'category-checks.json') }, null, 2));
} catch (error) {
  console.error(error);
  await writeFile(join(runtimeRoot, 'category-checks.json'), JSON.stringify({ status: 'FAIL', passed: results, error: String(error) }, null, 2));
  process.exitCode = 1;
} finally { await demo.dispose(); }
