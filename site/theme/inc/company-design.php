<?php
/** Presentation shared by the selected company-page layouts. */
namespace Starter\Theme;
defined('ABSPATH') || exit;

/** Small line icons inherit the page colour, avoiding platform-specific emoji. */
function eq_line_icon(string $name): string {
    $paths = [
        'quote' => '<path d="M5 4h14v12H9l-4 4V4Z"/><path d="M9 8h6M9 12h4"/>',
        'cooperation' => '<path d="m3 8 5-4 4 2 4-2 5 4-4 9-5 3-5-3-4-9Z"/><path d="m8 9 4-3 5 5-5 5-4-3M4 10l4 3M17 15l3-5"/>',
        'service' => '<path d="M14 5a5 5 0 0 0-6 6L3 16a3 3 0 0 0 5 5l5-5a5 5 0 0 0 6-6l-4 3-4-4 3-4Z"/>',
        'document' => '<path d="M6 2h8l5 5v15H6zM14 2v6h5M9 12h7M9 16h7"/>',
        'photo' => '<rect x="2" y="3" width="20" height="18" rx="1"/><circle cx="8" cy="8" r="2"/><path d="m2 18 6-6 4 4 4-5 6 7"/>',
        'model' => '<path d="M3 17h13l3 3H2zM6 16V9h7v7M13 10l4-7 3 1-3 7 4 3-1 2-5-3M8 12h3"/>',
        'globe' => '<circle cx="12" cy="12" r="10"/><ellipse cx="12" cy="12" rx="4" ry="10"/><path d="M2 12h20M4 6h16M4 18h16"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="17" rx="1"/><path d="M7 2v6M17 2v6M3 11h18M7 15h2M13 15h2M7 19h2M13 19h2"/>',
        'gear' => '<path d="m9 3 1-2h4l1 2 3 2 2-1 2 4-2 2v4l2 2-2 4-2-1-3 2-1 2h-4l-1-2-3-2-2 1-2-4 2-2v-4L2 8l2-4 2 1z"/><circle cx="12" cy="12" r="4"/>',
        'review' => '<path d="M5 2h12v9M5 2v20h7M8 6h6M8 10h3M8 14h3"/><circle cx="16" cy="15" r="4"/><path d="m19 18 3 4"/>',
        'factory' => '<path d="M2 22V11l7-5v6l7-5v15H2ZM16 13h6v9M4 15h3M4 19h3M10 15h3M10 19h3M18 17h2M18 20h2M18 12V2h3l1 10"/>',
        'chart' => '<path d="M3 21V12h4v9M10 21V7h4v14M17 21V2h4v19"/>',
        'check' => '<path d="M6 2h8l5 5v15H6zM14 2v6h5m-10 7 3 3 5-6"/>',
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['quote']) . '</svg>';
}

function eq_text(string $name, int $id = 0): string {
    $value = get_field($name, $id ?: get_the_ID());
    return is_string($value) ? trim($value) : '';
}
function eq_image(string $name, int $id = 0): int {
    return home_v2_image(get_field($name, $id ?: get_the_ID()));
}
function eq_pairs(string $name, int $id = 0): array {
    $raw = get_field($name, $id ?: get_the_ID());
    return array_values(array_filter(is_array($raw) ? $raw : [], static fn ($row): bool => is_array($row)
        && trim((string) ($row['label'] ?? '')) !== '' && trim((string) ($row['value'] ?? '')) !== ''));
}
function eq_section_head(string $title, string $intro = '', string $url = '', string $link = ''): void {
    if ($title === '' && $intro === '' && ($url === '' || $link === '')) return;
    echo '<header class="eq-section-head">';
    if ($title !== '') echo '<h2>' . esc_html($title) . '</h2>';
    if ($intro !== '') echo '<p>' . esc_html($intro) . '</p>';
    if ($url !== '' && $link !== '') echo '<a class="eq-text-link" href="' . esc_url($url) . '">' . esc_html($link) . ' <span aria-hidden="true">→</span></a>';
    echo '</header>';
}
function eq_photo(int $id, string $caption = '', string $class = '', bool $eager = false): void {
    if (!$id) return;
    echo '<figure class="eq-photo ' . esc_attr($class) . '">';
    echo wp_get_attachment_image($id, 'full', false, ['loading' => $eager ? 'eager' : 'lazy', 'decoding' => 'async']);
    if ($caption !== '') echo '<figcaption>' . esc_html($caption) . '</figcaption>';
    echo '</figure>';
}
function eq_photo_cards(array $cards, bool $numbered = false, string $class = '', string $heading_tag = 'h3'): void {
    if (!$cards) return;
    $heading_tag = in_array($heading_tag, ['h2', 'h3', 'h4'], true) ? $heading_tag : 'h3';
    echo '<div class="eq-photo-cards ' . esc_attr($class) . '" style="--card-count:' . min(5, count($cards)) . '">';
    foreach ($cards as $index => $card) {
        echo '<article class="eq-photo-card">';
        eq_photo($card['image']);
        echo '<div class="eq-photo-card-copy">';
        if ($numbered) echo '<span class="eq-step-number" aria-hidden="true">' . sprintf('%02d', $index + 1) . '</span>';
        if (!empty($card['icon'])) echo '<span class="eq-card-icon">' . eq_line_icon($card['icon']) . '</span>';
        echo '<div><' . $heading_tag . '>' . esc_html($card['title']) . '</' . $heading_tag . '>';
        if ($card['description'] !== '') echo '<p>' . esc_html($card['description']) . '</p>';
        if ($card['link']) home_v2_text_link($card['link'], 'eq-text-link');
        echo '</div></div></article>';
    }
    echo '</div>';
}
function eq_family_strip(string $title): void {
    $families = equipment_families();
    if (!$families) return;
    echo '<section class="eq-section eq-range"><div class="eq-shell">';
    eq_section_head($title, '', get_post_type_archive_link('starter_product'), 'View all equipment');
    echo '<div class="eq-family-strip">';
    foreach ($families as $family) {
        echo '<a href="' . esc_url($family['url']) . '">';
        if ($family['image']) echo wp_get_attachment_image($family['image'], 'medium_large', false, ['loading' => 'lazy']);
        echo '<span>' . esc_html($family['name']) . ' <span aria-hidden="true">→</span></span></a>';
    }
    echo '</div></div></section>';
}
function eq_inquiry_band(string $title, string $intro = '', int $image = 0): void {
    if ($title === '' && $intro === '') return;
    echo '<section class="eq-inquiry-band">';
    if ($image) echo wp_get_attachment_image($image, 'full', false, ['class' => 'eq-band-image', 'loading' => 'lazy', 'alt' => '']);
    echo '<div class="eq-shell"><div><p class="eq-overline">Get in touch</p>';
    if ($title !== '') echo '<h2>' . esc_html($title) . '</h2>';
    if ($intro !== '') echo '<p>' . esc_html($intro) . '</p>';
    echo '</div><a class="button" href="' . esc_url(contact_url()) . '">Request a Quote <span aria-hidden="true">→</span></a></div></section>';
}

function eq_business_hero(string $prefix, string $variant = 'dark', bool $inquiry = true): void {
    $image = eq_image($prefix . '_hero_image');
    echo '<header class="eq-scene-hero eq-business-hero is-' . esc_attr($variant) . '">';
    eq_photo($image, '', 'eq-scene-background', true);
    echo '<div class="eq-shell">';
    if ($overline = eq_text($prefix . '_hero_overline')) echo '<p class="eq-overline">' . esc_html($overline) . '</p>';
    echo '<h1>' . esc_html(eq_text($prefix . '_hero_title') ?: get_the_title()) . '</h1>';
    if ($intro = eq_text($prefix . '_hero_intro')) echo '<p class="eq-scene-intro">' . esc_html($intro) . '</p>';
    if ($variant !== 'light' && $inquiry) echo '<a class="button" href="' . esc_url(get_field($prefix . '_show_form') ? '#product-inquiry' : contact_url()) . '">Request a Quote <span aria-hidden="true">→</span></a>';
    echo '</div>';
    if ($image && ($caption = eq_text($prefix . '_hero_caption'))) echo '<p class="eq-scene-caption">' . esc_html($caption) . '</p>';
    echo '</header>';
}
function eq_card_section(string $prefix, string $section, bool $numbered = false, string $class = ''): void {
    $name = $prefix . '_' . $section;
    $cards = equipment_media_cards($name . '_cards', (int) get_the_ID());
    if (!$cards) return;
    echo '<section class="eq-section ' . esc_attr($class) . '"><div class="eq-shell">';
    eq_section_head(eq_text($name . '_title'), eq_text($name . '_intro'));
    eq_photo_cards($cards, $numbered, '', eq_text($name . '_title') !== '' ? 'h3' : 'h2');
    echo '</div></section>';
}
function eq_three_column_rows(string $name): array {
    $raw = get_field($name);
    return array_values(array_filter(is_array($raw) ? $raw : [], static fn ($row): bool => is_array($row)
        && trim((string) ($row['first'] ?? '')) !== '' && trim((string) ($row['second'] ?? '')) !== '' && trim((string) ($row['third'] ?? '')) !== ''));
}
function eq_business_table(string $prefix, string $section, array $headings): void {
    $rows = eq_three_column_rows($prefix . '_' . $section . '_rows');
    if (!$rows) return;
    echo '<section class="eq-section"><div class="eq-shell">';
    eq_section_head(eq_text($prefix . '_' . $section . '_title'), eq_text($prefix . '_' . $section . '_intro'));
    echo '<div class="eq-scroll-table" role="region" tabindex="0" aria-label="' . esc_attr(implode(', ', $headings)) . '"><table class="eq-table"><thead><tr>';
    foreach ($headings as $index => $heading) echo '<th scope="col">' . ($prefix === 'oem' ? '<span class="eq-table-heading-icon">' . eq_line_icon(['quote', 'gear', 'chart'][$index] ?? 'document') . '</span>' : '') . esc_html($heading) . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) echo '<tr><th scope="row">' . esc_html($row['first']) . '</th><td>' . esc_html($row['second']) . '</td><td>' . esc_html($row['third']) . '</td></tr>';
    echo '</tbody></table></div></div></section>';
}
function eq_business_form(string $prefix): void {
    if (!get_field($prefix . '_show_form')) return;
    component_product_inquiry(['title' => eq_text($prefix . '_inquiry_title'), 'description' => eq_text($prefix . '_inquiry_intro')]);
}
function eq_business_close(string $prefix): void {
    eq_inquiry_band(eq_text($prefix . '_cta_title'), eq_text($prefix . '_cta_intro'), eq_image($prefix . '_cta_image'));
}
