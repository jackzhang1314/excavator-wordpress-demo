<?php
/** Native navigation presentation for the approved equipment mega menu. */
namespace Starter\Theme;
defined('ABSPATH') || exit;
add_action('after_setup_theme', static function (): void {
    register_nav_menus(['utility' => __('Header utility links', 'b2b-starter'), 'footer_support' => __('Footer support links', 'b2b-starter')]);
});
add_filter('wp_nav_menu_objects', static function (array $items, $args): array {
    if (($args->theme_location ?? '') !== 'primary') return $items;
    $root = 0;
    foreach ($items as $item) if (in_array('equipment-menu', (array) $item->classes, true)) $root = (int) $item->ID;
    if ($root) foreach ($items as $item) if ((int) $item->menu_item_parent === $root && !in_array('menu-view-all', (array) $item->classes, true)) $item->classes[] = 'menu-family';
    return $items;
}, 10, 2);
add_filter('nav_menu_item_title', static function (string $title, $item, $args): string {
    if (($args->theme_location ?? '') !== 'primary' || !in_array('menu-family', (array) $item->classes, true)) return $title;
    $id = (int) $item->object_id;
    if (get_post_type($id) !== 'starter_category') return $title;
    $image = function_exists('get_field') ? get_field('category_card_image', $id) : 0;
    $image = is_array($image) ? (int) ($image['ID'] ?? 0) : (int) $image;
    $photo = $image && wp_attachment_is_image($image) ? '<span class="menu-family-photo">' . wp_get_attachment_image($image, 'medium', false, ['alt' => '', 'loading' => 'lazy']) . '</span>' : '';
    return '<span class="menu-family-label">' . esc_html(wp_strip_all_tags($title)) . '</span>' . $photo;
}, 10, 3);
add_filter('walker_nav_menu_start_el', static function (string $html, $item, int $depth, $args): string {
    if (($args->theme_location ?? '') === 'primary' && $item->type === 'custom' && $item->url === '#') {
        return (string) preg_replace('/<a\b[^>]*>(.*?)<\/a>/s', '<span class="menu-group-label">$1</span>', $html, 1);
    }
    return $html;
}, 10, 4);
