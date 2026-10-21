<?php
/** Industry overview — selected reference 07, with native curated industry relations. */
get_header();
$profile = Starter\Model\profile_post_id();
$title = Starter\Theme\eq_text('industry_archive_title', $profile);
$image = Starter\Theme\eq_image('industry_archive_image', $profile);
$selected = get_field('industry_directory_items', $profile);
$industries = array_values(array_filter(array_map('intval', is_array($selected) ? $selected : []), static fn (int $id): bool => get_post_type($id) === 'starter_industry' && get_post_status($id) === 'publish'));
$factors = Starter\Theme\equipment_media_cards('industry_factor_cards', $profile);
$journey = Starter\Theme\equipment_media_cards('industry_journey_cards', $profile);
?>
<main id="main" class="eq-page eq-industry-directory">
<header class="eq-scene-hero">
<?php if ($image) Starter\Theme\eq_photo($image, '', 'eq-scene-background', true); ?>
<div class="eq-shell"><p class="eq-overline">Industry solutions</p><h1><?php echo esc_html($title !== '' ? $title : post_type_archive_title('', false)); ?></h1>
<?php if ($intro = Starter\Theme\eq_text('industry_archive_description', $profile)) : ?><p class="eq-scene-intro"><?php echo esc_html($intro); ?></p><?php endif; ?>
<a class="button" href="<?php echo esc_url(Starter\Theme\contact_url()); ?>">Request a Quote <span aria-hidden="true">→</span></a></div>
</header>
<?php if ($industries) : ?><section class="eq-section"><div class="eq-shell">
<?php Starter\Theme\eq_section_head(Starter\Theme\eq_text('industry_directory_title', $profile), Starter\Theme\eq_text('industry_directory_intro', $profile)); ?>
<div class="eq-industry-grid"><?php foreach ($industries as $id) : $card_image = Starter\Theme\eq_image('industry_card_image', $id) ?: get_post_thumbnail_id($id); ?>
<a href="<?php echo esc_url(get_permalink($id)); ?>"><?php if ($card_image) echo wp_get_attachment_image($card_image, 'medium_large', false, ['loading' => 'lazy']); ?><h3><?php echo esc_html(get_the_title($id)); ?><span aria-hidden="true">→</span></h3></a>
<?php endforeach; ?></div></div></section><?php endif; ?>
<?php if ($factors) : ?><section class="eq-section eq-industry-factors"><div class="eq-shell">
<?php Starter\Theme\eq_section_head(Starter\Theme\eq_text('industry_factors_title', $profile), Starter\Theme\eq_text('industry_factors_intro', $profile)); ?>
<?php Starter\Theme\eq_photo_cards($factors, false, 'eq-factor-cards'); ?></div></section><?php endif; ?>
<?php if ($journey) : ?><section class="eq-section"><div class="eq-shell">
<?php Starter\Theme\eq_section_head(Starter\Theme\eq_text('industry_journey_title', $profile), Starter\Theme\eq_text('industry_journey_intro', $profile)); ?>
<?php Starter\Theme\eq_photo_cards($journey, false, 'eq-journey-cards'); ?></div></section><?php endif; ?>
<?php if (($advice = Starter\Theme\eq_text('industry_directory_cta_title', $profile)) || Starter\Theme\eq_text('industry_directory_cta_intro', $profile)) : ?>
<section class="eq-industry-advice eq-shell"><h2><?php echo esc_html($advice); ?></h2><p><?php echo esc_html(Starter\Theme\eq_text('industry_directory_cta_intro', $profile)); ?></p><a class="button" href="<?php echo esc_url(Starter\Theme\contact_url()); ?>">Request a Quote <span aria-hidden="true">→</span></a></section><?php endif; ?>
<?php Starter\Theme\eq_inquiry_band(Starter\Theme\eq_text('equipment_cta_title', $profile), Starter\Theme\eq_text('equipment_cta_text', $profile), Starter\Theme\eq_image('equipment_cta_image', $profile)); ?>
</main><?php get_footer(); ?>
