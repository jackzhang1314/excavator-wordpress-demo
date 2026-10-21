<?php
/** Selected Equipment Overview 02: native families, tasks and working conditions. */
$profile = Starter\Model\profile_post_id();
$families = Starter\Theme\equipment_families();
$title = Starter\Theme\global_text('equipment_archive_title', 'Equipment');
$intro = Starter\Theme\global_text('product_archive_description');
$image = Starter\Theme\home_v2_image(get_field('equipment_archive_image', $profile));
$tasks = Starter\Theme\equipment_media_cards('equipment_task_cards', $profile);
$conditions = Starter\Theme\equipment_media_cards('equipment_condition_cards', $profile);
get_header(); ?>
<main id="main" class="equipment-main equipment-overview">
<section class="equipment-hero<?php echo $image ? ' has-media' : ''; ?>">
    <?php if ($image) : ?><div class="equipment-hero-photo"><?php echo wp_get_attachment_image($image, 'full', false, ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width:767px) 100vw, 1440px']); ?></div><?php endif; ?>
    <div class="equipment-shell equipment-hero-inner"><div class="equipment-hero-copy"><?php Starter\Theme\breadcrumbs(); ?><h1><?php echo esc_html($title); ?></h1><?php if ($intro) : ?><p><?php echo esc_html($intro); ?></p><?php endif; ?><a class="button" href="<?php echo esc_url(Starter\Theme\contact_url()); ?>">Request a Quote <span aria-hidden="true">→</span></a></div></div>
</section>
<?php if ($families) : ?><section class="equipment-section equipment-shell equipment-overview-families" aria-label="Equipment families"><div class="equipment-family-catalogue"><?php foreach ($families as $family) : ?><article>
    <?php if ($family['image']) : ?><a class="equipment-family-catalogue-photo" href="<?php echo esc_url($family['url']); ?>" aria-hidden="true" tabindex="-1"><?php echo wp_get_attachment_image($family['image'], 'large', false, ['loading' => 'lazy']); ?></a><?php endif; ?>
    <div><h2><a href="<?php echo esc_url($family['url']); ?>"><?php echo esc_html($family['name']); ?></a></h2><?php if ($family['intro']) : ?><p><?php echo esc_html($family['intro']); ?></p><?php endif; ?><a class="equipment-text-link" href="<?php echo esc_url($family['url']); ?>">Explore Range <span aria-hidden="true">→</span></a></div>
</article><?php endforeach; ?></div></section><?php endif; ?>
<?php if ($tasks) : ?><section class="equipment-section equipment-task-section"><div class="equipment-shell equipment-tasks">
    <div class="equipment-advice"><h2><?php echo esc_html(Starter\Theme\global_text('equipment_advice_title', 'Not sure where to start?')); ?></h2><?php $advice = Starter\Theme\global_text('equipment_advice_description'); if ($advice) : ?><p><?php echo esc_html($advice); ?></p><?php endif; ?><a class="button" href="<?php echo esc_url(Starter\Theme\contact_url()); ?>">Get Product Advice <span aria-hidden="true">→</span></a></div>
    <?php foreach ($tasks as $card) : ?><article class="equipment-task-card<?php echo $card['image'] ? ' has-media' : ''; ?>"><?php if ($card['image']) echo wp_get_attachment_image($card['image'], 'large', false, ['loading' => 'lazy']); ?><div><h3><?php if ($card['link']) : ?><a href="<?php echo esc_url($card['link']['url']); ?>"><?php echo esc_html($card['title']); ?><span aria-hidden="true">→</span></a><?php else : echo esc_html($card['title']); endif; ?></h3><?php if ($card['description']) : ?><p><?php echo esc_html($card['description']); ?></p><?php endif; ?></div></article><?php endforeach; ?>
</div></section><?php endif; ?>
<?php if ($conditions) : ?><section class="equipment-section equipment-shell equipment-conditions"><div class="equipment-section-heading"><h2><?php echo esc_html(Starter\Theme\global_text('equipment_conditions_title', 'Choose by working conditions')); ?></h2><?php $conditions_intro = Starter\Theme\global_text('equipment_conditions_description'); if ($conditions_intro) : ?><p><?php echo esc_html($conditions_intro); ?></p><?php endif; ?></div><div class="equipment-condition-grid"><?php foreach ($conditions as $card) : ?><article><?php if ($card['image']) echo wp_get_attachment_image($card['image'], 'large', false, ['loading' => 'lazy']); ?><div><h3><?php if ($card['link']) : ?><a href="<?php echo esc_url($card['link']['url']); ?>"><?php echo esc_html($card['title']); ?> <span aria-hidden="true">→</span></a><?php else : echo esc_html($card['title']); endif; ?></h3><?php if ($card['description']) : ?><p><?php echo esc_html($card['description']); ?></p><?php endif; ?></div></article><?php endforeach; ?></div></section><?php endif; ?>
<?php Starter\Theme\equipment_cta(['title' => Starter\Theme\global_text('equipment_cta_title'), 'description' => Starter\Theme\global_text('equipment_cta_text'), 'url' => Starter\Theme\contact_url(), 'button_label' => 'Request a Quote']); ?>
</main>
<?php get_footer(); ?>
