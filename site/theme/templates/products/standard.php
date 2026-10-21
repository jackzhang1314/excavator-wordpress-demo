<?php
/** Selected 05/06 product designs. Every variant renders the same full Description field. */
$context = get_query_var('product_template_context');
$product = $context['product'];
$id = (int) get_the_ID();
$family = Starter\Theme\equipment_family_for_product($id);
$is_loader = $family && in_array($family->slug, ['wheel-loader', 'backhoe-loader', 'skid-steer-loader'], true);
$overview = Starter\Theme\equipment_media_cards('product_overview_cards', $id);
$attachments = Starter\Theme\equipment_media_cards('product_attachments', $id);
$configurations = Starter\Theme\product_table_groups('product_configuration_tables', $id, 'Configuration');
$has_compact_inquiry = !$is_loader && (bool) $configurations;
$quote_anchor = $has_compact_inquiry ? '#configuration-inquiry' : '#product-inquiry';
$spec_image = Starter\Theme\home_v2_image(get_field('product_specification_image', $id));
$specifications = Starter\Theme\normalize_attribute_rows($product['specifications']);
$guide = Starter\Theme\rich_heading_index($product['details']['content'], 'description-');
$has_specs = $specifications || $product['attribute_tables'] || $configurations || $product['packaging_tables'] || $product['lead_times'] || $product['certificates'];
$links = [];
if ($overview || $product['services']) $links[] = ['label' => 'Overview', 'url' => '#product-overview'];
if ($has_specs) $links[] = ['label' => 'Specifications', 'url' => '#product-specifications'];
if ($product['details']['has_content']) $links[] = ['label' => 'Description', 'url' => '#product-details'];
if ($attachments) $links[] = ['label' => 'Attachments', 'url' => '#product-attachments'];
if ($product['faq']) $links[] = ['label' => 'FAQ', 'url' => '#product-questions'];
$links[] = ['label' => 'Request a Quote', 'url' => $quote_anchor];
?>
<main id="main" class="equipment-main equipment-product product-page product-default<?php echo $is_loader ? ' is-loader' : ' is-excavator'; ?>">
<div class="equipment-shell equipment-product-top">
    <div class="equipment-product-breadcrumbs"><?php Starter\Theme\breadcrumbs(); ?></div>
    <section class="equipment-product-hero" aria-label="Product overview">
        <?php Starter\Theme\component_product_gallery(['images' => $product['gallery'], 'title' => $product['title']]); ?>
        <div class="equipment-product-summary">
            <?php if ($is_loader && $family) : ?><p class="equipment-product-eyebrow"><?php echo esc_html($family->name); ?></p><?php endif; ?>
            <h1><?php echo esc_html($product['title']); ?></h1>
            <?php if ($product['category']) : ?><p class="equipment-product-family"><a href="<?php echo esc_url($product['category']['url']); ?>"><?php echo esc_html($product['category']['name']); ?></a></p><?php endif; ?>
            <?php if ($product['excerpt']) : ?><p class="equipment-product-intro"><?php echo esc_html($product['excerpt']); ?></p><?php endif; ?>
            <?php if ($product['value_chips']) : ?><dl class="equipment-product-key-specs"><?php foreach ($product['value_chips'] as $row) : ?><div><dt><?php echo esc_html($row['label']); ?></dt><dd><?php echo esc_html($row['value']); ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
            <a class="button equipment-product-quote" href="<?php echo esc_url($quote_anchor); ?>"><?php echo esc_html($product['cta']['label']); ?> <span aria-hidden="true">→</span></a>
            <?php if ($product['cta']['note']) : ?><p class="equipment-product-note"><?php echo esc_html($product['cta']['note']); ?></p><?php endif; ?>
        </div>
    </section>
</div>
<nav class="equipment-product-nav" aria-label="Product sections"><div class="equipment-shell"><?php foreach ($links as $link) : ?><a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a><?php endforeach; ?></div></nav>
<?php if ($overview || $product['services']) : ?><section id="product-overview" class="equipment-section equipment-shell equipment-product-overview">
    <?php $overview_title = trim((string) get_field('product_overview_title', $id)); if ($overview_title) : ?><h2><?php echo esc_html($overview_title); ?></h2><?php elseif ($overview) : ?><h2 class="screen-reader-text">Product overview</h2><?php endif; ?>
    <?php if ($overview) : ?><div class="equipment-overview-grid"><?php foreach ($overview as $card) : ?><article><?php if ($card['image']) echo wp_get_attachment_image($card['image'], 'large', false, ['loading' => 'lazy']); ?><h3><?php echo esc_html($card['title']); ?></h3><?php if ($card['description']) : ?><p><?php echo esc_html($card['description']); ?></p><?php endif; ?></article><?php endforeach; ?></div><?php endif; ?>
    <?php if ($product['services']) : ?><section class="equipment-product-services"><h2>Services</h2><?php Starter\Theme\component_faq(['rows' => $product['services']]); ?></section><?php endif; ?>
</section><?php endif; ?>
<?php if ($has_specs) : ?><section id="product-specifications" class="equipment-section equipment-shell equipment-technical">
    <h2>Technical specifications</h2>
    <div class="equipment-technical-tables<?php echo $spec_image ? ' has-spec-media' : ''; ?>" style="--spec-table-count:<?php echo max(1, count($product['attribute_tables']) + ($specifications ? 1 : 0)); ?>">
        <?php if ($specifications) : ?><section><h3>General</h3><?php Starter\Theme\component_spec_table(['rows' => $specifications]); ?></section><?php endif; ?>
        <?php foreach ($product['attribute_tables'] as $group) : ?><section><h3><?php echo esc_html($group['title']); ?></h3><?php Starter\Theme\component_spec_table(['rows' => $group['rows']]); ?></section><?php endforeach; ?>
        <?php if ($spec_image) : ?><figure class="equipment-spec-photo"><?php echo wp_get_attachment_image($spec_image, 'large', false, ['loading' => 'lazy']); ?><?php if ($caption = trim((string) get_field('product_specification_caption', $id))) : ?><figcaption><?php echo esc_html($caption); ?></figcaption><?php endif; ?></figure><?php endif; ?>
    </div>
    <?php if ($configurations) : ?><div class="equipment-configuration-layout<?php echo $has_compact_inquiry ? ' has-inquiry' : ''; ?>"><section class="equipment-configurations"><h2>Standard and optional configuration</h2><div><?php foreach ($configurations as $group) : ?><section><h3><?php echo esc_html($group['title']); ?></h3><?php Starter\Theme\component_spec_table(['rows' => $group['rows']]); ?></section><?php endforeach; ?></div></section><?php if ($has_compact_inquiry) Starter\Theme\component_product_inquiry(['id' => 'configuration-inquiry', 'variant' => 'product-compact', 'title' => 'Request a Quote', 'description' => 'Tell us about your project and our team will get back to you with relevant information.', 'url' => $product['cta']['url']]); ?></div><?php endif; ?>
    <?php if ($product['packaging_tables'] || $product['lead_times'] || $product['certificates']) : ?><div class="equipment-logistics"><?php foreach ($product['packaging_tables'] as $group) : ?><section><h3><?php echo esc_html($group['title']); ?></h3><?php Starter\Theme\component_spec_table(['rows' => $group['rows']]); ?></section><?php endforeach; ?><?php Starter\Theme\component_product_lead_times(['rows' => $product['lead_times']]); Starter\Theme\component_product_certificates(['rows' => $product['certificates']]); ?></div><?php endif; ?>
</section><?php endif; ?>
<?php if ($product['factory_card']) : ?><div class="equipment-shell equipment-product-factory"><?php Starter\Theme\component_product_factory($product['factory_card']); ?></div><?php endif; ?>
<div class="equipment-shell equipment-product-reading product-reading-layout<?php echo !$product['details']['has_content'] && !$product['faq'] ? ' no-guide' : ''; ?>">
    <?php if ($product['details']['has_content'] || $product['faq']) : ?><div class="equipment-guide-body product-reading">
        <?php if ($product['details']['has_content']) : ?><section id="product-details"><h2><?php echo esc_html($product['details']['title']); ?></h2><article class="prose equipment-editorial"><?php echo $guide['content']; // Full canonical ACF WYSIWYG, formatted exactly once. ?></article></section><?php endif; ?>
        <?php if ($product['faq']) : ?><section id="product-questions" class="equipment-section"><h2>Frequently asked questions</h2><?php Starter\Theme\component_faq(['rows' => $product['faq']]); ?></section><?php endif; ?>
    </div><?php endif; ?>
    <?php Starter\Theme\component_product_inquiry(['equipment' => true, 'contents' => $product['details']['has_content'] ? $guide['items'] : [], 'title' => 'Request a Quote', 'description' => $product['title'] . ($product['category'] ? ' · ' . $product['category']['name'] : ''), 'url' => $product['cta']['url'], 'note' => $product['cta']['note']]); ?>
</div>
<?php if ($attachments) : ?><section id="product-attachments" class="equipment-section equipment-shell equipment-product-attachments"><h2>Attachments and implements</h2><div class="equipment-overview-grid"><?php foreach ($attachments as $card) : ?><article><?php if ($card['image']) echo wp_get_attachment_image($card['image'], 'medium_large', false, ['loading' => 'lazy']); ?><h3><?php if ($card['link']) : ?><a href="<?php echo esc_url($card['link']['url']); ?>"><?php echo esc_html($card['title']); ?></a><?php else : echo esc_html($card['title']); endif; ?></h3><?php if ($card['description']) : ?><p><?php echo esc_html($card['description']); ?></p><?php endif; ?></article><?php endforeach; ?></div></section><?php endif; ?>
<section class="equipment-section equipment-shell equipment-related-models"><?php Starter\Theme\component_related_products(['post_id' => $id, 'related_ids' => $product['related_ids'], 'equipment' => true]); ?></section>
<?php Starter\Theme\equipment_cta(['title' => 'Talk to our equipment specialists', 'description' => 'Share your application and the information needed for a quotation.', 'url' => $product['cta']['url'], 'button_label' => $product['cta']['label']]); ?>
</main>
