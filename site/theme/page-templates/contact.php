<?php
/**
 * Template Name: Contact / RFQ
 * Template Post Type: page
 * Template Content Source: ACF
 */
get_header(); ?><main id="main" class="eq-page eq-contact"><?php while (have_posts()) : the_post();
$data = Starter\Theme\contact_data();
$paths = Starter\Theme\equipment_media_cards('contact_path_cards', (int) get_the_ID());
$channels = Starter\Theme\eq_pairs('contact_channels');
$steps = Starter\Theme\equipment_media_cards('contact_next_cards', (int) get_the_ID());
$legacy_body = (string) get_post_field('post_content', get_the_ID());
$legacy_form = has_shortcode($legacy_body, 'starter_rfq_form') || has_shortcode($legacy_body, 'fluentform');
?>
<header class="eq-contact-hero eq-shell"><p class="eq-overline">Contact us</p><h1><?php the_title(); ?></h1><?php if ($data['intro']) : ?><p><?php echo esc_html($data['intro']); ?></p><?php endif; ?></header>
<div class="eq-shell eq-contact-layout"><div class="eq-contact-information">
<?php Starter\Theme\eq_photo(Starter\Theme\eq_image('contact_image'), '', 'eq-contact-photo', true); ?>
<?php if ($paths || $data['body'] !== '') : ?><section class="eq-contact-paths"><?php if ($heading = Starter\Theme\eq_text('contact_paths_title')) : ?><h2><?php echo esc_html($heading); ?></h2><?php endif; ?>
<?php if ($data['body']) : ?><div class="prose eq-prose"><?php echo $data['body']; ?></div><?php endif; ?>
<?php foreach ($paths as $index => $path) : ?><article><span class="eq-contact-path-icon" aria-hidden="true"><?php echo Starter\Theme\eq_line_icon(['quote','cooperation','service'][$index % 3]); ?></span><div><h3><?php if ($path['link']) Starter\Theme\home_v2_text_link($path['link']); else echo esc_html($path['title']); ?></h3><p><?php echo esc_html($path['description']); ?></p></div></article><?php endforeach; ?></section><?php endif; ?>
<?php if ($channels) : ?><dl class="eq-contact-channels"><?php foreach ($channels as $channel) : ?><div><dt><?php echo esc_html($channel['label']); ?></dt><dd><?php echo esc_html($channel['value']); ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
<?php if ($data['faq']) : ?><section class="eq-contact-faq"><?php Starter\Theme\component_faq(['rows' => $data['faq']]); ?></section><?php endif; ?>
</div><div class="eq-contact-form" id="rfq-form">
<?php if ($legacy_form) : ?><aside id="product-inquiry" class="product-inquiry"><div class="product-inquiry-card"><h2><?php echo esc_html($data['form_title']); ?></h2><?php the_content(); ?></div></aside><?php else : Starter\Theme\component_product_inquiry(['title' => $data['form_title'], 'description' => $data['form_note']]); endif; ?>
</div></div>
<?php if ($steps) : ?><section class="eq-section eq-contact-next"><div class="eq-shell"><?php Starter\Theme\eq_section_head(Starter\Theme\eq_text('contact_next_title'), Starter\Theme\eq_text('contact_next_intro')); Starter\Theme\eq_photo_cards($steps, true); ?></div></section><?php endif; ?>
<?php $range_title = Starter\Theme\eq_text('contact_range_title'); $range_image = Starter\Theme\eq_image('contact_range_image'); if ($range_title || Starter\Theme\eq_text('contact_range_intro')) : ?><section class="eq-contact-range"><?php Starter\Theme\eq_photo($range_image); ?><div class="eq-shell"><div><?php if ($range_title) : ?><h2><?php echo esc_html($range_title); ?></h2><?php endif; ?><?php if ($intro = Starter\Theme\eq_text('contact_range_intro')) : ?><p><?php echo esc_html($intro); ?></p><?php endif; ?></div><a class="button" href="<?php echo esc_url(get_post_type_archive_link('starter_product')); ?>">View all equipment <span aria-hidden="true">→</span></a></div></section><?php endif; ?>
<?php endwhile; ?></main><?php get_footer(); ?>
