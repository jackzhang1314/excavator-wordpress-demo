<?php
/** Product identity and specifications have one owner: the product record. */
$id = (int) ($args['id'] ?? get_the_ID());
$rows = Starter\Theme\equipment_quick_specs($id, (int) ($args['spec_limit'] ?? 3));
if (is_array($args['spec_fields'] ?? null)) {
    $saved = [];
    foreach (Starter\Theme\equipment_filter_specs($id) as $row) $saved[Starter\Theme\equipment_spec_key($row['label'])] = $row['value'];
    $rows = [];
    foreach ($args['spec_fields'] as $key => $facet) $rows[] = ['label' => $facet['label'], 'value' => $saved[$key] ?? 'To confirm'];
}
$tag = ($args['title_tag'] ?? 'h3') === 'h2' ? 'h2' : 'h3';
$url = get_permalink($id);
$family = Starter\Theme\equipment_family_for_product($id);
$compare = ['id' => $id, 'title' => get_the_title($id), 'family' => $family ? $family->name : '', 'url' => $url, 'specs' => Starter\Theme\equipment_quick_specs($id)];
?>
<article class="equipment-model-card">
    <?php if (has_post_thumbnail($id)) : ?><a class="equipment-model-photo" href="<?php echo esc_url($url); ?>" tabindex="-1" aria-hidden="true"><?php echo get_the_post_thumbnail($id, 'medium_large', ['loading' => 'lazy', 'decoding' => 'async']); ?></a><?php endif; ?>
    <div class="equipment-model-copy"><<?php echo $tag; ?>><a href="<?php echo esc_url($url); ?>"><?php echo esc_html(get_the_title($id)); ?></a></<?php echo $tag; ?>>
    <?php if ($rows) : ?><dl><?php foreach ($rows as $row) : ?><div><dt><?php echo esc_html($row['label']); ?></dt><dd data-equipment-value="<?php echo esc_attr($row['value']); ?>"><?php echo esc_html($row['value']); ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
    <?php if (is_singular('starter_category') || is_tax('product_collection')) : ?><label class="equipment-compare-choice" data-compare-choice hidden><input type="checkbox" data-compare-product="<?php echo esc_attr(wp_json_encode($compare)); ?>"> Compare <span class="screen-reader-text"><?php echo esc_html(get_the_title($id) . ($family ? ' · ' . $family->name : '')); ?></span></label><?php endif; ?>
    <a class="equipment-model-link" href="<?php echo esc_url($url); ?>">View Details <span aria-hidden="true">→</span><span class="screen-reader-text">: <?php echo esc_html(get_the_title($id)); ?></span></a></div>
</article>
