<?php
/** Native posts and taxonomies supply the selected editorial layouts. */
namespace Starter\Theme;
defined('ABSPATH') || exit;
/** Native Preview remains private and uses the posts page's current user's autosave. */
function eq_blog_preview_active(): bool {
    $id = (int) get_option('page_for_posts');
    return $id > 0 && absint($_GET['preview_id'] ?? 0) === $id
        && isset($_GET['preview_nonce']) && is_string($_GET['preview_nonce'])
        && current_user_can('edit_post', $id)
        && (bool) wp_verify_nonce(wp_unslash($_GET['preview_nonce']), 'post_preview_' . $id);
}
function eq_blog_page_record(): ?\WP_Post {
    $id = (int) get_option('page_for_posts');
    if (is_preview() && eq_blog_preview_active()) {
        $autosave = wp_get_post_autosave($id, get_current_user_id());
        if ($autosave instanceof \WP_Post) return $autosave;
    }
    return get_post($id);
}
add_filter('preview_post_link', static function (string $url, \WP_Post $post): string {
    if ($post->ID !== (int) get_option('page_for_posts') || !current_user_can('edit_post', $post->ID)) return $url;
    // A no-change autosave returns zero, so Core omits these two arguments.
    return add_query_arg(['preview_id' => $post->ID, 'preview_nonce' => wp_create_nonce('post_preview_' . $post->ID)], $url);
}, 10, 2);
function eq_blog_featured(): array {
    $raw = get_field('blog_featured', (int) get_option('page_for_posts'));
    return array_values(array_unique(array_filter(array_map('intval', is_array($raw) ? $raw : []), static fn (int $id): bool => get_post_type($id) === 'post' && get_post_status($id) === 'publish')));
}
add_action('pre_get_posts', static function (\WP_Query $query): void {
    if (is_admin() || !$query->is_main_query() || !$query->is_home()) return;
    if ($query->is_posts_page && $query->is_preview()) {
        if (!eq_blog_preview_active()) wp_die(esc_html__('Sorry, you are not allowed to preview this page.', 'b2b-starter'), '', ['response' => 403]);
        // Core treats the literal string "true" as a static-front-page preview.
        // A different truthy value keeps the native preview flag and the blog query.
        $query->set('preview', '1');
    }
    $topic = isset($_GET['topic']) && is_string($_GET['topic']) ? sanitize_title(wp_unslash($_GET['topic'])) : '';
    if ($topic !== '') {
        $term = get_term_by('slug', $topic, 'category');
        if ($term instanceof \WP_Term) $query->set('cat', $term->term_id);
        else $query->set('post__in', [0]);
        $query->set('posts_per_page', 9);
        return;
    }
    $featured = eq_blog_featured();
    if ($featured) $query->set('post__not_in', $featured);
    $query->set('posts_per_page', $featured ? 6 : 9);
    $query->set('ignore_sticky_posts', true);
});
function eq_blog_card(\WP_Post $post, bool $lead = false): void {
    $id = (int) $post->ID; $category = blog_primary_category($id); $image = get_post_thumbnail_id($id);
    echo '<article class="eq-editorial-card' . ($lead ? ' is-lead' : '') . ($image ? ' has-photo' : '') . '">';
    if ($image) echo '<a class="eq-editorial-photo" href="' . esc_url(get_permalink($id)) . '" tabindex="-1" aria-hidden="true">' . wp_get_attachment_image($image, 'large', false, ['loading' => $lead ? 'eager' : 'lazy']) . '</a>';
    echo '<div class="eq-editorial-card-copy">';
    if ($category instanceof \WP_Term) echo '<p class="eq-editorial-topic">' . esc_html($category->name) . '</p>';
    echo '<h2><a href="' . esc_url(get_permalink($id)) . '">' . esc_html(get_the_title($id)) . '</a></h2>';
    $excerpt = trim(wp_strip_all_tags((string) get_post_field('post_excerpt', $id)));
    if ($lead && $excerpt !== '') echo '<p class="eq-editorial-excerpt">' . esc_html($excerpt) . '</p>';
    echo '<div class="eq-editorial-meta"><time datetime="' . esc_attr(get_the_date('c', $id)) . '">' . esc_html(get_the_date('', $id)) . '</time><span>By ' . esc_html(get_the_author_meta('display_name', (int) $post->post_author)) . '</span><a href="' . esc_url(get_permalink($id)) . '" aria-label="' . esc_attr('Read ' . get_the_title($id)) . '">→</a></div></div></article>';
}
function eq_article_context(): void {
    $title = eq_text('article_context_title'); $intro = eq_text('article_context_intro');
    if ($title || $intro) {
        echo '<aside class="eq-article-context">';
        if ($title) echo '<h2>' . esc_html($title) . '</h2>';
        if ($intro) echo '<p>' . esc_html($intro) . '</p>';
        echo '<a class="button" href="' . esc_url(contact_url()) . '">Request a Quote <span aria-hidden="true">→</span></a></aside>';
    }
    $raw = get_field('article_products');
    $ids = array_values(array_filter(array_map('intval', is_array($raw) ? $raw : []), static fn (int $id): bool => get_post_type($id) === 'starter_product' && get_post_status($id) === 'publish'));
    if (!$ids) return;
    echo '<aside class="eq-article-equipment"><h2>Related equipment</h2>';
    foreach ($ids as $id) {
        $family = equipment_family_for_product($id);
        echo '<article>'; eq_photo(get_post_thumbnail_id($id));
        echo '<h3>' . esc_html(get_the_title($id)) . '</h3>';
        if ($family) echo '<p>' . esc_html($family->name) . '</p>';
        echo '<a class="eq-text-link" href="' . esc_url(get_permalink($id)) . '">View Details <span aria-hidden="true">→</span></a></article>';
    }
    echo '</aside>';
}
