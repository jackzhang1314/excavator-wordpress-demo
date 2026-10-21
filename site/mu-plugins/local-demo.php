<?php
/** Plugin Name: Excavator local demo isolation */
defined('ABSPATH') || exit;

// The downloadable demo stores inquiries locally. No mail transport is used.
add_filter('pre_wp_mail', static function () {
    update_option('excavator_demo_blocked_mail_count', (int) get_option('excavator_demo_blocked_mail_count', 0) + 1, false);
    return true;
}, PHP_INT_MAX);
add_filter('automatic_updater_disabled', '__return_true');
add_filter('auto_update_plugin', '__return_false');
add_filter('auto_update_theme', '__return_false');
add_filter('pre_option_blog_public', static fn() => '0');

// Classic draft previews can select a revision created before ACF was saved.
// Use the saved draft only when that revision has no ACF snapshot at all.
// Existing snapshots (including explicit empty values) remain authoritative.
add_filter('acf/validate_post_id', static function ($post_id) {
    if (!is_numeric($post_id) || !isset($_GET['preview']) || !is_preview() || !function_exists('acf_get_meta')) return $post_id;
    $parent = wp_is_post_revision((int) $post_id);
    if (!$parent || get_queried_object_id() !== $parent || !current_user_can('edit_post', $parent)
        || !in_array(get_post_status($parent), ['draft', 'pending'], true)
        || !in_array(get_post_type($parent), ['page', 'post', 'starter_product', 'starter_category', 'starter_industry', 'starter_guide'], true)) return $post_id;
    return acf_get_meta($post_id) === [] && acf_get_meta($parent) !== [] ? $parent : $post_id;
}, 20);

// Fluent Forms sends slash-separated calendar dates. MySQL accepts those as
// DATETIME values; SQLite text comparisons need the stored ISO representation.
add_action('muplugins_loaded', static function () {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $route = is_string($_GET['rest_route'] ?? null) ? $_GET['rest_route'] : $path;
    if (!is_string($route) || !preg_match('~^/(?:wp-json/)?fluentform/v1/~', $route)) return;
    $dates = $_GET['date_range'] ?? null;
    if (!is_array($dates)) return;
    foreach ($dates as &$date) {
        if (is_string($date) && preg_match('~^(\d{4})/(\d{1,2})/(\d{1,2})$~D', $date, $parts) && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) $date = sprintf('%04d-%02d-%02d', (int) $parts[1], (int) $parts[2], (int) $parts[3]);
    }
    unset($date);
    // Normalize before the plugin's request object captures PHP input.
    $_GET['date_range'] = $dates;
    $_REQUEST['date_range'] = $dates;
});
add_filter('get_avatar_url', static function ($url, $identity) {
    if (str_contains($url, 'gravatar.com')) return plugins_url('starter-model/assets/images/example-author.webp');
    return $url;
}, 100, 2);

add_action('admin_notices', static function () {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Local demo:</strong> Content changes are saved on this computer. Inquiries stay in Fluent Forms → Entries. Outgoing email and business account integrations are disabled. The original language selector is included; online translation requires internet.</p></div>';
});
