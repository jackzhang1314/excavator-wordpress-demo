<?php
/**
 * Template Name: OEM Solution
 * Template Post Type: page
 * Template Content Source: ACF
 */
get_header(); ?><main id="main" class="eq-page eq-oem"><?php while (have_posts()) : the_post();
Starter\Theme\eq_business_hero('oem');
$topics = Starter\Theme\equipment_media_cards('oem_customize_cards', (int) get_the_ID()); $image = Starter\Theme\eq_image('oem_customization_image');
if ($topics) : ?><section class="eq-section"><div class="eq-shell"><?php Starter\Theme\eq_section_head(Starter\Theme\eq_text('oem_customize_title'), Starter\Theme\eq_text('oem_customize_intro')); ?><div class="eq-oem-configuration<?php echo $image ? ' has-photo' : ''; ?>"><?php Starter\Theme\eq_photo($image); ?><div class="eq-oem-points"><?php foreach ($topics as $topic) : ?><article><h3><?php echo esc_html($topic['title']); ?></h3><p><?php echo esc_html($topic['description']); ?></p></article><?php endforeach; ?></div></div></div></section><?php endif;
Starter\Theme\eq_card_section('oem', 'process', true, 'eq-text-process');
Starter\Theme\eq_business_table('oem', 'table', ['Request', 'Engineering review', 'Market requirements']);
Starter\Theme\eq_card_section('oem', 'evidence', false, 'eq-document-scope');
if (get_field('oem_show_form')) : ?><section class="eq-section eq-tone eq-business-inquiry"><div class="eq-shell"><?php Starter\Theme\eq_business_form('oem'); ?></div></section><?php endif;
Starter\Theme\eq_business_close('oem');
endwhile; ?></main><?php get_footer(); ?>
