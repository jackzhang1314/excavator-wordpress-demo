<?php
/** Industry detail — selected reference 08, preserving native inquiry and optional rich details. */
get_header(); ?>
<main id="main" class="eq-page eq-industry-detail"><?php while (have_posts()) : the_post();
$id = (int) get_the_ID();
$industry = Starter\Theme\industry_data();
$constraints = Starter\Theme\eq_pairs('industry_constraints');
$cards = array_map(static fn (array $row): array => ['title' => $row['label'], 'description' => $row['value'], 'image' => Starter\Theme\home_v2_image($row['image'] ?? 0), 'link' => []], $constraints);
$attachments = Starter\Theme\equipment_media_cards('industry_attachments', $id);
$raw = get_field('industry_scenarios', $id);
$scenarios = array_values(array_filter(is_array($raw) ? $raw : [], static fn ($row): bool => is_array($row) && trim((string) ($row['task'] ?? '')) !== '' && trim((string) ($row['equipment'] ?? '')) !== '' && trim((string) ($row['configuration'] ?? '')) !== ''));
$body = Starter\Theme\module_body('industry_body');
$has_body = Starter\Theme\has_rich_content($body);
$planning_image = Starter\Theme\eq_image('industry_planning_image');
$has_planning = $industry['steps'] || $industry['planning_rows'] || $industry['outcome'] !== '' || $planning_image;
$nav = [];
if ($scenarios) {
    foreach ($scenarios as $index => $scenario) {
        $label = trim((string) ($scenario['navigation_label'] ?? ''));
        $nav['industry-task-' . ($index + 1)] = $label !== '' ? $label : $scenario['task'];
    }
} else {
    if ($cards || $industry['challenge'] !== '') $nav['industry-constraints'] = 'Working conditions';
    if ($industry['product_ids'] || $attachments) $nav['application-products'] = 'Equipment combination';
    if ($has_planning) $nav['industry-planning'] = 'Project preparation';
}
?>
<header class="eq-scene-hero">
<?php Starter\Theme\eq_photo(get_post_thumbnail_id(), '', 'eq-scene-background', true); ?>
<div class="eq-shell"><p class="eq-overline">Industry solution</p><h1><?php the_title(); ?></h1>
<?php if (has_excerpt()) : ?><p class="eq-scene-intro"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
<?php if ($industry['cta']['button_label']) : ?><a class="button" href="<?php echo esc_url($industry['show_inquiry'] ? '#product-inquiry' : $industry['cta']['url']); ?>"><?php echo esc_html($industry['cta']['button_label']); ?><span aria-hidden="true">→</span></a><?php endif; ?></div></header>
<?php if ($nav) : ?><nav class="eq-industry-nav<?php echo $scenarios ? ' eq-industry-task-nav' : ''; ?>" aria-label="<?php echo $scenarios ? 'Industry tasks' : 'Industry page sections'; ?>"><div class="eq-shell"><?php foreach ($nav as $anchor => $label) : ?><a href="#<?php echo esc_attr($anchor); ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?></div></nav><?php endif; ?>
<?php if ($cards || $industry['challenge'] !== '') : ?><section id="industry-constraints" class="eq-section"><div class="eq-shell">
<?php Starter\Theme\eq_section_head($industry['constraints_title'], $industry['challenge']); Starter\Theme\eq_photo_cards($cards); ?>
</div></section><?php endif; ?>
<?php if ($industry['product_ids'] || $attachments) : ?><section id="application-products" class="eq-section eq-industry-equipment"><div class="eq-shell">
<?php Starter\Theme\eq_section_head($industry['products_title'], $industry['products_intro']); ?>
<div class="eq-solution-equipment" style="--equipment-count:<?php echo min(4, count($industry['product_ids']) + count($attachments)); ?>">
<?php foreach ($industry['product_ids'] as $product_id) : $family = Starter\Theme\equipment_family_for_product($product_id); ?><article>
<?php Starter\Theme\eq_photo(get_post_thumbnail_id($product_id), '', 'eq-solution-product-photo'); ?><div class="eq-solution-equipment-copy"><h3><?php echo esc_html(get_the_title($product_id)); ?></h3><?php if ($family) : ?><p class="eq-solution-family"><?php echo esc_html($family->name); ?></p><?php endif; ?><p class="eq-solution-status">Configuration: To confirm</p><a class="eq-text-link" href="<?php echo esc_url(get_permalink($product_id)); ?>">View Details <span aria-hidden="true">→</span></a></div></article><?php endforeach; ?>
<?php foreach ($attachments as $attachment) : ?><article><?php Starter\Theme\eq_photo($attachment['image'], '', 'eq-solution-product-photo'); ?><div class="eq-solution-equipment-copy"><h3><?php echo esc_html($attachment['title']); ?></h3><p class="eq-solution-status"><?php echo esc_html($attachment['description']); ?></p><?php if ($attachment['link']) Starter\Theme\home_v2_text_link($attachment['link'], 'eq-text-link'); ?></div></article><?php endforeach; ?>
</div></div></section><?php endif; ?>
<?php if ($scenarios) : ?><section id="industry-scenarios" class="eq-section"><div class="eq-shell">
<?php Starter\Theme\eq_section_head(Starter\Theme\eq_text('industry_scenarios_title'), Starter\Theme\eq_text('industry_scenarios_intro')); ?>
<div class="eq-scroll-table" role="region" aria-label="Application scenarios" tabindex="0"><table class="eq-table"><thead><tr><th scope="col">Task</th><th scope="col">Equipment type</th><th scope="col">Configuration</th></tr></thead><tbody><?php foreach ($scenarios as $index => $row) : ?><tr id="industry-task-<?php echo $index + 1; ?>" tabindex="-1"><th scope="row"><?php echo esc_html($row['task']); ?></th><td><?php echo esc_html($row['equipment']); ?></td><td><?php echo esc_html($row['configuration']); ?></td></tr><?php endforeach; ?></tbody></table></div>
</div></section><?php endif; ?>
<?php if ($industry['modules'] || $has_body) : ?><section class="eq-section eq-shell"><?php Starter\Theme\eq_section_head($industry['applications_title']); Starter\Theme\component_category_sections($industry['modules']); if ($has_body) : ?><article class="prose eq-prose"><?php echo $body; // Formatted canonical rich-text field. ?></article><?php endif; ?></section><?php endif; ?>
<?php if ($has_planning) : ?><section id="industry-planning" class="eq-section eq-tone"><div class="eq-shell eq-industry-planning<?php echo $planning_image ? ' has-photo' : ''; ?>"><div>
<?php Starter\Theme\eq_section_head($industry['planning_title'], $industry['planning_intro']); ?>
<?php if ($industry['steps']) : ?><ol class="eq-preparation-cards"><?php foreach ($industry['steps'] as $index => $row) : ?><li><span aria-hidden="true"><?php echo Starter\Theme\eq_line_icon(['photo','document','quote'][$index % 3]); ?></span><div><h3><?php echo esc_html($row['label']); ?></h3><p><?php echo esc_html($row['value']); ?></p></div></li><?php endforeach; ?></ol><?php endif; ?>
<?php if ($industry['planning_rows']) : ?><dl class="eq-facts"><?php foreach ($industry['planning_rows'] as $row) : ?><div><dt><?php echo esc_html($row['label']); ?></dt><dd><?php echo esc_html($row['value']); ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
<?php if ($industry['outcome']) : ?><p><?php echo esc_html($industry['outcome']); ?></p><?php endif; ?></div>
<?php Starter\Theme\eq_photo($planning_image); ?></div></section><?php endif; ?>
<?php if ($industry['faq']) : ?><section class="eq-section eq-shell"><?php Starter\Theme\eq_section_head($industry['faq_title']); Starter\Theme\component_faq(['rows' => $industry['faq']]); ?></section><?php endif; ?>
<?php if ($industry['show_inquiry']) : ?><section class="eq-section eq-industry-inquiry"><div class="eq-shell"><?php Starter\Theme\component_product_inquiry(['title' => $industry['cta']['title'], 'description' => $industry['cta']['description'], 'url' => $industry['cta']['url']]); ?></div></section><?php endif; ?>
<?php endwhile; ?></main><?php get_footer(); ?>
