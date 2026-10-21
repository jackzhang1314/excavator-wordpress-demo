<?php
/** Two approved designs selected by Core template, independently of category depth. */
$context = get_query_var('category_template_context');
$product_query = $context['product_query'];
$category = $context['category'];
$term = Starter\Theme\category_current_term();
$selection = ($context['layout'] ?? 'standard') === 'selection';
$children = $term ? Starter\Theme\equipment_families((int) $term->term_id) : [];
$facets = Starter\Theme\equipment_category_filters($term);
$active = Starter\Theme\equipment_active_filters();
$guide = Starter\Theme\rich_heading_index($category['long_description'], 'guide-');
$hero_image = (int) ($category['hero_image']['id'] ?? 0);
?>
<main id="main" class="equipment-main equipment-category category-standard<?php echo $selection ? ' is-secondary' : ' is-primary'; ?>" data-category-depth="<?php echo $term ? (int) Starter\Model\category_tree_context($term)['depth'] : 0; ?>">
<section class="equipment-hero<?php echo $hero_image ? ' has-media' : ''; ?>">
    <?php if ($hero_image) : ?><div class="equipment-hero-photo"><?php echo wp_get_attachment_image($hero_image, 'full', false, ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width:767px) 100vw, 1440px']); ?></div><?php endif; ?>
    <div class="equipment-shell equipment-hero-inner"><div class="equipment-hero-copy">
        <?php Starter\Theme\breadcrumbs(); ?><h1><?php echo esc_html($category['name']); ?></h1>
        <?php if ($category['intro']) : ?><p><?php echo esc_html($category['intro']); ?></p><?php endif; ?>
        <?php if (!$selection) : ?><a class="button" href="<?php echo esc_url($category['cta']['url']); ?>"><?php echo esc_html($category['cta']['button_label']); ?> <span aria-hidden="true">→</span></a><?php endif; ?>
    </div></div>
</section>
<?php if ($children && !is_paged()) : ?>
<section class="equipment-section equipment-shell equipment-children">
    <div class="equipment-section-heading"><h2>Explore <?php echo esc_html(strtolower($category['name'])); ?> families</h2><p>Choose a range, then compare the information available for each model.</p></div>
    <div class="equipment-child-grid"><?php foreach ($children as $child) : ?>
    <article class="equipment-child"><h3><a href="<?php echo esc_url($child['url']); ?>"><?php echo esc_html($child['name']); ?></a></h3>
        <?php if ($child['intro']) : ?><p><?php echo esc_html($child['intro']); ?></p><?php endif; ?>
        <?php if ($child['image']) : ?><a class="equipment-child-photo" href="<?php echo esc_url($child['url']); ?>" tabindex="-1" aria-hidden="true"><?php echo wp_get_attachment_image($child['image'], 'medium_large', false, ['loading' => 'lazy']); ?></a><?php endif; ?>
        <a class="equipment-text-link" href="<?php echo esc_url($child['url']); ?>">Explore Range <span aria-hidden="true">→</span></a>
    </article>
    <?php endforeach; ?></div>
</section>
<?php endif; ?>
<?php if ($category['show_products']) : ?>
<section id="products" class="equipment-section equipment-shell equipment-listing<?php echo $selection ? ' with-filters' : ''; ?>">
    <?php if (!$selection) : ?><div class="equipment-section-heading"><h2>Find a machine for your site</h2><p>Compare model information and discuss the configuration for your project.</p></div><?php endif; ?>
    <?php if ($facets) : ?><form class="equipment-filters" action="<?php echo esc_url(get_permalink(get_queried_object_id()) . '#products'); ?>" method="get"<?php echo $selection ? ' data-equipment-auto-filter' : ''; ?>>
        <?php if ($selection) : ?><h2>Filter</h2><?php endif; ?>
        <?php foreach ($facets as $key => $facet) : ?>
        <?php if ($selection) : ?><details class="equipment-filter equipment-filter-radios" open><summary><?php echo esc_html($facet['label']); ?></summary><fieldset><legend class="screen-reader-text"><?php echo esc_html($facet['label']); ?></legend>
        <label><input type="radio" name="eq_spec[<?php echo esc_attr($key); ?>]" value="" <?php checked(($active[$key] ?? '') === ''); ?>><span>All</span></label>
        <?php foreach ($facet['values'] as $value) : ?><label><input type="radio" name="eq_spec[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>" <?php checked(($active[$key] ?? '') === $value); ?>><span data-equipment-option="<?php echo esc_attr($value); ?>"><?php echo esc_html($value); ?></span></label><?php endforeach; ?>
        <?php if (!$facet['values']) : ?><label class="is-unconfirmed"><input type="radio" disabled aria-label="<?php echo esc_attr($facet['label'] . ': data to confirm'); ?>"><span>To confirm</span></label><?php endif; ?>
        </fieldset></details>
        <?php else : ?><div class="equipment-filter"><label for="eq-filter-<?php echo esc_attr($key); ?>"><?php echo esc_html($facet['label']); ?></label>
        <select id="eq-filter-<?php echo esc_attr($key); ?>" name="eq_spec[<?php echo esc_attr($key); ?>]"<?php disabled(!$facet['values']); ?>><option value="">All values</option><?php foreach ($facet['values'] as $value) : ?><option data-equipment-option="<?php echo esc_attr($value); ?>" value="<?php echo esc_attr($value); ?>" <?php selected($active[$key] ?? '', $value); ?>><?php echo esc_html($value); ?></option><?php endforeach; ?></select>
        <?php if (!$facet['values']) : ?><small>Data to confirm</small><?php endif; ?></div><?php endif; endforeach; ?>
        <button class="button" type="submit" data-equipment-apply>Search Machines <span aria-hidden="true">→</span></button>
        <?php if ($active) : ?><a class="equipment-reset" href="<?php echo esc_url(get_permalink(get_queried_object_id())); ?>#products">Reset filters</a><?php endif; ?>
    </form><?php endif; ?>
    <div class="equipment-list-results"><h2 class="screen-reader-text">Available models</h2><div class="equipment-list-toolbar"><p class="equipment-result-count"><?php echo esc_html((string) $product_query->found_posts); ?> <?php echo $product_query->found_posts === 1 ? 'model' : 'models'; ?></p><div class="equipment-selection-tools" data-equipment-tools hidden><div role="group" aria-label="Measurement units"><button type="button" data-unit="metric" aria-pressed="true">Metric</button><button type="button" data-unit="imperial" aria-pressed="false">Imperial</button></div><button type="button" data-compare-open disabled>Compare (0)</button><p class="screen-reader-text" data-compare-status aria-live="polite">Select up to 3 models to compare.</p></div></div>
    <div class="equipment-model-grid"><?php if ($product_query->have_posts()) : while ($product_query->have_posts()) : $product_query->the_post(); get_template_part('parts/equipment-card', null, ['spec_limit' => $selection ? 5 : 3, 'spec_fields' => $selection ? array_slice($facets, 0, 5, true) : null]); endwhile; wp_reset_postdata(); else : ?><p>No matching models. Reset the filters or <a href="<?php echo esc_url($category['cta']['url']); ?>">share your requirements</a>.</p><?php endif; ?></div>
    <?php if ($product_query->max_num_pages > 1) : ?><nav class="navigation pagination" aria-label="Models pagination"><?php echo wp_kses_post(paginate_links(['total' => $product_query->max_num_pages, 'current' => max(1, (int) get_query_var('paged')), 'add_args' => $active ? ['eq_spec' => $active] : [], 'prev_text' => '←', 'next_text' => '→'])); ?></nav><?php endif; ?>
    </div>
</section>
<dialog class="equipment-compare-dialog" data-compare-dialog aria-labelledby="equipment-compare-heading"><header><h2 id="equipment-compare-heading">Compare equipment</h2><button type="button" data-compare-close aria-label="Close equipment comparison">×</button></header><p>Values come from each model. Unconfirmed specifications remain unconfirmed.</p><div class="equipment-compare-table" data-compare-content tabindex="0" role="region" aria-label="Equipment comparison table"></div></dialog>
<?php endif; ?>
<?php if (!is_paged()) : ?>
<div class="equipment-shell equipment-content-sections"><?php Starter\Theme\component_category_sections($category['content_sections']); ?></div>
<?php if ($category['long_description'] || $category['specifications'] || $category['attribute_tables'] || $category['faq']) : ?>
<section id="category-information" class="equipment-section equipment-shell equipment-guide-layout">
    <div class="equipment-guide-body"><?php if ($category['long_description']) : ?><section id="buying-guide"><h2><?php echo esc_html($category['guide_title']); ?></h2><article class="prose equipment-editorial"><?php echo $guide['content']; // Formatted once by ACF, including full media and tables. ?></article></section><?php endif; ?>
        <?php if ($category['specifications'] || $category['attribute_tables']) : ?><section class="equipment-section"><h2>Category attributes</h2><?php Starter\Theme\component_spec_table(['rows' => $category['specifications']]); ?><?php foreach ($category['attribute_tables'] as $group) : ?><h3><?php echo esc_html($group['title']); ?></h3><?php Starter\Theme\component_spec_table(['rows' => $group['rows']]); endforeach; ?></section><?php endif; ?>
        <?php if ($category['faq']) : ?><section id="category-faq" class="equipment-section"><h2>Frequently asked questions</h2><?php Starter\Theme\component_faq(['rows' => $category['faq']]); ?></section><?php endif; ?>
    </div>
    <aside class="equipment-guide-sidebar">
        <?php if ($guide['items']) : ?><nav class="equipment-guide-nav" aria-label="In this guide"><h2>In this guide</h2><ol><?php foreach ($guide['items'] as $item) : ?><li class="level-<?php echo (int) $item['level']; ?>"><a href="#<?php echo esc_attr($item['id']); ?>"><?php echo esc_html($item['text']); ?></a></li><?php endforeach; ?></ol></nav><?php endif; ?>
        <div class="equipment-quote-card"><h2><?php echo esc_html($category['cta']['title']); ?></h2><p><?php echo esc_html($category['cta']['description']); ?></p><a class="button" href="<?php echo esc_url($category['cta']['url']); ?>"><?php echo esc_html($category['cta']['button_label']); ?> <span aria-hidden="true">→</span></a></div>
    </aside>
</section>
<?php endif; ?>
<?php if ($selection) : $families = Starter\Theme\equipment_families(); if ($families) : ?><section class="equipment-section equipment-shell equipment-related-families"><div class="equipment-section-heading"><h2>Related equipment</h2><a class="equipment-text-link" href="<?php echo esc_url(get_post_type_archive_link('starter_product')); ?>">View all equipment →</a></div><div class="equipment-family-strip"><?php foreach ($families as $family) : ?><a href="<?php echo esc_url($family['url']); ?>"><?php if ($family['image']) echo wp_get_attachment_image($family['image'], 'medium', false, ['loading' => 'lazy']); ?><strong><?php echo esc_html($family['name']); ?></strong><span aria-hidden="true">→</span></a><?php endforeach; ?></div></section><?php endif; endif; ?>
<?php Starter\Theme\equipment_cta($category['cta']); ?>
<?php endif; ?>
</main>
<?php
$items = [];
foreach (($category['show_products'] ? ($product_query->posts ?? []) : []) as $index => $post) $items[] = ['@type' => 'ListItem', 'position' => (max(1, (int) get_query_var('paged')) - 1) * (int) $product_query->get('posts_per_page') + $index + 1, 'url' => get_permalink($post), 'name' => get_the_title($post)];
if ($items) : ?><script type="application/ld+json"><?php echo wp_json_encode(['@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => $category['name'], 'itemListElement' => $items], JSON_HEX_TAG | JSON_UNESCAPED_SLASHES); ?></script><?php endif; ?>
