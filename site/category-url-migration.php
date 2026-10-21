<?php
/** Explicit local-demo URL migration. No content reseed, slug change or historic inquiry rewrite. */
function excavator_category_url_value($value, array $slugs) {
    if (is_array($value)) {
        foreach ($value as $key => $item) $value[$key] = excavator_category_url_value($item, $slugs);
        return $value;
    }
    if (!is_string($value) || !str_contains($value, 'product-category')) return $value;
    if (is_serialized($value)) {
        if (preg_match('/(?:^|[;{}])(?:O|C):[0-9]+:/', $value)) return $value;
        $decoded = @unserialize($value, ['allowed_classes' => false]);
        if ($decoded === false && $value !== 'b:0;') return $value;
        // Do not rewrite opaque serialized objects or their internal fields.
        if (is_object($decoded)) return $value;
        $next = excavator_category_url_value($decoded, $slugs);
        return $next === $decoded ? $value : serialize($next);
    }
    if (in_array(substr($value, 0, 1), ['{', '['], true)) {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $next = excavator_category_url_value($decoded, $slugs);
            return $next === $decoded ? $value : wp_json_encode($next, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }
    $origins = array_unique([rtrim(home_url(), '/'), rtrim(site_url(), '/')]);
    $prefix = '(?:' . implode('|', array_map(static fn($origin) => preg_quote($origin, '~'), $origins)) . ')?';
    foreach ($slugs as $slug) {
        $pattern = '~(?<![A-Za-z0-9_/.:\-])' . $prefix . '/product-category/' . preg_quote($slug, '~') . '(?=/|[?#"\'\s<>]|$)~';
        $value = preg_replace_callback($pattern, static fn($match) => str_replace('/product-category/' . $slug, '/' . $slug, $match[0]), $value);
    }
    return $value;
}

function excavator_demo_migrate_category_urls(): void {
    if (get_option('excavator_demo_category_url_schema') === 'root/1') return;
    global $wpdb;
    // A new import activated the plugin after init; register its owned model before auditing routes.
    if (!post_type_exists('starter_category')) \Starter\Model\register_model();
    $posts = $wpdb->get_results("SELECT ID,post_name FROM {$wpdb->posts} WHERE post_type='starter_category' AND post_status IN ('publish','private','future') ORDER BY ID");
    $conflicts = []; $slugs = [];
    foreach ($posts as $post) {
        if (\Starter\Model\category_root_claimed($post->post_name, (int) $post->ID)) $conflicts[] = $post->ID . ':' . $post->post_name;
        $slugs[] = $post->post_name;
    }
    if ($conflicts) throw new RuntimeException('Root category URLs conflict with existing or reserved routes. Preserve the existing owners and resolve these category slugs first: ' . implode(', ', $conflicts));
    $counts = ['post_content' => 0, 'post_excerpt' => 0, 'postmeta' => 0];
    $types = ['page', 'post', 'starter_category', 'starter_product', 'starter_industry', 'starter_guide', 'starter_profile', 'nav_menu_item'];
    $quoted = implode(',', array_fill(0, count($types), '%s'));
    foreach ($wpdb->get_results($wpdb->prepare("SELECT ID,post_content,post_excerpt FROM {$wpdb->posts} WHERE post_type IN ($quoted)", ...$types), ARRAY_A) as $row) {
        foreach (['post_content', 'post_excerpt'] as $column) {
            $next = excavator_category_url_value($row[$column], $slugs);
            if ($next === $row[$column]) continue;
            if ($wpdb->update($wpdb->posts, [$column => $next], ['ID' => $row['ID'], $column => $row[$column]]) !== 1) throw new RuntimeException('Category URL migration stopped on changed content: ' . $row['ID']);
            $counts[$column]++;
        }
    }
    foreach ($wpdb->get_results($wpdb->prepare("SELECT m.meta_id,m.meta_value FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID=m.post_id WHERE p.post_type IN ($quoted) AND m.meta_key NOT IN ('_wp_attachment_metadata','_edit_lock') AND m.meta_key NOT LIKE '_starter_%%'", ...$types), ARRAY_A) as $row) {
        $next = excavator_category_url_value($row['meta_value'], $slugs);
        if ($next === $row['meta_value']) continue;
        if ($wpdb->update($wpdb->postmeta, ['meta_value' => $next], ['meta_id' => $row['meta_id'], 'meta_value' => $row['meta_value']]) !== 1) throw new RuntimeException('Category URL migration stopped on changed metadata: ' . $row['meta_id']);
        $counts['postmeta']++;
    }
    wp_cache_flush();
    update_option('excavator_demo_category_url_report', ['version' => 'root/1', 'categoryIds' => array_map(static fn($post) => (int) $post->ID, $posts), 'changedRows' => $counts], false);
    update_option('excavator_demo_category_url_schema', 'root/1', false);
    flush_rewrite_rules(false);
}
