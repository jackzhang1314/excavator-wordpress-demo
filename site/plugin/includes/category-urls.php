<?php
/** Root category URLs, explicit native rewrite rules and a shared root namespace. */
namespace Starter\Model;
defined('ABSPATH') || exit;

function category_root_post_type(string $type, int $parent = 0): bool {
    global $wp_rewrite;
    return $type === 'starter_category' || ($type === 'page' && !$parent)
        || ($type === 'post' && str_starts_with(ltrim($wp_rewrite->permalink_structure, '/'), '%postname%'));
}

function category_reserved_roots(): array {
    global $wp_rewrite;
    $roots = ['wp-admin', 'wp-json', 'wp-content', 'wp-includes', 'wp-login.php', 'wp-cron.php', 'xmlrpc.php',
        'robots.txt', 'favicon.ico', 'sitemap.xml', 'sitemap_index.xml', 'wp-sitemap.xml', 'product-category',
        'page', 'feed', 'embed', 'trackback', 'comments', 'search', 'author'];
    $bases = [$wp_rewrite->pagination_base, $wp_rewrite->search_base, $wp_rewrite->author_base,
        get_option('category_base') ?: 'category', get_option('tag_base') ?: 'tag'];
    foreach (get_post_types(['public' => true], 'objects') as $type) {
        if ($type->name === 'starter_category') continue;
        if (is_array($type->rewrite)) $bases[] = $type->rewrite['slug'];
        if (is_string($type->has_archive)) $bases[] = $type->has_archive;
    }
    foreach (get_taxonomies(['public' => true], 'objects') as $taxonomy) {
        if (is_array($taxonomy->rewrite)) $bases[] = $taxonomy->rewrite['slug'];
    }
    foreach (array_merge($bases, $wp_rewrite->feeds) as $base) {
        $first = explode('/', trim((string) $base, '/'))[0];
        if ($first !== '' && !str_contains($first, '%')) $roots[] = $first;
    }
    return array_values(array_unique($roots));
}

/** IDs, not the current query or global $post, determine ownership. Includes drafts/trash and old category aliases. */
function category_root_claimed(string $slug, int $except = 0): bool {
    global $wpdb;
    if ($slug === '' || ctype_digit($slug) || in_array($slug, category_reserved_roots(), true)
        || preg_match('/^(?:wp-|.+-sitemap(?:\d+)?\.xml$)/', $slug)) return true;
    $posts = $wpdb->get_results($wpdb->prepare("SELECT ID,post_type,post_parent FROM {$wpdb->posts} WHERE post_name=%s AND ID<>%d AND post_type<>'revision' AND post_status<>'auto-draft'", $slug, $except));
    foreach ($posts as $post) {
        if ($post->post_type === 'attachment' || category_root_post_type($post->post_type, (int) $post->post_parent)) return true;
    }
    return (bool) $wpdb->get_var($wpdb->prepare("SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id=p.ID WHERE p.post_type='starter_category' AND p.ID<>%d AND m.meta_key IN ('_wp_old_slug','_wp_desired_post_slug') AND m.meta_value=%s LIMIT 1", $except, $slug));
}

// Keep native per-type uniqueness, then prevent a category, root Page or root article from taking another root URL.
add_filter('wp_unique_post_slug', static function (string $slug, int $id, string $status, string $type, int $parent, string $original): string {
    if (!category_root_post_type($type, $parent)) return $slug;
    $candidate = $slug; $suffix = 2;
    $base = str_starts_with($original, 'wp-') ? 'equipment-' . $original : $original;
    while (category_root_claimed($candidate, $id)) {
        $tail = '-' . $suffix++;
        $candidate = _truncate_post_slug($base, 200 - strlen($tail)) . $tail;
    }
    return $candidate;
}, 20, 6);

// Core defers draft slug uniqueness. A new draft must not hide an already published root route.
add_filter('wp_insert_post_data', static function (array $data, array $postarr): array {
    if (in_array($data['post_status'], ['draft', 'pending'], true) && $data['post_name'] !== ''
        && category_root_post_type($data['post_type'], (int) $data['post_parent'])) {
        $data['post_name'] = wp_unique_post_slug($data['post_name'], (int) ($postarr['ID'] ?? 0), 'publish', $data['post_type'], (int) $data['post_parent']);
    }
    return $data;
}, 20, 2);

function category_root_path(string $slug, int $page = 1): string {
    global $wp_rewrite;
    return '/' . user_trailingslashit($slug . ($page > 1 ? '/' . $wp_rewrite->pagination_base . '/' . $page : ''));
}

add_filter('post_type_link', static function ($url, $post, bool $leave_name, bool $sample) {
    if (!$post instanceof \WP_Post) $post = is_object($post) && isset($post->ID) ? get_post((int) $post->ID) : null;
    if (!$post instanceof \WP_Post || $post->post_type !== 'starter_category') return $url;
    // Core's plain draft preview URL carries the post ID and its native authorization.
    if (!$sample && in_array($post->post_status, ['draft', 'pending', 'auto-draft'], true)) return $url;
    return home_url(category_root_path($leave_name ? '%starter_category%' : $post->post_name));
}, 10, 4);

/** Specific stored rules also work in Core url_to_postid() and Harness route proofs. No root wildcard. */
add_filter('rewrite_rules_array', static function (array $rules): array {
    global $wpdb, $wp_rewrite;
    $extra = [];
    $posts = $wpdb->get_results("SELECT ID,post_name FROM {$wpdb->posts} WHERE post_type='starter_category' AND post_status IN ('publish','private','future') ORDER BY ID");
    foreach ($posts as $post) {
        if (category_root_claimed($post->post_name, (int) $post->ID)) continue;
        $slugs = array_unique(array_merge([$post->post_name], get_post_meta((int) $post->ID, '_wp_old_slug')));
        foreach ($slugs as $slug) {
            if (category_root_claimed((string) $slug, (int) $post->ID)) continue;
            $pattern = preg_quote((string) $slug, '#');
            $target = 'index.php?starter_category=' . rawurlencode($post->post_name);
            $extra['^' . $pattern . '/' . preg_quote($wp_rewrite->pagination_base, '#') . '/([1-9][0-9]*)/?$'] = $target . '&paged=$matches[1]';
            $extra['^' . $pattern . '/?$'] = $target;
        }
    }
    return $extra + $rules;
});

// A normal publish, withdrawal or slug edit refreshes only when the category URL set actually changes.
add_action('wp_after_insert_post', static function (int $id, \WP_Post $post, bool $update, ?\WP_Post $before): void {
    if ($post->post_type !== 'starter_category') return;
    if (!$before || $before->post_name !== $post->post_name || $before->post_status !== $post->post_status) flush_rewrite_rules(false);
}, 30, 4);
add_action('deleted_post', static function (int $id, \WP_Post $post): void {
    if ($post->post_type === 'starter_category') flush_rewrite_rules(false);
}, 10, 2);

// Preserve old prefixed links after a later native category slug change as well.
add_filter('request', static function (array $vars): array {
    global $wpdb;
    $slug = (string) ($vars['starter_category'] ?? '');
    if (!$slug || get_page_by_path($slug, OBJECT, ['starter_category'])) return $vars;
    $ids = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id=p.ID WHERE p.post_type='starter_category' AND p.post_status='publish' AND m.meta_key='_wp_old_slug' AND m.meta_value=%s", $slug));
    if (count($ids) === 1) $vars['starter_category'] = get_post_field('post_name', (int) $ids[0]);
    return $vars;
}, 5);

add_action('template_redirect', static function (): void {
    global $wp;
    if (!is_singular('starter_category') || is_preview() || !in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) return;
    $id = get_queried_object_id();
    $term = (int) get_post_meta($id, 'category_collection', true);
    if (category_public_url($term) === '' || is_404()) return;
    $path = category_root_path(get_post_field('post_name', $id), max(1, (int) get_query_var('paged')));
    $current = '/' . $wp->request . (str_ends_with((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/') ? '/' : '');
    if ($current === $path) return;
    $query = array_diff_key(wp_unslash($_GET), array_fill_keys(['p', 'page_id', 'post_type', 'starter_category', 'product_collection', 'name', 'pagename', 'paged', 'page'], true));
    wp_safe_redirect($query ? add_query_arg($query, home_url($path)) : home_url($path), 301, 'Equipment category URL'); exit;
}, 8);
add_filter('do_redirect_guess_404_permalink', static function (bool $guess): bool {
    global $wp, $wpdb;
    if (str_starts_with($wp->request, 'product-category/')) return false;
    if (preg_match('~^([^/]+)(?:/page/[0-9]+)?/?$~', $wp->request, $match)) {
        $slug = sanitize_title($match[1]);
        $known = $wpdb->get_var($wpdb->prepare("SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id=p.ID AND m.meta_key IN ('_wp_old_slug','_wp_desired_post_slug') WHERE p.post_type='starter_category' AND (p.post_name=%s OR m.meta_value=%s) LIMIT 1", $slug, $slug));
        if ($known) return false;
    }
    return $guess;
});
// Core's old-slug lookup does not filter publication status. Category aliases are resolved above.
add_filter('old_slug_redirect_post_id', static fn(int $id): int => get_post_type($id) === 'starter_category' ? 0 : $id);
