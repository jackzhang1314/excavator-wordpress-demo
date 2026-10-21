<?php
/** Explicit demo upgrade: preserve the old rendered layout as a native template choice once. */
function excavator_demo_migrate_category_layouts(): void {
    if (get_option('excavator_demo_category_layout_schema') === '1') return;
    global $wpdb;
    $ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type='starter_category' AND post_status <> 'auto-draft'");
    foreach ($ids as $id) {
        $template = get_post_meta((int) $id, '_wp_page_template', true);
        if (!in_array($template, ['', 'default'], true)) continue;
        $term_id = (int) get_post_meta((int) $id, 'category_collection', true);
        $parent = $wpdb->get_var($wpdb->prepare("SELECT parent FROM {$wpdb->term_taxonomy} WHERE taxonomy='product_collection' AND term_id=%d", $term_id));
        if ($parent === null) continue;
        $target = (int) $parent > 0 ? 'page-templates/category-selection.php' : 'default';
        update_post_meta((int) $id, '_wp_page_template', $target);
        if (get_post_meta((int) $id, '_wp_page_template', true) !== $target) throw new RuntimeException('Category template upgrade incomplete: ' . (int) $id);
    }
    update_option('excavator_demo_category_layout_schema', '1', false);
}
