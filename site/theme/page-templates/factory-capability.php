<?php
/**
 * Template Name: Production & Quality
 * Template Post Type: page
 * Template Content Source: ACF
 */
get_header(); ?><main id="main" class="eq-page eq-production"><?php while (have_posts()) : the_post();
Starter\Theme\eq_business_hero('production');
Starter\Theme\eq_card_section('production', 'process', true, 'eq-process-photo-steps');
Starter\Theme\eq_business_table('production', 'checklist', ['Checkpoint', 'Record', 'Scope']);
Starter\Theme\eq_card_section('production', 'areas');
Starter\Theme\eq_card_section('production', 'evidence', false, 'eq-evidence-cards');
$body = Starter\Theme\module_body('factory_body'); if (Starter\Theme\has_rich_content($body)) : ?><article class="eq-section eq-shell prose eq-prose"><?php echo $body; ?></article><?php endif;
if (get_field('production_show_form')) : ?><section class="eq-section eq-shell eq-business-inquiry"><?php Starter\Theme\eq_business_form('production'); ?></section><?php endif;
Starter\Theme\eq_business_close('production');
endwhile; ?></main><?php get_footer(); ?>
