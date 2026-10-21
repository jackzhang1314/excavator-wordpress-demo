<?php
/** Category identity and page ownership; taxonomy remains the only structure. */
namespace Starter\Model;
defined('ABSPATH') || exit;

function category_tree_context(\WP_Term $term): array {
    $ancestors = array_reverse(get_ancestors($term->term_id, 'product_collection', 'taxonomy'));
    $path = [];
    foreach (array_merge($ancestors, [$term->term_id]) as $id) {
        $node = get_term((int) $id, 'product_collection');
        if ($node instanceof \WP_Term) $path[] = $node->name;
    }
    return ['depth' => count($ancestors) + 1, 'ancestor_ids' => $ancestors, 'path' => implode(' / ', $path),
        'children' => get_terms(['taxonomy' => 'product_collection', 'parent' => $term->term_id, 'hide_empty' => false])];
}

/** Include trash and every native status; a withdrawn page still owns its classification. */
function category_pages_for_term(int $term_id): array {
    return array_values(array_unique(array_map('intval', get_posts([
        'post_type' => 'starter_category', 'post_status' => array_keys(get_post_stati()),
        'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC',
        'meta_key' => 'category_collection', 'meta_value' => $term_id,
    ]))));
}

function category_public_url(int $term_id): string {
    $ids = category_pages_for_term($term_id);
    if (count($ids) !== 1 || get_post_status($ids[0]) !== 'publish' || get_post_field('post_password', $ids[0]) !== ''
        || category_root_claimed(get_post_field('post_name', $ids[0]), $ids[0])) return '';
    return (string) get_permalink($ids[0]);
}

function category_validate_parent(int $parent, int $id = 0): bool|\WP_Error {
    if ($parent === 0 || $parent === -1) return true; // Core's native None option uses -1.
    $term = get_term($parent, 'product_collection');
    if (!$term instanceof \WP_Term) return new \WP_Error('starter_category_parent', 'Choose an existing product category as the parent.', ['status' => 400]);
    if ($id && ($parent === $id || in_array($id, get_ancestors($parent, 'product_collection', 'taxonomy'), true))) {
        return new \WP_Error('starter_category_cycle', 'A category cannot be its own parent or be moved below one of its descendants.', ['status' => 400]);
    }
    return true;
}

function category_validate_link(int $id, int $term_id): bool|\WP_Error {
    if (!$term_id || !get_term($term_id, 'product_collection') instanceof \WP_Term) {
        return new \WP_Error('starter_category_missing', 'Choose a valid product classification.', ['status' => 400]);
    }
    $owners = category_pages_for_term($term_id);
    if (array_diff($owners, [$id])) return new \WP_Error('starter_category_conflict', 'This category already has a content page, including drafts or trash. Open the existing page.', ['status' => 409, 'page_ids' => $owners]);
    $previous = (int) get_post_meta($id, 'category_collection', true);
    if ($previous && $previous !== $term_id) {
        $repair = !get_term($previous, 'product_collection') instanceof \WP_Term
            && in_array(get_post_status($id), ['draft', 'pending', 'auto-draft'], true)
            && current_user_can('manage_categories') && current_user_can('edit_post', $id);
        if (!$repair) return new \WP_Error('starter_category_identity', 'Edit the classification structure separately. An existing content page keeps its category identity.', ['status' => 409]);
    }
    return true;
}

/** Unlike Core add_option's upsert, this never overwrites a competing claim. */
function category_insert_claim(string $key, $value): bool|\WP_Error {
    global $wpdb;
    $result = $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,%s,'no')", $key, maybe_serialize($value)));
    wp_cache_delete($key, 'options'); wp_cache_delete('notoptions', 'options');
    return $result === false ? new \WP_Error('starter_category_storage', 'Category ownership could not be saved. Inspect the existing draft before retrying.', ['status' => 500]) : $result === 1;
}
function category_release_claim(string $key, $expected): bool {
    global $wpdb;
    $result = $wpdb->delete($wpdb->options, ['option_name' => $key, 'option_value' => maybe_serialize($expected)], ['%s', '%s']);
    wp_cache_delete($key, 'options'); wp_cache_delete('notoptions', 'options');
    return $result === 1;
}

/** Core metadata APIs are also used by ACF, imports and REST updates. */
function category_guard_link($check, int $id, string $key, $value) {
    if ($check !== null || $key !== 'category_collection' || get_post_type($id) !== 'starter_category') return $check;
    $term_id = is_scalar($value) && ctype_digit((string) $value) ? (int) $value : 0;
    if (is_wp_error(category_validate_link($id, $term_id))) return false;
    $claim = 'starter_category_owner_' . $term_id;
    $owner = (int) get_option($claim, 0);
    if ($owner && !get_post($owner)) { category_release_claim($claim, $owner); $owner = (int) get_option($claim, 0); }
    if (!$owner) {
        $inserted = category_insert_claim($claim, $id);
        if (is_wp_error($inserted)) return false;
        $owner = $inserted ? $id : (int) get_option($claim, 0);
    }
    return $owner !== $id ? false : null;
}
add_filter('add_post_metadata', __NAMESPACE__ . '\\category_guard_link', 10, 4);
add_filter('update_post_metadata', __NAMESPACE__ . '\\category_guard_link', 10, 4);
add_filter('delete_post_metadata', static function ($check, int $id, string $key) {
    return $key === 'category_collection' && get_post_type($id) === 'starter_category' ? false : $check;
}, 10, 3);

/** Idempotent creation: retries open the same page; no content is copied or published. */
function category_ensure_page(int $term_id): int|\WP_Error {
    $taxonomy = get_taxonomy('product_collection');
    if (!$taxonomy || !current_user_can($taxonomy->cap->manage_terms) || !current_user_can('edit_posts')) return new \WP_Error('starter_category_forbidden', 'You cannot manage product categories.', ['status' => 403]);
    $term = get_term($term_id, 'product_collection');
    if (!$term instanceof \WP_Term) return new \WP_Error('starter_category_missing', 'Product category not found.', ['status' => 404]);
    $lock = 'starter_category_create_' . $term_id;
    $lease = ['token' => wp_generate_uuid4(), 'time' => time()];
    $acquired = category_insert_claim($lock, $lease);
    if (is_wp_error($acquired)) return $acquired;
    if (!$acquired) {
        $existing = category_pages_for_term($term_id);
        if (count($existing) === 1) return $existing[0];
        $pending = get_option($lock);
        if (is_array($pending) && time() - (int) ($pending['time'] ?? time()) > 300) {
            // Compare the exact expired value; never delete another request's newer lock.
            if (category_release_claim($lock, $pending)) return category_ensure_page($term_id);
        }
        return new \WP_Error('starter_category_busy', 'A creation operation is still pending. Reopen the category list and inspect its page before retrying.', ['status' => 409]);
    }
    try {
        $existing = category_pages_for_term($term_id);
        if (count($existing) > 1) return new \WP_Error('starter_category_conflict', 'Multiple content pages claim this category. Resolve the conflict before continuing.', ['status' => 409, 'page_ids' => $existing]);
        if ($existing) return $existing[0];
        // A previous partial create can have reserved an ID before its metadata write failed.
        $id = (int) get_option('starter_category_owner_' . $term_id, 0);
        if ($id && !get_post($id)) { category_release_claim('starter_category_owner_' . $term_id, $id); $id = 0; }
        if ($id && get_post_type($id) !== 'starter_category') return new \WP_Error('starter_category_conflict', 'The ownership record belongs to a different content type. Inspect it before retrying.', ['status' => 409]);
        if (!$id || get_post_type($id) !== 'starter_category') {
            $id = wp_insert_post(['post_type' => 'starter_category', 'post_status' => 'draft', 'post_title' => $term->name,
                'post_name' => $term->slug, 'meta_input' => ['_wp_page_template' => 'default']], true);
            if (is_wp_error($id)) return $id;
            $reserved = category_insert_claim('starter_category_owner_' . $term_id, $id);
            if (is_wp_error($reserved) || (!$reserved && (int) get_option('starter_category_owner_' . $term_id) !== $id)) return new \WP_Error('starter_category_busy', 'A draft was created but ownership could not be confirmed. Inspect the category before retrying.', ['status' => 409, 'post_id' => $id]);
        }
        update_field('field_category_collection', $term_id, $id);
        if ((int) get_post_meta($id, 'category_collection', true) !== $term_id) return new \WP_Error('starter_category_link_failed', 'The draft exists but linking is incomplete. Retry from this category to resume.', ['status' => 500, 'post_id' => $id]);
        return (int) $id;
    } finally {
        category_release_claim($lock, $lease);
    }
}

add_action('before_delete_post', static function (int $id, \WP_Post $post): void {
    if ($post->post_type !== 'starter_category') return;
    $term_id = (int) get_post_meta($id, 'category_collection', true);
    category_release_claim('starter_category_owner_' . $term_id, $id);
}, 10, 2);

add_filter('pre_insert_term', static function ($name, string $taxonomy, array $args) {
    if ($taxonomy !== 'product_collection') return $name;
    $valid = category_validate_parent((int) ($args['parent'] ?? 0));
    return is_wp_error($valid) ? $valid : $name;
}, 10, 3);
// Run before Core silently turns an invalid cycle into a root node.
add_filter('wp_update_term_parent', static function ($parent, int $id, string $taxonomy) {
    if ($taxonomy !== 'product_collection') return $parent;
    $valid = category_validate_parent((int) $parent, $id);
    if (is_wp_error($valid)) wp_die(esc_html($valid->get_error_message()), 'Invalid category structure', ['response' => 400]);
    return max(0, (int) $parent);
}, 1, 3);

add_action('rest_api_init', static function (): void {
    register_rest_route('starter/v1', '/product-categories/(?P<id>\d+)/page', [
        'methods' => 'POST', 'permission_callback' => static fn() => current_user_can('manage_categories') && current_user_can('edit_posts'),
        'callback' => static function (\WP_REST_Request $request) {
            $id = category_ensure_page((int) $request['id']);
            return is_wp_error($id) ? $id : rest_ensure_response(['id' => $id, 'status' => get_post_status($id), 'edit_url' => get_edit_post_link($id, 'raw')]);
        },
    ]);
});
add_filter('rest_pre_insert_starter_category', static function ($post, \WP_REST_Request $request) {
    if (is_wp_error($post)) return $post;
    $id = (int) $request['id'];
    if (!$id) return new \WP_Error('starter_category_create_route', 'Create a category page from its category, using /starter/v1/product-categories/{id}/page.', ['status' => 400]);
    $acf = $request->get_param('acf'); $meta = $request->get_param('meta');
    $value = is_array($acf) && array_key_exists('category_collection', $acf) ? $acf['category_collection'] : (is_array($meta) && array_key_exists('category_collection', $meta) ? $meta['category_collection'] : get_post_meta($id, 'category_collection', true));
    $valid = category_validate_link($id, (int) $value);
    return is_wp_error($valid) ? $valid : $post;
}, 10, 2);
// The installed Core term controller does not propagate WP_Error from its
// prepare-item filter. Reject before its callback, using the REST server contract.
add_filter('rest_request_before_callbacks', static function ($response, array $handler, \WP_REST_Request $request) {
    if ($response !== null || !in_array($request->get_method(), ['POST', 'PUT', 'PATCH'], true)
        || !preg_match('~^/wp/v2/product-categories(?:/\d+)?$~', $request->get_route()) || !$request->has_param('parent')) return $response;
    $valid = category_validate_parent((int) $request['parent'], (int) $request['id']);
    return is_wp_error($valid) ? $valid : $response;
}, 10, 3);

// A deleted classification cannot leave a marketing page at a former public URL.
add_action('delete_product_collection', static function (int $term_id): void {
    foreach (category_pages_for_term($term_id) as $id) {
        if (in_array(get_post_status($id), ['publish', 'future', 'private'], true)) wp_update_post(['ID' => $id, 'post_status' => 'draft']);
    }
}, 10);

add_action('template_redirect', static function (): void {
    if (!is_singular('starter_category')) return;
    $id = get_queried_object_id();
    $term = get_term((int) get_post_meta($id, 'category_collection', true), 'product_collection');
    $owners = $term instanceof \WP_Term ? category_pages_for_term((int) $term->term_id) : [];
    if (count($owners) !== 1 || $owners[0] !== $id) {
        $GLOBALS['wp_query']->set_404(); status_header(404); nocache_headers();
    }
}, 5);
add_filter('rank_math/sitemap/exclude_taxonomy', static fn(bool $exclude, string $taxonomy): bool => $taxonomy === 'product_collection' || $exclude, 10, 2);

/** Do not expose draft/trashed page links through native or custom menu items. */
add_filter('wp_setup_nav_menu_item', static function ($item) {
    if (is_admin()) return $item;
    $term_id = 0;
    if ($item->type === 'taxonomy' && $item->object === 'product_collection') $term_id = (int) $item->object_id;
    elseif ($item->type === 'post_type' && $item->object === 'starter_category') $term_id = (int) get_post_meta((int) $item->object_id, 'category_collection', true);
    elseif (is_string($item->url) && (str_starts_with($item->url, home_url('/')) || !wp_parse_url($item->url, PHP_URL_HOST))
        && preg_match('~^/(?:product-category/)?([^/]+)/?$~', (string) wp_parse_url($item->url, PHP_URL_PATH), $match)) {
        $page = get_page_by_path(rawurldecode($match[1]), OBJECT, ['starter_category']);
        if ($page) $term_id = (int) get_post_meta($page->ID, 'category_collection', true);
        else {
            $term = get_term_by('slug', rawurldecode($match[1]), 'product_collection');
            if ($term instanceof \WP_Term) $term_id = (int) $term->term_id;
        }
    }
    if (($term_id && !category_public_url($term_id)) || is_wp_error($item->url)) { $item->_invalid = true; $item->url = ''; }
    elseif ($term_id) {
        $query = $item->type === 'custom' ? wp_parse_url($item->url, PHP_URL_QUERY) : null;
        $fragment = $item->type === 'custom' ? wp_parse_url($item->url, PHP_URL_FRAGMENT) : null;
        $item->url = category_public_url($term_id) . ($query ? '?' . $query : '') . ($fragment ? '#' . $fragment : '');
    }
    return $item;
});

if (is_admin()) require_once __DIR__ . '/category-admin.php';
