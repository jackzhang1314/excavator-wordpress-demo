<?php
/** Enhance Core's category list instead of adding another tree or editor. */
namespace Starter\Model;
defined('ABSPATH') || exit;

add_action('admin_enqueue_scripts', static function (): void {
    $screen = get_current_screen();
    if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'starter_category') return;
    $file = dirname(__DIR__) . '/assets/js/category-template-guard.js';
    wp_enqueue_script('starter-category-template-guard', plugins_url('assets/js/category-template-guard.js', dirname(__DIR__) . '/starter-model.php'), ['acf-input'], (string) filemtime($file), true);
});

function category_admin_url(array $args = []): string {
    return add_query_arg($args, admin_url('edit-tags.php?taxonomy=product_collection&post_type=starter_product'));
}
function category_page_action_url(int $term_id): string {
    return wp_nonce_url(add_query_arg(['action' => 'starter_category_page', 'term_id' => $term_id], admin_url('admin-post.php')), 'starter_category_page_' . $term_id);
}
function category_template_label(int $id): string {
    $template = get_page_template_slug($id);
    return $template ? (wp_get_theme()->get_page_templates(get_post($id))[$template] ?? 'Unavailable template: ' . $template) : 'Category: Overview';
}

add_filter('manage_edit-product_collection_columns', static function (array $columns): array {
    return ['cb' => $columns['cb'], 'name' => $columns['name'], 'category_path' => 'Category path / level',
        'category_page' => 'Content page', 'category_template' => 'Page template', 'posts' => 'Direct products', 'category_total' => 'Including descendants'];
});
add_filter('manage_product_collection_custom_column', static function (string $content, string $column, int $term_id): string {
    $term = get_term($term_id, 'product_collection');
    if (!$term instanceof \WP_Term) return $content;
    $context = category_tree_context($term);
    $ids = category_pages_for_term($term_id);
    if ($column === 'category_path') return esc_html($context['path']) . '<br><strong>Level ' . (int) $context['depth'] . '</strong>';
    if ($column === 'category_page') {
        if (!$ids) return '<a href="' . esc_url(category_page_action_url($term_id)) . '">Create content draft</a>';
        if (count($ids) > 1) return '<strong>Conflict</strong>: ' . esc_html(implode(', ', $ids));
        $status = get_post_status_object(get_post_status($ids[0]));
        return esc_html($status ? $status->label : 'Unknown') . ' · #' . $ids[0];
    }
    if ($column === 'category_template') return count($ids) === 1 ? esc_html(category_template_label($ids[0])) : '—';
    if ($column === 'category_total') {
        $query = new \WP_Query(['post_type' => 'starter_product', 'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => 1,
            'tax_query' => [['taxonomy' => 'product_collection', 'terms' => [$term_id], 'include_children' => true]]]);
        return (string) $query->found_posts;
    }
    return $content;
}, 10, 3);
add_filter('product_collection_row_actions', static function (array $actions, \WP_Term $term): array {
    $ids = category_pages_for_term((int) $term->term_id);
    if (!category_public_url((int) $term->term_id)) unset($actions['view']);
    if (isset($actions['edit'])) $actions['edit'] = '<a href="' . esc_url(get_edit_term_link($term->term_id, 'product_collection', 'starter_product')) . '">Edit structure</a>';
    unset($actions['inline hide-if-no-js']); // Moving structure is an explicit native edit, with its notice visible.
    if (count($ids) === 1 && get_post_status($ids[0]) === 'trash') {
        $actions = ['restore_content' => '<a href="' . esc_url(wp_nonce_url(admin_url('post.php?post=' . $ids[0] . '&action=untrash'), 'untrash-post_' . $ids[0])) . '">Restore content</a>'] + $actions;
    } elseif (count($ids) < 2) {
        $url = $ids ? get_edit_post_link($ids[0], 'raw') : category_page_action_url((int) $term->term_id);
        $actions = ['content' => '<a href="' . esc_url($url) . '">' . ($ids ? 'Edit content' : 'Create content draft') . '</a>'] + $actions;
    }
    $actions['add_child'] = '<a href="' . esc_url(category_admin_url(['starter_parent' => $term->term_id])) . '">Add subcategory</a>';
    return $actions;
}, 10, 2);

add_action('admin_post_starter_category_page', static function (): void {
    $term_id = isset($_GET['term_id']) ? absint($_GET['term_id']) : 0;
    check_admin_referer('starter_category_page_' . $term_id);
    $id = category_ensure_page($term_id);
    if (is_wp_error($id)) wp_die(esc_html($id->get_error_message()), 'Category content', ['response' => (int) ($id->get_error_data()['status'] ?? 400), 'back_link' => true]);
    if (get_post_status($id) === 'trash') {
        wp_safe_redirect(admin_url('edit.php?post_type=starter_category&post_status=trash')); exit;
    }
    wp_safe_redirect(get_edit_post_link($id, 'raw')); exit;
});

// Core validates its term nonce before this hook. A failed second step leaves a resumable row action.
add_action('created_product_collection', static function (int $term_id): void {
    $action = isset($_REQUEST['action']) && is_scalar($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
    if (!in_array($action, ['add-tag', 'addtag'], true)) return;
    $result = category_ensure_page($term_id);
    if (is_wp_error($result)) set_transient('starter_category_create_error_' . get_current_user_id(), $result->get_error_message(), 300);
});

add_action('product_collection_add_form_fields', static function (): void {
    $parent = isset($_GET['starter_parent']) ? absint($_GET['starter_parent']) : 0;
    $term = $parent ? get_term($parent, 'product_collection') : null;
    $label = $term instanceof \WP_Term ? category_tree_context($term)['path'] : 'None — top-level category';
    echo '<div class="notice notice-info inline"><p><strong>Category structure saves immediately.</strong> Content starts as a separate draft. Saving its draft will not move or rename this classification.</p><p>New parent: <strong>' . esc_html($label) . '</strong></p></div>';
});
add_action('product_collection_edit_form_fields', static function (\WP_Term $term): void {
    $context = category_tree_context($term);
    echo '<tr><th>Structure changes</th><td><p><strong>' . esc_html($context['path']) . '</strong> · Level ' . (int) $context['depth'] . '</p><p>Updating this structure takes effect immediately for all descendants, product ranges and breadcrumbs. Content, media and the selected page template stay with their existing page. Public URLs use stable page slugs.</p></td></tr>';
});
add_action('admin_notices', static function (): void {
    $screen = get_current_screen();
    if (!$screen || ($screen->taxonomy !== 'product_collection' && $screen->post_type !== 'starter_category')) return;
    echo '<div class="notice notice-info"><p><a href="' . esc_url(category_admin_url(['starter_parent' => 0])) . '"><strong>Add top-level category</strong></a> · <a href="' . esc_url(category_admin_url()) . '">Manage category structure and content</a>. Existing content and templates are preserved when a category changes level.</p></div>';
    $error = get_transient('starter_category_create_error_' . get_current_user_id());
    if ($error) { echo '<div class="notice notice-error"><p>' . esc_html($error) . ' Use the category row’s Create content draft action to resume.</p></div>'; delete_transient('starter_category_create_error_' . get_current_user_id()); }
});
add_action('admin_init', static function (): void {
    global $pagenow;
    if ($pagenow === 'post-new.php' && ($_GET['post_type'] ?? '') === 'starter_category') {
        wp_safe_redirect(category_admin_url()); exit;
    }
});

add_filter('views_edit-product_collection', static function (array $views): array {
    foreach (['' => 'All levels', '1' => 'Level 1', '2' => 'Level 2', '3' => 'Level 3+'] as $depth => $label) {
        $views['level_' . $depth] = '<a href="' . esc_url(category_admin_url($depth ? ['starter_depth' => $depth, 'orderby' => 'name'] : [])) . '">' . esc_html($label) . '</a>';
    }
    foreach (['unpublished' => 'Unpublished content', 'missing' => 'Missing page', 'trash' => 'Trashed content', 'conflict' => 'Conflicts'] as $state => $label) {
        $views['state_' . $state] = '<a href="' . esc_url(category_admin_url(['starter_page_state' => $state, 'orderby' => 'name'])) . '">' . esc_html($label) . '</a>';
    }
    return $views;
});
add_action('load-edit-tags.php', static function (): void {
    if (get_current_screen()->taxonomy === 'product_collection' && (!empty($_GET['starter_depth']) || !empty($_GET['starter_page_state'])) && empty($_REQUEST['orderby'])) {
        // Core's tree walker cannot display a filtered child without its parent.
        // Filtered lists use Core's flat ordering; the path column retains the whole hierarchy.
        $_REQUEST['orderby'] = 'name'; $_GET['orderby'] = 'name';
    }
});
add_action('pre_get_terms', static function (\WP_Term_Query $query): void {
    static $busy = false;
    if (!function_exists('get_current_screen')) return;
    $screen = get_current_screen();
    $depth = isset($_GET['starter_depth']) ? absint($_GET['starter_depth']) : 0;
    $state = isset($_GET['starter_page_state']) && is_string($_GET['starter_page_state']) ? $_GET['starter_page_state'] : '';
    if ($busy || !$screen || $screen->id !== 'edit-product_collection' || (!$depth && !$state) || $query->query_vars['taxonomy'] !== ['product_collection']) return;
    // Do not filter the parent dropdown, direct-child queries or other unrelated reads.
    if (!array_key_exists('page', $query->query_vars) && $query->query_vars['fields'] !== 'count') return;
    $busy = true;
    try {
        $terms = get_terms(['taxonomy' => 'product_collection', 'hide_empty' => false]); $ids = [];
        foreach (is_array($terms) ? $terms : [] as $term) {
            $level = count(get_ancestors($term->term_id, 'product_collection', 'taxonomy')) + 1;
            if ($depth && !($depth === 3 ? $level >= 3 : $level === $depth)) continue;
            $pages = category_pages_for_term((int) $term->term_id);
            $status = count($pages) === 1 ? get_post_status($pages[0]) : '';
            if ($state === 'missing' && $pages) continue;
            if ($state === 'conflict' && count($pages) < 2) continue;
            if ($state === 'trash' && $status !== 'trash') continue;
            if ($state === 'unpublished' && !in_array($status, ['draft', 'pending', 'future', 'private'], true)) continue;
            $ids[] = $term->term_id;
        }
        $query->query_vars['include'] = $ids ?: [0];
    } finally { $busy = false; }
});

add_filter('taxonomy_labels_product_collection', static function ($labels) {
    if (($_REQUEST['taxonomy'] ?? '') === 'product_collection') $labels->add_new_item = 'Create category & content draft';
    $labels->desc_field_description = 'Short classification note only. Edit the full description, images and buying guide in the associated content page.';
    return $labels;
});
// The parent must be present in the initial HTML, including a fast submit or no JavaScript.
add_filter('taxonomy_parent_dropdown_args', static function (array $args, string $taxonomy, string $context): array {
    if ($taxonomy !== 'product_collection' || $context !== 'new' || !isset($_GET['starter_parent'])) return $args;
    $parent = absint($_GET['starter_parent']);
    $args['selected'] = $parent && get_term($parent, 'product_collection') instanceof \WP_Term ? $parent : -1;
    return $args;
}, 10, 3);

add_action('add_meta_boxes_starter_category', static function (\WP_Post $post): void {
    if (get_term((int) get_post_meta($post->ID, 'category_collection', true), 'product_collection') instanceof \WP_Term) {
        remove_meta_box('acf-group_category_classification', 'starter_category', 'side');
    }
    add_meta_box('starter-category-context', 'Category identity and structure', static function (\WP_Post $post): void {
        $term = get_term((int) get_post_meta($post->ID, 'category_collection', true), 'product_collection');
        if (!$term instanceof \WP_Term) { echo '<p>Classification missing. Return to category management to resolve this page before publishing.</p>'; return; }
        $context = category_tree_context($term);
        echo '<p><strong>' . esc_html($context['path']) . '</strong></p><p>Level ' . (int) $context['depth'] . ' · Saved template: ' . esc_html(category_template_label($post->ID)) . '</p><p>Product range: this category and all descendants. The page template is independent of its level.</p><p><a href="' . esc_url(get_edit_term_link($term->term_id, 'product_collection', 'starter_product')) . '">Edit structure (takes effect immediately)</a> · <a href="' . esc_url(category_admin_url()) . '">Return to category management</a></p>';
    }, 'starter_category', 'normal', 'high');
});
add_filter('acf/prepare_field/key=field_category_collection', static function ($field) {
    $id = isset($_GET['post']) ? absint($_GET['post']) : 0;
    return $id && get_term((int) get_post_meta($id, 'category_collection', true), 'product_collection') instanceof \WP_Term ? false : $field;
});
// ACF's native screen AJAX has post_id in its location context, not in GET['post'].
add_filter('acf/location/rule_match/post_type', static function (bool $match, array $rule, array $screen, array $group): bool {
    if (!$match || ($group['key'] ?? '') !== 'group_category_classification') return $match;
    $id = (int) ($screen['post_id'] ?? 0);
    return !$id || !get_term((int) get_post_meta($id, 'category_collection', true), 'product_collection') instanceof \WP_Term;
}, 10, 4);
