<?php
/**
 * Template Name: Dealer Opportunity
 * Template Post Type: page
 * Template Content Source: ACF
 */
get_header(); ?><main id="main" class="eq-page eq-dealer"><?php while (have_posts()) : the_post();
Starter\Theme\eq_business_hero('dealer', 'fleet');
if (get_field('dealer_show_range')) Starter\Theme\eq_family_strip(Starter\Theme\eq_text('dealer_range_title'));
Starter\Theme\eq_card_section('dealer', 'support');
Starter\Theme\eq_card_section('dealer', 'process', true, 'eq-text-process');
Starter\Theme\eq_card_section('dealer', 'exchange', false, 'eq-exchange-cards');
if (get_field('dealer_show_form')) : ?><section class="eq-section eq-business-inquiry"><div class="eq-shell"><?php Starter\Theme\eq_business_form('dealer'); ?></div></section><?php endif;
Starter\Theme\eq_business_close('dealer');
endwhile; ?></main><?php get_footer(); ?>
