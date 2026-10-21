<?php
/** Blog overview — reference 17, using native posts and four topic taxonomies. */
get_header();
$page_id = (int) get_option('page_for_posts');
$page_record = Starter\Theme\eq_blog_page_record();
$featured_ids = !is_paged() && empty($_GET['topic']) ? Starter\Theme\eq_blog_featured() : [];
$categories = get_categories(['hide_empty' => false, 'exclude' => [(int) get_option('default_category')], 'number' => 8, 'orderby' => 'term_id', 'order' => 'ASC']);
$topic = isset($_GET['topic']) && is_string($_GET['topic']) ? sanitize_title(wp_unslash($_GET['topic'])) : '';
?>
<main id="main" class="eq-page eq-blog"><div class="eq-shell">
<header class="eq-blog-hero"><?php if ($overline = Starter\Theme\eq_text('blog_overline', $page_id)) : ?><p class="eq-overline"><?php echo esc_html($overline); ?></p><?php endif; ?><h1><?php echo esc_html($page_record ? $page_record->post_title : ''); ?></h1><?php $intro = trim((string) ($page_record->post_excerpt ?? '')); if ($intro) : ?><p><?php echo esc_html($intro); ?></p><?php endif; ?></header>
<nav class="eq-blog-topics" aria-label="Article topics"><a href="<?php echo esc_url(get_permalink($page_id)); ?>"<?php echo $topic === '' ? ' aria-current="page"' : ''; ?>>All</a><?php foreach ($categories as $category) : ?><a href="<?php echo esc_url(add_query_arg('topic', $category->slug, get_permalink($page_id))); ?>"<?php echo $topic === $category->slug ? ' aria-current="page"' : ''; ?>><?php echo esc_html($category->name); ?></a><?php endforeach; ?></nav>
<?php if ($featured_ids) : ?><section class="eq-editorial-feature has-<?php echo count($featured_ids); ?>" aria-label="Featured articles"><?php foreach ($featured_ids as $index => $id) { $post = get_post($id); if ($post instanceof WP_Post) Starter\Theme\eq_blog_card($post, $index === 0); } ?></section><?php endif; ?>
<?php if (have_posts()) : ?><section class="eq-editorial-grid" aria-label="Latest articles"><?php while (have_posts()) : the_post(); Starter\Theme\eq_blog_card(get_post()); endwhile; ?></section><?php else : ?><p class="eq-empty-results">No articles have been published in this topic yet.</p><?php endif; ?>
<?php the_posts_pagination(['prev_text' => '←', 'next_text' => '→']); ?>
</div>
<?php $consult_title = Starter\Theme\eq_text('blog_consult_title', $page_id); $consult_intro = Starter\Theme\eq_text('blog_consult_intro', $page_id); $consult_image = Starter\Theme\eq_image('blog_consult_image', $page_id); $show_form = (bool) get_field('blog_show_form', $page_id); if ($consult_title || $consult_intro || $consult_image || $show_form) : ?><section class="eq-blog-consult"><div class="eq-blog-consult-copy"><?php if ($consult_title) : ?><h2><?php echo esc_html($consult_title); ?></h2><?php endif; ?><?php if ($consult_intro) : ?><p><?php echo esc_html($consult_intro); ?></p><?php endif; ?></div><?php Starter\Theme\eq_photo($consult_image); ?><?php if ($show_form) : ?><div class="eq-blog-consult-form"><?php Starter\Theme\component_product_inquiry(['title' => Starter\Theme\eq_text('blog_form_title', $page_id)]); ?></div><?php endif; ?></section><?php endif; ?>
<?php if (get_field('blog_show_range', $page_id)) Starter\Theme\eq_family_strip(Starter\Theme\eq_text('blog_range_title', $page_id)); ?>
</main><?php get_footer(); ?>
