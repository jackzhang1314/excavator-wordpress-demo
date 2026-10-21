<?php
/**
 * Template Name: Certifications & Documents
 * Template Post Type: page
 * Template Content Source: ACF
 */
get_header(); ?><main id="main" class="eq-page eq-documents"><?php while (have_posts()) : the_post();
Starter\Theme\eq_business_hero('documents', 'light');
Starter\Theme\eq_document_directory();
Starter\Theme\eq_card_section('documents', 'scope', false, 'eq-document-scope');
if (get_field('documents_show_form')) : ?><section class="eq-document-inquiry"><div class="eq-document-inquiry-copy"><?php if ($heading = Starter\Theme\eq_text('documents_request_title')) : ?><h2><?php echo esc_html($heading); ?></h2><?php endif; ?><div class="prose eq-prose"><?php echo get_field('documents_request_body', false, true, true); ?></div></div><div class="eq-document-inquiry-form"><?php Starter\Theme\eq_business_form('documents'); ?></div></section><?php endif;
if ($image = Starter\Theme\eq_image('documents_fleet_image')) Starter\Theme\eq_photo($image, '', 'eq-fleet-strip');
Starter\Theme\eq_business_close('documents');
endwhile; ?></main><?php get_footer(); ?>
