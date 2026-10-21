<?php
/**
 * Template Name: Payment, Delivery & Support
 * Template Post Type: page
 * Template Content Source: ACF
 */
get_header(); ?><main id="main" class="eq-page eq-service"><?php while (have_posts()) : the_post();
Starter\Theme\eq_business_hero('service'); $steps = Starter\Theme\eq_pairs('service_steps');
if ($steps || get_field('service_show_form')) : ?><section class="eq-section"><div class="eq-shell eq-service-process"><div><?php if ($steps) : Starter\Theme\eq_section_head(Starter\Theme\eq_text('service_process_title'), Starter\Theme\eq_text('service_process_intro')); ?><ol class="eq-service-steps"><?php foreach ($steps as $index => $row) : ?><li><span aria-hidden="true"><?php echo $index + 1; ?></span><div><h3><?php echo esc_html($row['label']); ?></h3><p><?php echo esc_html($row['value']); ?></p></div></li><?php endforeach; ?></ol><?php endif; ?></div><?php Starter\Theme\eq_business_form('service'); ?></div></section><?php endif;
Starter\Theme\eq_business_table('service', 'table', ['Stage', 'Information needed', 'Documents to confirm']);
Starter\Theme\eq_card_section('service', 'packing', false, 'eq-service-photos');
if ($rows = Starter\Theme\eq_pairs('service_faq_rows')) : ?><section class="eq-section"><div class="eq-shell"><?php Starter\Theme\eq_section_head(Starter\Theme\eq_text('service_faq_title'), Starter\Theme\eq_text('service_faq_intro')); Starter\Theme\component_faq(['rows' => $rows]); ?></div></section><?php endif;
$body = Starter\Theme\module_body('service_body'); if (Starter\Theme\has_rich_content($body)) : ?><article class="eq-section eq-shell prose eq-prose"><?php echo $body; ?></article><?php endif;
Starter\Theme\eq_card_section('service', 'related');
Starter\Theme\eq_business_close('service');
endwhile; ?></main><?php get_footer(); ?>
