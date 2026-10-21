<?php
namespace Starter\Theme;
defined('ABSPATH') || exit;

function category_current_term(): ?\WP_Term {
    $object = get_queried_object();
    if ($object instanceof \WP_Term) return $object;
    if (!$object instanceof \WP_Post || $object->post_type !== 'starter_category') return null;
    // Identity belongs to the canonical page, not a partial ACF autosave snapshot.
    // Mutable editorial fields still use ACF's native preview/revision behavior.
    $owner = wp_is_post_revision($object->ID) ?: $object->ID;
    $id = (int) get_post_meta($owner, 'category_collection', true);
    $term = $id ? get_term($id, 'product_collection') : null;
    return $term instanceof \WP_Term ? $term : null;
}

/** The page is the sole owner after migration. Saved empties never fall back to term metadata. */
function category_content_object(int $term_id) {
    if (is_singular('starter_category')) {
        $current = category_current_term();
        if ($current && $current->term_id === $term_id) return get_queried_object_id();
    }
    $id = \Starter\Model\category_page_for_term($term_id);
    return $id ?: 'product_collection_' . $term_id;
}

function category_meta_exists(int $term_id, string $name): bool {
    $object = category_content_object($term_id);
    return metadata_exists(is_int($object) ? 'post' : 'term', is_int($object) ? $object : $term_id, $name);
}

function category_product_query(): \WP_Query {
    if (!is_singular('starter_category')) return $GLOBALS['wp_query'];
    $term = category_current_term();
    $args = [
        'post_type' => 'starter_product', 'post_status' => 'publish',
        'posts_per_page' => max(1, (int) get_option('posts_per_page')), 'paged' => max(1, (int) get_query_var('paged')),
        'tax_query' => [['taxonomy' => 'product_collection', 'field' => 'term_id', 'terms' => $term ? [$term->term_id] : [0], 'include_children' => true]],
    ];
    $filters = equipment_active_filters();
    if ($filters) {
        $all = get_posts(array_merge($args, ['posts_per_page' => -1, 'paged' => 1, 'fields' => 'ids']));
        $args['post__in'] = array_values(array_filter($all, static fn ($id): bool => equipment_product_matches((int) $id, $filters))) ?: [0];
    }
    return new \WP_Query($args);
}

// The singular landing page owns the URL, but its product query owns the page count.
add_filter('pre_handle_404', static function ($preempt, \WP_Query $query) {
    if ($preempt !== false || is_admin() || !$query->is_singular('starter_category')) return $preempt;
    $page = (int) $query->get('paged');
    if ($page < 2) return $preempt;
    $id = (int) $query->get_queried_object_id();
    if (metadata_exists('post', $id, 'category_show_products') && get_post_meta($id, 'category_show_products', true) === '0') return $preempt;
    $products = category_product_query();
    if ($page <= max(1, (int) $products->max_num_pages)) return $preempt;
    $query->set_404();
    status_header(404);
    nocache_headers();
    return true;
}, 10, 2);

// Core's singular canonical redirect would otherwise discard a valid product-list page.
add_filter('redirect_canonical', static function ($redirect) {
    if (get_query_var('starter_category') && (int) get_query_var('paged') > 1) return false;
    return $redirect;
});

add_filter('rank_math/frontend/canonical', static function ($canonical) {
    if (!is_string($canonical) || $canonical === '' || !is_singular('starter_category') || !is_paged()) return $canonical;
    if (get_post_meta(get_queried_object_id(), 'rank_math_canonical', true) !== '') return $canonical;
    return get_pagenum_link(max(1, (int) get_query_var('paged')), false);
});

add_filter('rank_math/frontend/title', static function ($title) {
    if (!is_string($title) || !is_singular('starter_category') || !is_paged()) return $title;
    $page = max(1, (int) get_query_var('paged'));
    if (preg_match('/\bpage\s+' . $page . '\b/i', $title)) return $title;
    return $title . ' — ' . sprintf(__('Page %d', 'b2b-starter'), $page);
});

function render_category_page(string $layout = 'standard'): void {
    if (post_password_required(get_queried_object_id())) {
        get_header(); echo '<main id="main" class="shell section">' . get_the_password_form(get_queried_object_id()) . '</main>'; get_footer(); return;
    }
    if (!category_current_term()) {
        get_header();
        echo '<main id="main" class="shell section"><h1>' . esc_html(get_the_title(get_queried_object_id())) . '</h1><p>' . esc_html__('Select a product classification in this page’s editor.', 'b2b-starter') . '</p></main>';
        get_footer();
        return;
    }
    render_category_archive($layout);
}
