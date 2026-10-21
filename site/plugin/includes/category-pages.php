<?php
namespace Starter\Model;
defined('ABSPATH') || exit;

function register_category_page_type(): void {
    register_post_type('starter_category', [
        'labels' => content_labels('Product category page', 'Product Category Pages'),
        'public' => true, 'show_in_rest' => true, 'rest_base' => 'product-category-pages',
        'has_archive' => false, 'rewrite' => false,
        'show_in_menu' => 'edit.php?post_type=starter_product',
        'menu_icon' => 'dashicons-category', 'menu_position' => 21,
        'map_meta_cap' => true, 'capability_type' => 'post',
        'supports' => ['title', 'editor', 'revisions', 'custom-fields'],
    ]);
    // Historical paths are retained for exact 301 migration, never as a second canonical page.
    add_rewrite_rule('^product-category/([^/]+)/page/([0-9]+)/?$', 'index.php?starter_category=$matches[1]&paged=$matches[2]', 'top');
    add_rewrite_rule('^product-category/([^/]+)/?$', 'index.php?starter_category=$matches[1]', 'top');
}

/** Query only the small set of pages linked to this classification; no duplicate editable term content. */
function category_page_for_term(int $term_id, bool $published_only = true): int {
    $ids = category_pages_for_term($term_id);
    if (count($ids) !== 1 || ($published_only && get_post_status($ids[0]) !== 'publish')) return 0;
    return $ids[0];
}

// Resolve a historical slug to its classification; the public guard below requires a published content page.
add_filter('request', static function (array $vars): array {
    $slug = (string) ($vars['starter_category'] ?? '');
    if ($slug === '' || get_page_by_path($slug, OBJECT, ['starter_category'])) return $vars;
    $term = get_term_by('slug', $slug, 'product_collection');
    if (!$term instanceof \WP_Term || category_page_for_term((int) $term->term_id, false)) return $vars;
    unset($vars['starter_category']);
    $vars['product_collection'] = $slug;
    return $vars;
});

add_filter('term_link', static function ($url, \WP_Term $term, string $taxonomy) {
    if ($taxonomy !== 'product_collection') return $url;
    $public = category_public_url((int) $term->term_id);
    return $public ?: (is_admin() ? $url : new \WP_Error('starter_category_not_public', 'This category has no public content page.'));
}, 10, 3);

// Legacy taxonomy query URLs canonicalize to the content page, including secondary listing pages.
add_action('template_redirect', static function (): void {
    if (!is_tax('product_collection')) return;
    $term = get_queried_object();
    if (!$term instanceof \WP_Term) return;
    $id = category_page_for_term((int) $term->term_id, false);
    if ($id && category_public_url((int) $term->term_id)) {
        $url = (string) get_permalink($id);
        $number = (int) get_query_var('paged');
        if ($number > 1) $url = trailingslashit($url) . 'page/' . $number . '/';
        wp_safe_redirect($url, 301); exit;
    }
    $GLOBALS['wp_query']->set_404(); status_header(404);
});

add_action('template_redirect', static function (): void {
    if (!is_singular('starter_category') || !is_paged()) return;
    $id = get_queried_object_id();
    if (metadata_exists('post', $id, 'category_show_products') && get_post_meta($id, 'category_show_products', true) === '0') {
        wp_safe_redirect(get_permalink($id), 302); exit;
    }
});

/** Each actual Core template owns one complete editor; canonical fields keep their storage names. */
function register_category_editors(array $library, callable $field): void {
    acf_add_local_field_group($library);
    acf_add_local_field_group([
        'key' => 'group_category_page_standard', 'title' => 'Category overview content', 'position' => 'acf_after_title',
        'label_placement' => 'top', 'instruction_placement' => 'label', 'show_in_rest' => true,
        'fields' => [$field('field_category_standard_editor', 'category_standard_editor', '', 'clone', [
            'clone' => [
                'field_tc_tab_hero', 'field_tc_intro', 'field_tc_hero_image', 'field_tc_card_image', 'field_tc_card_description', 'field_tc_show_products',
                'field_tc_tab_sections', 'field_tc_content_sections',
                'field_tc_tab_guide', 'field_tc_guide_title', 'field_tc_long_description',
                'field_tc_tab_selection', 'field_tc_spec_items', 'field_tc_attribute_tables', 'field_tc_faq_items',
                'field_tc_tab_editorial', 'field_tc_cta_title', 'field_tc_cta_text', 'field_tc_cta_button',
            ],
            'display' => 'seamless', 'prefix_name' => 0, 'prefix_label' => 0,
        ])],
        'location' => [[
            ['param' => 'post_type', 'operator' => '==', 'value' => 'starter_category'],
            ['param' => 'post_template', 'operator' => '==', 'value' => 'default'],
        ]],
    ]);
    $overview = acf_get_local_field_group('group_category_page_standard');
    $overview['key'] = 'group_category_page_selection';
    $overview['title'] = 'Product selection content';
    // Register the complete clone explicitly; do not copy an already registered field key.
    $overview['fields'] = [$field('field_category_selection_editor', 'category_selection_editor', '', 'clone', [
        'clone' => ['field_tc_tab_hero', 'field_tc_intro', 'field_tc_hero_image', 'field_tc_card_image', 'field_tc_card_description', 'field_tc_show_products',
            'field_tc_tab_sections', 'field_tc_content_sections', 'field_tc_tab_guide', 'field_tc_guide_title', 'field_tc_long_description',
            'field_tc_tab_selection', 'field_tc_spec_items', 'field_tc_attribute_tables', 'field_tc_faq_items',
            'field_tc_tab_editorial', 'field_tc_cta_title', 'field_tc_cta_text', 'field_tc_cta_button'],
        'display' => 'seamless', 'prefix_name' => 0, 'prefix_label' => 0,
    ])];
    $overview['location'][0][1]['value'] = 'page-templates/category-selection.php';
    acf_add_local_field_group($overview);
    acf_add_local_field_group([
        'key' => 'group_category_classification', 'title' => 'Product classification', 'position' => 'side',
        'fields' => [$field('field_category_collection', 'category_collection', 'Product category', 'taxonomy', [
            'taxonomy' => 'product_collection', 'field_type' => 'select', 'return_format' => 'id',
            'add_term' => 0, 'save_terms' => 0, 'load_terms' => 0, 'allow_null' => 1, 'required' => 1,
            'instructions' => 'Select the classification whose published products this page lists. Includes child categories. One page per classification.',
        ])],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'starter_category']]],
    ]);
}

add_filter('acf/validate_value/key=field_category_collection', static function ($valid, $value) {
    if ($valid !== true || !$value) return $valid;
    $term = get_term((int) $value, 'product_collection');
    if (!$term instanceof \WP_Term) return __('Choose an existing product classification.', 'b2b-starter');
    // The native AJAX validation request precedes acf/save_post form initialization.
    $post_id = (int) acf_get_form_data('post_id');
    if (!$post_id && isset($_POST['_acf_post_id']) && is_scalar($_POST['_acf_post_id'])) {
        $post_id = absint(wp_unslash((string) $_POST['_acf_post_id']));
    }
    $result = category_validate_link($post_id, (int) $value);
    return is_wp_error($result) ? $result->get_error_message() : $valid;
}, 10, 2);

add_action('product_collection_edit_form', static function (\WP_Term $term): void {
    $id = category_page_for_term((int) $term->term_id, false);
    if (!$id) return;
    printf('<p>%s <a href="%s">%s</a></p>', esc_html__('This screen manages classification only.', 'b2b-starter'), esc_url(get_edit_post_link($id, 'raw')), esc_html__('Edit category page content', 'b2b-starter'));
});

/** Explicit retirement only: preserve saved content and archive the removed native assignment. */
function retire_category_page_template(int $id): void {
    if (get_post_type($id) !== 'starter_category') return;
    $previous = get_post_meta($id, '_wp_page_template', true);
    if (!in_array($previous, [
        'page-templates/category-catalogue.php', 'page-templates/category-conversion.php', 'page-templates/category-editorial.php',
    ], true)) return;
    update_post_meta($id, '_starter_retired_category_template', $previous);
    if (get_post_meta($id, '_starter_retired_category_template', true) === $previous) update_post_meta($id, '_wp_page_template', 'default');
}

/** Explicit, additive migration. Caller backs up term metadata; no automatic activation-time database writes. */
function migrate_category_page(int $term_id): int {
    if (!function_exists('acf_add_local_field_group')) throw new \RuntimeException('ACF must be active before migration.');
    $term = get_term($term_id, 'product_collection');
    if (!$term instanceof \WP_Term) throw new \RuntimeException('Unknown product classification.');
    $existing = category_page_for_term($term_id, false);
    if ($existing) {
        retire_category_page_template($existing);
        return $existing;
    }
    if (get_page_by_path($term->slug, OBJECT, ['starter_category'])) throw new \RuntimeException('Category page slug already owned by another classification.');
    $id = wp_insert_post(['post_type' => 'starter_category', 'post_title' => $term->name, 'post_name' => $term->slug, 'post_status' => 'draft'], true);
    if (is_wp_error($id)) throw new \RuntimeException($id->get_error_message());
    foreach (get_term_meta($term_id) as $key => $values) {
        if (!str_starts_with(ltrim($key, '_'), 'category_') && !str_starts_with($key, 'rank_math_')) continue;
        foreach ($values as $value) add_post_meta($id, $key, maybe_unserialize($value));
    }
    update_field('field_category_collection', $term_id, $id);
    update_post_meta($id, '_wp_page_template', 'default');
    update_post_meta($id, '_starter_category_migrated_term', $term_id);
    $published = wp_update_post(['ID' => $id, 'post_status' => 'publish'], true);
    if (is_wp_error($published)) throw new \RuntimeException($published->get_error_message());
    return (int) $id;
}
