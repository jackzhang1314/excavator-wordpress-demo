<?php
/** Data contract for the customer's selected Home. No demonstration defaults. */
namespace Starter\Theme;
defined('ABSPATH') || exit;

function home_v2_value(string $name, int $post_id) {
    return function_exists('get_field') ? get_field($name, $post_id, true, true) : null;
}
function home_v2_image($value): int {
    $id = is_array($value) ? (int) ($value['ID'] ?? 0) : (int) $value;
    return $id > 0 && wp_attachment_is_image($id) ? $id : 0;
}
function home_v2_link($value): array {
    if (!is_array($value)) return [];
    $url = esc_url_raw(trim((string) ($value['url'] ?? '')));
    $title = trim((string) ($value['title'] ?? ''));
    return $url !== '' && $title !== '' ? ['url' => $url, 'title' => $title, 'target' => ($value['target'] ?? '') === '_blank' ? '_blank' : ''] : [];
}
function home_v2_rows(string $name, int $id): array {
    $rows = home_v2_value($name, $id);
    return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
}
function home_v2_text_link(array $link, string $class = ''): void {
    if (!$link) return;
    printf('<a class="%s" href="%s"%s>%s <span aria-hidden="true">→</span></a>',
        esc_attr($class), esc_url($link['url']),
        $link['target'] === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '',
        esc_html($link['title']));
}

/** Curated category relations and product IDs remain the canonical data owners. */
function home_v2_data(int $id = 0): array {
    $id = $id ?: get_the_ID();
    $text = static fn (string $name): string => trim((string) home_v2_value($name, $id));
    $ranges = [];
    foreach ((array) home_v2_value('home_ranges', $id) as $term_id) {
        $term = get_term((int) $term_id, 'product_collection');
        if (!$term instanceof \WP_Term || $term->parent !== 0) continue;
        $page_id = \Starter\Model\category_page_for_term($term->term_id);
        if (!$page_id || get_post_status($page_id) !== 'publish') continue;
        $image = home_v2_image(get_field('category_card_image', $page_id));
        if (!$image) $image = home_v2_image(get_field('category_hero_image', $page_id));
        $ranges[] = ['title' => $term->name, 'url' => get_permalink($page_id), 'image' => $image];
    }
    $applications = [];
    foreach (home_v2_rows('home_application_items', $id) as $row) {
        $title = trim((string) ($row['title'] ?? ''));
        $body = trim((string) ($row['text'] ?? ''));
        $image = home_v2_image($row['image'] ?? 0);
        $link = home_v2_link($row['link'] ?? []);
        if ($title === '' || (!$image && $body === '')) continue;
        $applications[] = ['title' => $title, 'text' => $body, 'image' => $image, 'link' => $link];
    }
    $products = [];
    foreach (array_unique(array_map('intval', (array) home_v2_value('home_featured_products', $id))) as $product_id) {
        $post = get_post($product_id);
        if (!$post || $post->post_type !== 'starter_product' || $post->post_status !== 'publish') continue;
        $raw_specs = get_field('product_quick_specs', $product_id);
        $specs = [];
        foreach (is_array($raw_specs) ? $raw_specs : [] as $row) {
            if (!is_array($row)) continue;
            $label = trim((string) ($row['quick_label'] ?? ''));
            $value = trim((string) ($row['quick_value'] ?? ''));
            if ($label !== '' && $value !== '') $specs[] = ['label' => $label, 'value' => $value];
        }
        $terms = get_the_terms($product_id, 'product_collection');
        $family_term = is_array($terms) && $terms ? $terms[0] : null;
        if ($family_term instanceof \WP_Term && $family_term->parent) {
            $ancestors = get_ancestors($family_term->term_id, 'product_collection', 'taxonomy');
            $root = $ancestors ? get_term((int) end($ancestors), 'product_collection') : null;
            if ($root instanceof \WP_Term) $family_term = $root;
        }
        $family = $family_term instanceof \WP_Term ? $family_term->name : '';
        $products[] = ['id' => $product_id, 'title' => get_the_title($product_id), 'url' => get_permalink($product_id), 'family' => $family, 'specs' => array_slice($specs, 0, 3)];
    }
    $links = [];
    foreach (home_v2_rows('home_delivery_links', $id) as $row) {
        $link = home_v2_link($row['link'] ?? []);
        if ($link) $links[] = ['link' => $link, 'text' => trim((string) ($row['text'] ?? ''))];
    }
    $body = $text('home_delivery_body');
    $supply = [
        'eyebrow' => $text('home_delivery_eyebrow'), 'title' => $text('home_delivery_title'),
        'body' => $body, 'image' => home_v2_image(home_v2_value('home_delivery_image', $id)),
        'links' => $links, 'enabled' => has_rich_content($body) || (bool) $links,
    ];
    $cta = ['eyebrow' => $text('home_cta_eyebrow'), 'title' => $text('home_cta_title'), 'text' => $text('home_cta_text'), 'link' => home_v2_link(home_v2_value('home_cta_link', $id)), 'image' => home_v2_image(home_v2_value('home_cta_image', $id))];
    return [
        'overline' => $text('home_overline'), 'description' => $text('home_description'),
        'primary' => home_v2_link(home_v2_value('home_primary_link', $id)), 'secondary' => home_v2_link(home_v2_value('home_secondary_link', $id)),
        'ranges' => $ranges, 'ranges_title' => $text('applications_title'), 'ranges_intro' => $text('home_ranges_intro'), 'ranges_link' => home_v2_link(home_v2_value('home_ranges_link', $id)),
        'applications' => $applications, 'applications_title' => $text('home_applications_title'), 'applications_intro' => $text('home_applications_intro'),
        'products' => $products, 'products_title' => $text('featured_products_title'), 'products_intro' => $text('home_products_intro'),
        'supply' => $supply, 'cta' => $cta,
    ];
}
