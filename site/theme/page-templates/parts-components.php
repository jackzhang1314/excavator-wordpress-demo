<?php
/**
 * Template Name: Parts & Components
 * Template Post Type: page
 * Template Content Source: ACF
 */
get_header(); ?><main id="main" class="eq-page eq-parts"><?php while (have_posts()) : the_post();
Starter\Theme\eq_business_hero('parts');
Starter\Theme\eq_card_section('parts', 'support', false, 'eq-parts-support');
Starter\Theme\eq_card_section('parts', 'components', false, 'eq-component-cards');
Starter\Theme\eq_card_section('parts', 'identify');
$requests = Starter\Theme\equipment_media_cards('parts_requests_cards', (int) get_the_ID());
if (get_field('parts_show_form') || $requests) : ?><section class="eq-section"><div class="eq-shell eq-parts-inquiry"><?php if (get_field('parts_show_form')) Starter\Theme\eq_business_form('parts'); ?>
<?php if ($requests) : ?><aside class="eq-parts-common"><?php Starter\Theme\eq_section_head(Starter\Theme\eq_text('parts_requests_title'), Starter\Theme\eq_text('parts_requests_intro')); ?><div><?php foreach ($requests as $card) : ?><article><?php Starter\Theme\eq_photo($card['image']); ?><div><h3><?php echo esc_html($card['title']); ?></h3><p><?php echo esc_html($card['description']); ?></p></div></article><?php endforeach; ?></div></aside><?php endif; ?>
</div></section><?php endif;
$service = Starter\Theme\equipment_media_cards('parts_service_cards', (int) get_the_ID());
if ($service) : ?><section class="eq-parts-service"><?php Starter\Theme\eq_photo($service[0]['image']); ?><div><?php Starter\Theme\eq_section_head(Starter\Theme\eq_text('parts_service_title'), Starter\Theme\eq_text('parts_service_intro')); ?><ul><?php foreach ($service as $card) : ?><li><h3><?php if ($card['link']) Starter\Theme\home_v2_text_link($card['link']); else echo esc_html($card['title']); ?></h3><p><?php echo esc_html($card['description']); ?></p></li><?php endforeach; ?></ul></div></section><?php endif;
Starter\Theme\eq_business_close('parts');
endwhile; ?></main><?php get_footer(); ?>
