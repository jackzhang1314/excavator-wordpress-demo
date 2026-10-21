import assert from 'node:assert/strict';
import { mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { createServer } from 'node:net';
import { join } from 'node:path';
import { projectRoot, startDemo } from './runtime.mjs';

const runtimeRoot = await mkdtemp(join(projectRoot, '.test-runtime-urls-'));
const probe = createServer();
await new Promise(resolve => probe.listen(0, '127.0.0.1', resolve));
const port = probe.address().port;
await new Promise(resolve => probe.close(resolve));
let demo;
const seoFixture = join(runtimeRoot, 'wordpress/wp-content/mu-plugins/qa-indexed-output.php');
const checks = [];
const migration = (await readFile(join(projectRoot, 'site/category-url-migration.php'), 'utf8')).replace(/^<\?php/, '');
try {
  demo = await startDemo({ port, runtimeRoot });
  await fetch(demo.origin + '/').then(r => r.text());
  const php = await demo.handler.getPrimaryPhp();
  async function run(code) {
    const result = await php.run({ code: `<?php require '/wordpress/wp-load.php';wp_set_current_user(1);${code}` });
    if (result.exitCode || result.errors) throw new Error(result.errors + result.text);
    return JSON.parse(result.text);
  }
  function check(name, actual, expected) { assert.deepEqual(actual, expected, name); checks.push({ name, status: 'PASS' }); }
  async function get(path) { const r = await fetch(demo.origin + path, { redirect: 'manual' }); return { status: r.status, location: r.headers.get('location'), html: await r.text() }; }
  const initial = await run(`
    $out=[];foreach(get_posts(['post_type'=>'starter_category','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'ID','order'=>'ASC']) as $post){
      $url=get_permalink($post);$term=(int)get_post_meta($post->ID,'category_collection',true);
      $out[]=['id'=>$post->ID,'slug'=>$post->post_name,'url'=>$url,'resolved'=>url_to_postid($url),'termUrl'=>get_term_link($term,'product_collection')];
    }echo wp_json_encode(['pages'=>$out,'report'=>get_option('excavator_demo_category_url_report'),'schema'=>get_option('excavator_demo_category_url_schema')]);
  `);
  check('15 original category IDs resolve from native root permalinks and taxonomy links', initial.pages.map(p => [p.url, p.resolved, p.termUrl]), initial.pages.map(p => [demo.origin + '/' + p.slug + '/', p.id, demo.origin + '/' + p.slug + '/']));
  check('Existing content upgrade changes only four known URL metadata rows', [initial.pages.length, initial.report.changedRows, initial.schema], [15, { post_content: 0, post_excerpt: 0, postmeta: 4 }, 'root/1']);
  check('Demo preview retains its noindex state', await run(`echo wp_json_encode(get_option('blog_public'));`), '0');
  // Only this disposable runtime temporarily overrides the demo's forced noindex filter.
  // No saved setting, source MU plugin, user runtime or public website changes.
  await writeFile(seoFixture, "<?php add_filter('pre_option_blog_public',static fn()=>'1',PHP_INT_MAX);");
  check('Isolated test fixture exposes native indexable output', await run(`echo wp_json_encode(get_option('blog_public'));`), '1');
  const current = [], legacy = [], canonical = [], oldLinks = [];
  for (const p of initial.pages) {
    const root = await get('/' + p.slug + '/'); current.push(root.status);
    const old = await get('/product-category/' + p.slug + '/'); legacy.push([old.status, old.location]);
    canonical.push(root.html.match(/<link[^>]+rel=["']canonical["'][^>]+href=["']([^"']+)/i)?.[1]);
    oldLinks.push([...root.html.matchAll(/\b(?:href|src)=["']([^"']+)["']/gi)].filter(m => m[1].startsWith(demo.origin + '/product-category/') || m[1].startsWith('/product-category/')).length);
  }
  check('Every new original category returns 200', current, Array(15).fill(200));
  check('Every original prefixed URL redirects once directly to its root URL', legacy, initial.pages.map(p => [301, p.url]));
  check('All original category canonicals point to their root URL', canonical, initial.pages.map(p => p.url));
  check('Rendered original category internal href/src values contain no old category prefix', oldLinks, Array(15).fill(0));
  const xml = await get('/starter_category-sitemap.xml');
  check('Actual Rank Math category sitemap is XML and contains all new URLs only', [xml.status, /<urlset\b/.test(xml.html), initial.pages.every(p => xml.html.includes(p.url)), xml.html.includes('/product-category/')], [200, true, true, false]);
  const index = await get('/sitemap_index.xml');
  check('Native sitemap index exposes the content sitemap and no taxonomy duplicate', [index.status, index.html.includes('/starter_category-sitemap.xml'), index.html.includes('/product_collection-sitemap.xml')], [200, true, false]);
  check('Custom navigation canonicalizes local category links with query/fragment and leaves foreign URLs alone', await run(`
    $out=[];foreach([home_url('/product-category/small-digger/?utm_source=menu#products'),'https://outside.example/small-digger/'] as $url){$id=wp_update_nav_menu_item(0,0,['menu-item-title'=>'QA link','menu-item-url'=>$url,'menu-item-type'=>'custom','menu-item-status'=>'publish']);$out[]=wp_setup_nav_menu_item(get_post($id))->url;}echo wp_json_encode($out);
  `),[demo.origin+'/small-digger/?utm_source=menu#products','https://outside.example/small-digger/']);
  const rulesBefore = await run(`echo wp_json_encode(hash('sha256',serialize(get_option('rewrite_rules'))));`);
  await get('/construction-digger/'); await get('/small-digger/');
  check('Normal page requests do not regenerate stored rewrite rules', await run(`echo wp_json_encode(hash('sha256',serialize(get_option('rewrite_rules'))));`), rulesBefore);
  check('Home, root Page, blog, product archive, product and industry routes keep their owners', await run(`
    $out=[];foreach(['page','post','starter_product','starter_industry'] as $type){$p=get_posts(['post_type'=>$type,'post_status'=>'publish','posts_per_page'=>1])[0];$out[]=[get_post_type(url_to_postid(get_permalink($p))),$type];}
    echo wp_json_encode($out);
  `), [['page','page'],['post','post'],['starter_product','starter_product'],['starter_industry','starter_industry']]);
  for (const path of ['/about/','/blog/','/products/','/wp-json/']) {
    const r = await get(path); check('Existing reserved route remains available: ' + path, r.status, 200);
  }
  check('Native sample permalink and plain draft preview use the correct Core contracts', await run(`
    require_once ABSPATH.'wp-admin/includes/post.php';$term=wp_insert_term('QA draft URL','product_collection');$id=\\Starter\\Model\\category_ensure_page((int)$term['term_id']);
    $sample=get_sample_permalink($id);$preview=get_preview_post_link($id);
    echo wp_json_encode([str_contains($sample[0],'/%pagename%/'),$sample[1],str_contains($preview,'p='.$id),str_contains($preview,'preview=true'),get_post_status($id)]);
  `), [true,'qa-draft-url',true,true,'draft']);
  const fixture = await run(`
    $root=wp_insert_term('QA URL root','product_collection');$term=(int)$root['term_id'];$id=\\Starter\\Model\\category_ensure_page($term);
    update_field('field_tc_long_description','<h2>QA persistent URL guide</h2><p>Keep this content.</p>',$id);update_post_meta($id,'_wp_page_template','page-templates/category-selection.php');
    wp_update_post(['ID'=>$id,'post_status'=>'publish']);$child=wp_insert_term('QA URL child','product_collection',['parent'=>$term]);$childId=\\Starter\\Model\\category_ensure_page((int)$child['term_id']);wp_update_post(['ID'=>$childId,'post_status'=>'publish']);
    $products=[];foreach(['one','two'] as $suffix){$p=wp_insert_post(['post_type'=>'starter_product','post_status'=>'publish','post_title'=>'QA URL product '.$suffix]);wp_set_object_terms($p,[(int)$child['term_id']],'product_collection');update_field('field_p_quick_specs_rows',[['quick_label'=>'Operating weight','quick_value'=>'2t']],$p);$products[]=$p;}
    update_option('posts_per_page',1);echo wp_json_encode(['id'=>$id,'term'=>$term,'childId'=>$childId,'childTerm'=>(int)$child['term_id'],'products'=>$products,'slug'=>get_post_field('post_name',$id)]);
  `);
  const f = fixture;
  const second = await get('/' + f.slug + '/page/2/');
  check('New root category pagination returns its own canonical and Page 2 title', [second.status, second.html.includes(`href="${demo.origin}/${f.slug}/page/2/"`), /<title>[^<]*Page 2/.test(second.html)], [200,true,true]);
  const oldSecond = await get('/product-category/' + f.slug + '/page/2/?utm_source=qa&eq_spec%5Boperating-weight%5D=2t');
  check('Old pagination preserves page, UTM and filter query in one same-origin 301', [oldSecond.status, oldSecond.location], [301,`${demo.origin}/${f.slug}/page/2/?utm_source=qa&eq_spec%5Boperating-weight%5D=2t`]);
  check('Native URL resolver understands the root category second page', await run(`echo wp_json_encode(url_to_postid(home_url('/${f.slug}/page/2/')));`), f.id);
  for (const path of [`/${f.slug}/page/3/`, `/product-category/${f.slug}/page/3/`, '/product-category/definitely-missing/', '/definitely-missing-root-qa/']) {
    const r = await get(path); check('Missing category or out-of-range pagination stays 404: ' + path, r.status, 404);
  }
  check('Moving a classification retains root URL, ID, native template and complete guide', await run(`
    $url=get_permalink(${f.childId});wp_update_term(${f.childTerm},'product_collection',['parent'=>0]);$first=get_permalink(${f.childId});wp_update_term(${f.childTerm},'product_collection',['parent'=>${f.term}]);
    echo wp_json_encode([$first===$url,get_permalink(${f.childId})===$url,get_post_meta(${f.id},'_wp_page_template',true),get_post_meta(${f.id},'category_long_description',true)]);
  `), [true,true,'page-templates/category-selection.php','<h2>QA persistent URL guide</h2><p>Keep this content.</p>']);
  check('A partial ACF preview snapshot keeps canonical category identity while exposing changed editorial fields',await run(`
    $revision=wp_insert_post(['post_type'=>'revision','post_status'=>'inherit','post_parent'=>${f.id},'post_title'=>'QA partial preview']);
    update_field('field_tc_intro','QA changed preview intro',$revision);
    $GLOBALS['wp_query']=new WP_Query(['post_type'=>'starter_category','p'=>${f.id}]);
    add_filter('acf/validate_post_id',static fn($id)=>is_numeric($id)&&(int)$id===${f.id}?$revision:$id,100);
    $term=\\Starter\\Theme\\category_current_term();echo wp_json_encode([(int)get_field('category_collection',${f.id}),$term?$term->term_id:0,get_field('category_intro',${f.id})]);
  `),[0,f.term,'QA changed preview intro']);
  check('New category drafts and reserved names never shadow existing root owners', await run(`
    $out=[];foreach(['about','products','wp-admin','small-digger'] as $slug){$term=wp_insert_term('QA collision '.$slug,'product_collection');$id=\\Starter\\Model\\category_ensure_page((int)$term['term_id']);wp_update_post(['ID'=>$id,'post_name'=>$slug]);$draft=get_post_field('post_name',$id);wp_update_post(['ID'=>$id,'post_status'=>'publish']);$out[]=[$draft,$slug!==get_post_field('post_name',$id),url_to_postid(get_permalink($id))===$id];}
    echo wp_json_encode($out);
  `), [['about-2',true,true],['products-2',true,true],['equipment-wp-admin-2',true,true],['small-digger-2',true,true]]);
  check('Creating Page/article drafts cannot steal a published category root; nested Pages and prefixed products stay independent', await run(`
    $out=[];foreach(['page','post'] as $type){$id=wp_insert_post(['post_type'=>$type,'post_status'=>'draft','post_title'=>'QA root collision','post_name'=>'construction-digger']);$out[]=get_post_field('post_name',$id)!=='construction-digger';}
    $parent=get_page_by_path('about');$nested=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'QA nested Page','post_name'=>'construction-digger','post_parent'=>$parent->ID]);
    $product=wp_insert_post(['post_type'=>'starter_product','post_status'=>'publish','post_title'=>'QA prefixed product','post_name'=>'construction-digger']);
    echo wp_json_encode([$out,get_post_field('post_name',$nested),get_post_field('post_name',$product),url_to_postid(home_url('/construction-digger/'))]);
  `), [[true,true],'construction-digger','construction-digger',20]);
  await run(`wp_update_post(['ID'=>${f.id},'post_name'=>'qa-url-renamed']);echo '{}';`);
  for (const oldPath of [`/${f.slug}/`,`/product-category/${f.slug}/`]) {
    const r = await get(oldPath); check('Native slug history redirects straight to the current category: ' + oldPath, [r.status,r.location], [301,demo.origin+'/qa-url-renamed/']);
  }
  check('Old category root remains reserved against new Page/article ownership', await run(`$id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'QA alias collision','post_name'=>'${f.slug}']);echo wp_json_encode(get_post_field('post_name',$id));`), f.slug+'-2');
  await run(`wp_update_post(['ID'=>${f.id},'post_status'=>'draft']);echo '{}';`);
  for (const path of ['/qa-url-renamed/',`/${f.slug}/`,`/product-category/${f.slug}/`,'/product-category/qa-url-renamed/']) {
    const r = await get(path); check('Withdrawn current and historical routes expose no content: '+path,[r.status,r.html.includes('QA persistent URL guide')],[404,false]);
  }
  await run(`wp_update_post(['ID'=>${f.id},'post_status'=>'publish']);wp_trash_post(${f.id});echo '{}';`);
  const trashed = await get('/qa-url-renamed/'); check('Trashed root does not expose content', [trashed.status,trashed.html.includes('QA persistent URL guide')],[404,false]);
  check('Restore and publish recovers the same root page ID and content', await run(`wp_untrash_post(${f.id});wp_update_post(['ID'=>${f.id},'post_status'=>'publish']);echo wp_json_encode([url_to_postid(get_permalink(${f.id})),get_post_meta(${f.id},'category_long_description',true)]);`), [f.id,'<h2>QA persistent URL guide</h2><p>Keep this content.</p>']);
  check('Exact URL replacement preserves serialized/JSON structures, query strings, foreign hosts and partial names', await run(`
    if(!function_exists('excavator_category_url_value')){${migration}}
    $old=home_url('/product-category/small-digger/?utm_source=qa#guide');$value=['url'=>$old,'nested'=>['/product-category/small-digger/page/2/','https://outside.example/product-category/small-digger/','/product-category/small-digger-special/']];
    $a=unserialize(excavator_category_url_value(serialize($value),['small-digger']));$b=json_decode(excavator_category_url_value(wp_json_encode($value),['small-digger']),true);
    echo wp_json_encode([$a,$a===$b]);
  `), [{url:demo.origin+'/small-digger/?utm_source=qa#guide',nested:['/small-digger/page/2/','https://outside.example/product-category/small-digger/','/product-category/small-digger-special/']},true]);
  check('A second startup migration preserves newer edits and does not reseed content', await run(`
    if(!function_exists('excavator_demo_migrate_category_urls')){${migration}}
    update_field('field_tc_long_description','<p>QA newer edit remains</p>',${f.id});$before=get_option('excavator_demo_category_url_report');excavator_demo_migrate_category_urls();
    echo wp_json_encode([get_post_meta(${f.id},'category_long_description',true),$before===get_option('excavator_demo_category_url_report')]);
  `), ['<p>QA newer edit remains</p>',true]);
  check('A pre-existing root conflict stops migration before rewriting content or claiming the URL', await run(`
    if(!function_exists('excavator_demo_migrate_category_urls')){${migration}}
    global $wpdb;$id=wp_insert_post(['post_type'=>'page','post_status'=>'draft','post_title'=>'QA legacy import conflict','post_name'=>'qa-conflict-slot']);
    $before=hash('sha256',serialize($wpdb->get_results("SELECT * FROM {$wpdb->postmeta} ORDER BY meta_id",ARRAY_A)));
    $wpdb->update($wpdb->posts,['post_name'=>'construction-digger'],['ID'=>$id]);delete_option('excavator_demo_category_url_schema');$blocked=false;
    try{excavator_demo_migrate_category_urls();}catch(RuntimeException $e){$blocked=str_contains($e->getMessage(),'20:construction-digger');}
    $same=$before===hash('sha256',serialize($wpdb->get_results("SELECT * FROM {$wpdb->postmeta} ORDER BY meta_id",ARRAY_A)));
    $wpdb->update($wpdb->posts,['post_name'=>'qa-conflict-slot'],['ID'=>$id]);clean_post_cache($id);update_option('excavator_demo_category_url_schema','root/1',false);
    echo wp_json_encode([$blocked,$same,get_post_field('post_name',20)]);
  `),[true,true,'construction-digger']);
  await rm(seoFixture);
  check('Remove the test override and restore native demo noindex', await run(`echo wp_json_encode(get_option('blog_public'));`), '0');
  const report={status:'PASS',verifiedAt:new Date().toISOString(),version:'1.2.0',scope:'isolated new WordPress database, actual Core/Rank Math/HTTP checks; not native browser or Google indexing acceptance',checks};
  await writeFile(join(runtimeRoot,'category-url-checks.json'),JSON.stringify(report,null,2));
  console.log(JSON.stringify({status:'PASS',checks:checks.length,report:join(runtimeRoot,'category-url-checks.json')},null,2));
} catch(error) {
  console.error(error);process.exitCode=1;
  await writeFile(join(runtimeRoot,'category-url-checks.json'),JSON.stringify({status:'FAIL',checks,error:String(error)},null,2));
} finally {await rm(seoFixture,{force:true});if(demo)await demo.dispose();}
