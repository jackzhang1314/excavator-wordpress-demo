<?php
/** Selected Home v1: equipment families, industries, comparison and supply process. */
$home = Starter\Theme\home_v2_data();
?>
<main id="main" class="eq-home">
<section class="eq-hero<?php echo has_post_thumbnail() ? ' has-media' : ''; ?>">
    <?php if (has_post_thumbnail()) : ?><div class="eq-hero-media"><?php the_post_thumbnail('full', ['loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async', 'sizes' => '(max-width:767px) 1200px, 100vw']); ?></div><?php endif; ?>
    <div class="eq-shell eq-hero-inner"><div class="eq-hero-copy">
        <?php if ($home['overline'] !== '') : ?><p class="eq-eyebrow"><?php echo esc_html($home['overline']); ?></p><?php endif; ?>
        <h1><?php the_title(); ?></h1>
        <?php if ($home['description'] !== '') : ?><p class="eq-leader"><?php echo esc_html($home['description']); ?></p><?php endif; ?>
        <?php if ($home['primary'] || $home['secondary']) : ?><div class="eq-actions"><?php Starter\Theme\home_v2_text_link($home['primary'], 'eq-button'); ?><?php Starter\Theme\home_v2_text_link($home['secondary'], 'eq-button eq-button-outline'); ?></div><?php endif; ?>
    </div></div>
</section>
<?php if ($home['ranges']) : ?>
<section class="eq-section eq-ranges"><div class="eq-shell">
    <div class="eq-section-heading"><h2><?php echo esc_html($home['ranges_title']); ?></h2><p><?php echo esc_html($home['ranges_intro']); ?></p><?php Starter\Theme\home_v2_text_link($home['ranges_link'], 'eq-text-link'); ?></div>
    <div class="eq-family-grid"><?php foreach ($home['ranges'] as $range) : ?>
        <a class="eq-family" href="<?php echo esc_url($range['url']); ?>">
            <?php if ($range['image']) : ?><div class="eq-family-photo"><?php echo wp_get_attachment_image($range['image'], 'medium_large', false, ['loading' => 'lazy', 'decoding' => 'async']); ?></div><?php endif; ?>
            <h3><?php echo esc_html($range['title']); ?><span aria-hidden="true">→</span></h3>
        </a>
    <?php endforeach; ?></div>
</div></section>
<?php endif; ?>
<?php if ($home['applications']) : ?>
<section class="eq-section eq-applications"><div class="eq-shell">
    <div class="eq-section-heading"><h2><?php echo esc_html($home['applications_title']); ?></h2><p><?php echo esc_html($home['applications_intro']); ?></p></div>
    <div class="eq-application-grid"><?php foreach ($home['applications'] as $application) : ?>
        <article class="eq-application<?php echo $application['image'] ? ' has-media' : ''; ?>">
            <?php if ($application['image']) echo wp_get_attachment_image($application['image'], 'large', false, ['loading' => 'lazy', 'decoding' => 'async']); ?>
            <div class="eq-application-copy"><h3><?php if ($application['link']) : ?><a href="<?php echo esc_url($application['link']['url']); ?>"<?php echo $application['link']['target'] === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html($application['title']); ?><span aria-hidden="true">→</span></a><?php else : echo esc_html($application['title']); endif; ?></h3>
            <?php if ($application['text'] !== '') : ?><p><?php echo esc_html($application['text']); ?></p><?php endif; ?></div>
        </article>
    <?php endforeach; ?></div>
</div></section>
<?php endif; ?>
<?php if ($home['products']) : ?>
<section class="eq-section eq-products"><div class="eq-shell">
    <div class="eq-section-heading"><h2><?php echo esc_html($home['products_title']); ?></h2><p><?php echo esc_html($home['products_intro']); ?></p><?php Starter\Theme\home_v2_text_link($home['ranges_link'], 'eq-text-link'); ?></div>
    <div class="eq-product-grid"><?php foreach ($home['products'] as $product) : ?>
        <article class="eq-product-card">
            <?php if (has_post_thumbnail($product['id'])) : ?><div class="eq-product-photo"><?php echo get_the_post_thumbnail($product['id'], 'medium_large', ['loading' => 'lazy', 'decoding' => 'async']); ?></div><?php endif; ?>
            <div class="eq-product-copy"><h3><a href="<?php echo esc_url($product['url']); ?>"><?php echo esc_html($product['title']); ?></a></h3>
            <?php if ($product['family'] !== '') : ?><p class="eq-product-family"><?php echo esc_html($product['family']); ?></p><?php endif; ?>
            <?php if ($product['specs']) : ?><dl class="eq-quick-specs"><?php foreach ($product['specs'] as $row) : ?><div><dt><?php echo esc_html($row['label']); ?></dt><dd><?php echo esc_html($row['value']); ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
            </div><a class="eq-product-details" href="<?php echo esc_url($product['url']); ?>">View Details <span aria-hidden="true">→</span><span class="screen-reader-text">: <?php echo esc_html($product['title']); ?></span></a>
        </article>
    <?php endforeach; ?></div>
</div></section>
<?php endif; ?>
<?php if ($home['supply']['enabled']) : $supply = $home['supply']; ?>
<section class="eq-supply<?php echo $supply['image'] ? ' has-media' : ''; ?>">
    <div class="eq-supply-copy">
        <?php if ($supply['eyebrow'] !== '') : ?><p class="eq-eyebrow"><?php echo esc_html($supply['eyebrow']); ?></p><?php endif; ?>
        <?php if ($supply['title'] !== '') : ?><h2><?php echo esc_html($supply['title']); ?></h2><?php endif; ?>
        <?php if (Starter\Theme\has_rich_content($supply['body'])) : ?><div class="eq-supply-intro"><?php echo $supply['body']; // ACF safely formatted exactly once. ?></div><?php endif; ?>
        <?php if ($supply['links']) : ?><ul class="eq-supply-links"><?php foreach ($supply['links'] as $row) : ?><li><span class="eq-line-icon" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M3 26V12l8 5v-5l8 5V6h6v20H3Z"/><path d="M7 21h3m3 0h3m6-12h3m-3 5h3"/></svg></span><div><?php Starter\Theme\home_v2_text_link($row['link'], 'eq-supply-link'); ?><?php if ($row['text'] !== '') : ?><p><?php echo esc_html($row['text']); ?></p><?php endif; ?></div></li><?php endforeach; ?></ul><?php endif; ?>
    </div>
    <?php if ($supply['image']) : ?><div class="eq-supply-photo"><?php echo wp_get_attachment_image($supply['image'], 'large', false, ['loading' => 'lazy', 'decoding' => 'async']); ?></div><?php endif; ?>
</section>
<?php endif; ?>
<?php $cta = $home['cta']; if ($cta['link'] && ($cta['title'] !== '' || $cta['text'] !== '')) : ?>
<section class="eq-cta">
    <?php if ($cta['image']) : ?><div class="eq-cta-image"><?php echo wp_get_attachment_image($cta['image'], 'large', false, ['alt' => '', 'loading' => 'lazy', 'decoding' => 'async']); ?></div><?php endif; ?>
    <div class="eq-shell eq-cta-inner"><div><?php if ($cta['eyebrow'] !== '') : ?><p class="eq-eyebrow"><?php echo esc_html($cta['eyebrow']); ?></p><?php endif; ?><?php if ($cta['title'] !== '') : ?><h2><?php echo esc_html($cta['title']); ?></h2><?php endif; ?><?php if ($cta['text'] !== '') : ?><p><?php echo esc_html($cta['text']); ?></p><?php endif; ?></div><?php Starter\Theme\home_v2_text_link($cta['link'], 'eq-button'); ?></div>
</section>
<?php endif; ?>
</main>
