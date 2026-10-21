<?php
/** Data and shared presentation for the selected equipment catalogue design. */
namespace Starter\Theme;
defined('ABSPATH') || exit;

function equipment_category_card(\WP_Term $term): array {
    $id = \Starter\Model\category_page_for_term((int) $term->term_id);
    $url = \Starter\Model\category_public_url((int) $term->term_id);
    if (!$id || !$url) return [];
    return [
        'name' => $term->name, 'url' => $url, 'id' => $id,
        'image' => home_v2_image(get_field('category_card_image', $id)),
        'intro' => trim((string) get_field('category_card_description', $id)),
    ];
}

function equipment_families(int $parent = 0): array {
    $terms = get_terms(['taxonomy' => 'product_collection', 'parent' => $parent, 'hide_empty' => false, 'orderby' => 'term_id']);
    $rows = [];
    foreach (is_array($terms) ? $terms : [] as $term) {
        $row = equipment_category_card($term);
        if ($row) $rows[] = $row;
    }
    return $rows;
}

function equipment_quick_specs(int $id, int $limit = 0): array {
    $rows = normalize_attribute_rows(product_repeater_rows('product_quick_specs', $id, 'quick_label', 'quick_value', 'quick_specs'));
    return $limit > 0 ? array_slice($rows, 0, $limit) : $rows;
}

/** The short catalogue labels and editable specification labels share one key. */
function equipment_spec_key(string $label): string {
    $key = sanitize_title($label);
    return ['weight' => 'operating-weight', 'power' => 'engine-power', 'digging-depth' => 'dig-depth'][$key] ?? $key;
}

/** Read the same product data for filtering; quick specifications take priority. */
function equipment_filter_specs(int $id): array {
    $rows = equipment_quick_specs($id);
    $keys = array_map(static fn (array $row): string => equipment_spec_key($row['label']), $rows);
    foreach (normalize_attribute_rows(product_repeater_rows('product_specifications', $id, 'spec_label', 'spec_value', 'spec_table')) as $row) {
        $key = equipment_spec_key($row['label']);
        if ($key === 'tail-swing' && !in_array($key, $keys, true)) $rows[] = $row;
    }
    return $rows;
}

/** Values are derived only from published products and their canonical quick specs. */
function equipment_category_filters(?\WP_Term $term): array {
    if (!$term) return [];
    $ids = get_posts(['post_type' => 'starter_product', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids',
        'tax_query' => [['taxonomy' => 'product_collection', 'field' => 'term_id', 'terms' => [$term->term_id], 'include_children' => true]]]);
    $facets = [];
    foreach ($ids as $id) foreach (equipment_filter_specs((int) $id) as $row) {
        $key = equipment_spec_key($row['label']);
        if (!isset($facets[$key])) $facets[$key] = ['label' => $row['label'], 'values' => []];
        if (!in_array(strtolower(trim($row['value'])), ['to confirm', 'pending', 'n/a', ''], true)) $facets[$key]['values'][$row['value']] = $row['value'];
    }
    foreach ($facets as &$facet) { natcasesort($facet['values']); $facet['values'] = array_values($facet['values']); }
    unset($facet);
    if ($term->parent > 0) {
        $ancestors = get_ancestors($term->term_id, 'product_collection', 'taxonomy');
        $family = $ancestors ? get_term((int) end($ancestors), 'product_collection') : null;
        if ($family instanceof \WP_Term && in_array($family->slug, ['construction-digger', 'general-excavator'], true)) {
            $selected = [];
            foreach (['operating-weight' => 'Weight', 'engine-power' => 'Power', 'dig-depth' => 'Dig depth', 'tail-swing' => 'Tail swing'] as $key => $label) {
                $selected[$key] = ['label' => $label, 'values' => $facets[$key]['values'] ?? []];
            }
            return $selected;
        }
    }
    return $facets;
}

function equipment_active_filters(): array {
    $input = isset($_GET['eq_spec']) && is_array($_GET['eq_spec']) ? wp_unslash($_GET['eq_spec']) : [];
    $result = [];
    foreach ($input as $key => $value) if (is_string($value) && $value !== '') $result[equipment_spec_key((string) $key)] = sanitize_text_field($value);
    return $result;
}

/** Filter invalid/partial media cards while preserving editor order. */
function equipment_media_cards(string $field, int $id): array {
    $raw = get_field($field, $id);
    $result = [];
    foreach (is_array($raw) ? $raw : [] as $row) {
        if (!is_array($row)) continue;
        $title = trim((string) ($row['title'] ?? ''));
        $description = trim((string) ($row['description'] ?? ''));
        $image = home_v2_image($row['image'] ?? 0);
        if ($title === '' || (!$image && $description === '')) continue;
        $result[] = ['title' => $title, 'description' => $description, 'image' => $image, 'icon' => sanitize_key((string) ($row['icon'] ?? '')), 'link' => home_v2_link($row['link'] ?? [])];
    }
    return $result;
}

function equipment_family_for_product(int $id): ?\WP_Term {
    $terms = get_the_terms($id, 'product_collection');
    $term = is_array($terms) && $terms ? $terms[0] : null;
    if (!$term instanceof \WP_Term) return null;
    $ancestors = get_ancestors($term->term_id, 'product_collection', 'taxonomy');
    if ($ancestors) {
        $parent = get_term((int) end($ancestors), 'product_collection');
        if ($parent instanceof \WP_Term) $term = $parent;
    }
    return $term;
}

function equipment_product_matches(int $id, array $filters): bool {
    $values = [];
    foreach (equipment_filter_specs($id) as $row) $values[equipment_spec_key($row['label'])][] = $row['value'];
    foreach ($filters as $key => $value) if (!in_array($value, $values[$key] ?? [], true)) return false;
    return true;
}

function equipment_cta(array $props): void {
    $title = trim((string) ($props['title'] ?? ''));
    $body = trim((string) ($props['description'] ?? ''));
    $url = esc_url((string) ($props['url'] ?? ''));
    if ((!$title && !$body) || !$url) return;
    $profile_id = function_exists('Starter\\Model\\profile_post_id') ? \Starter\Model\profile_post_id() : 0;
    $image = home_v2_image($props['image'] ?? ($profile_id ? get_field('equipment_cta_image', $profile_id) : 0));
    echo '<section class="equipment-cta' . ($image ? ' has-photo' : '') . '">';
    if ($image) echo '<div class="equipment-cta-photo" aria-hidden="true">' . wp_get_attachment_image($image, 'full', false, ['loading' => 'lazy', 'decoding' => 'async', 'alt' => '']) . '</div>';
    echo '<div class="equipment-shell"><div>';
    if ($title) echo '<h2>' . esc_html($title) . '</h2>';
    if ($body) echo '<p>' . esc_html($body) . '</p>';
    echo '</div><a class="button" href="' . $url . '">' . esc_html(($props['button_label'] ?? '') ?: 'Request a Quote') . ' <span aria-hidden="true">→</span></a></div></section>';
}
