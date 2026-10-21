<?php
/** Defer the first native save for the confirmed Rank Math classic submission re-entry. */
namespace Starter\ClassicSubmit;
defined('ABSPATH') || exit;

add_action('rank_math/admin/editor_scripts', static function (): void {
    if (!defined('RANK_MATH_VERSION') || !in_array(RANK_MATH_VERSION, ['1.0.279', '1.0.280'], true)) return;
    $screen = get_current_screen();
    if (!$screen || $screen->base !== 'post' || $screen->is_block_editor()) return;
    if (!wp_script_is('rank-math-editor', 'enqueued')) return;
    $script = wp_scripts()->registered['rank-math-editor'] ?? null;
    $path = $script ? (string) wp_parse_url($script->src, PHP_URL_PATH) : '';
    if (!str_ends_with($path, '/assets/admin/js/classic.js')) return;
    wp_enqueue_script(
        'starter-classic-submit',
        plugins_url('../assets/js/classic-submit.js', __FILE__),
        ['rank-math-editor'],
        (string) filemtime(__DIR__ . '/../assets/js/classic-submit.js'),
        true
    );
});
