<?php
/**
 * Template Name: About page
 * Template Post Type: page
 * Template Content Source: ACF
 * @package b2b-starter
 */
get_header(); ?>
<main id="main" class="eq-page eq-about"><?php while (have_posts()) : the_post();
    $id = (int) get_the_ID();
    $hero_image = Starter\Theme\eq_image('about_hero_image');
    $story = Starter\Theme\about_data()['story'];
    $facts = Starter\Theme\eq_pairs('about_company_facts');
    $steps = Starter\Theme\equipment_media_cards('about_process_cards', $id);
    $team_image = Starter\Theme\eq_image('about_team_image');
    $timeline = Starter\Theme\eq_pairs('about_timeline');
    $background_image = Starter\Theme\eq_image('about_background_image');
    ?>
    <header class="eq-about-hero<?php echo $hero_image ? ' has-photo' : ''; ?>">
        <div class="eq-about-hero-copy"><p class="eq-overline">About <?php bloginfo('name'); ?></p>
            <h1><?php echo esc_html(Starter\Theme\eq_text('about_hero_title') ?: get_the_title()); ?></h1>
            <?php if ($intro = Starter\Theme\eq_text('about_hero_intro')) : ?><p><?php echo esc_html($intro); ?></p><?php endif; ?>
            <a class="button" href="<?php echo esc_url(Starter\Theme\contact_url()); ?>">Request a Quote <span aria-hidden="true">→</span></a>
        </div>
        <?php Starter\Theme\eq_photo($hero_image, Starter\Theme\eq_text('about_hero_caption'), 'eq-about-hero-photo', true); ?>
    </header>
    <?php if (Starter\Theme\has_rich_content($story) || $facts) : ?>
    <section class="eq-section"><div class="eq-shell eq-company-intro">
        <div><?php if ($heading = Starter\Theme\eq_text('about_company_title')) : ?><h2><?php echo esc_html($heading); ?></h2><?php endif; ?>
            <?php if (Starter\Theme\has_rich_content($story)) : ?><div class="eq-prose prose"><?php echo $story; // Formatted ACF content. ?></div><?php endif; ?>
        </div>
        <?php if ($facts) : ?><dl class="eq-facts"><?php foreach ($facts as $fact) : ?><div><dt><?php echo esc_html($fact['label']); ?></dt><dd><?php echo esc_html($fact['value']); ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
    </div></section>
    <?php endif; ?>
    <?php if ($steps) : ?><section class="eq-section eq-tone"><div class="eq-shell">
        <?php Starter\Theme\eq_section_head(Starter\Theme\eq_text('about_process_title'), Starter\Theme\eq_text('about_process_intro')); ?>
        <?php Starter\Theme\eq_photo_cards($steps, true); ?>
    </div></section><?php endif; ?>
    <?php $team_title = Starter\Theme\eq_text('about_team_title'); $team_intro = Starter\Theme\eq_text('about_team_intro'); if ($team_title || $team_intro || $team_image) : ?>
    <section class="eq-team-band<?php echo $team_image ? ' has-photo' : ''; ?>">
        <div class="eq-team-copy"><?php if ($team_title) : ?><h2><?php echo esc_html($team_title); ?></h2><?php endif; ?>
            <?php if ($team_intro) : ?><p><?php echo esc_html($team_intro); ?></p><?php endif; ?>
            <a class="button" href="<?php echo esc_url(Starter\Theme\contact_url()); ?>">Request a Quote <span aria-hidden="true">→</span></a>
        </div><?php Starter\Theme\eq_photo($team_image, Starter\Theme\eq_text('about_team_caption')); ?>
    </section><?php endif; ?>
    <?php if ($timeline || $background_image) : ?><section class="eq-section"><div class="eq-shell eq-background<?php echo $background_image ? ' has-photo' : ''; ?>">
        <div><?php Starter\Theme\eq_section_head(Starter\Theme\eq_text('about_background_title'), Starter\Theme\eq_text('about_background_intro')); ?>
            <?php if ($timeline) : ?><ol class="eq-timeline"><?php foreach ($timeline as $row) : ?><li><h3><?php echo esc_html($row['label']); ?></h3><p><?php echo esc_html($row['value']); ?></p></li><?php endforeach; ?></ol><?php endif; ?>
        </div><?php Starter\Theme\eq_photo($background_image, 'Concept photography — company images pending'); ?>
    </div></section><?php endif; ?>
    <?php $sections = Starter\Theme\about_content_sections(); if ($sections) : ?><section class="eq-section eq-shell"><?php Starter\Theme\component_category_sections($sections); ?></section><?php endif; ?>
    <?php Starter\Theme\component_about_gallery(['images' => Starter\Theme\about_gallery_images(), 'title' => Starter\Theme\eq_text('about_gallery_title'), 'intro' => Starter\Theme\eq_text('about_gallery_intro')]); ?>
    <?php if ($faq = Starter\Theme\about_faq_rows()) : ?><section class="eq-section eq-shell"><?php Starter\Theme\eq_section_head(Starter\Theme\eq_text('about_faq_title')); Starter\Theme\component_faq(['rows' => $faq]); ?></section><?php endif; ?>
    <?php if (get_field('about_show_range')) Starter\Theme\eq_family_strip(Starter\Theme\eq_text('about_range_title')); ?>
    <?php Starter\Theme\eq_inquiry_band(Starter\Theme\eq_text('about_inquiry_title'), Starter\Theme\eq_text('about_inquiry_intro'), $background_image); ?>
<?php endwhile; ?></main><?php get_footer(); ?>
