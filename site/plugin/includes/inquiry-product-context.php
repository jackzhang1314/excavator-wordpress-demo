<?php
/** Default inquiry context for the existing managed RFQ, using native product IDs. */
namespace Starter\Model;
defined('ABSPATH') || exit;

/** One shortcode render can request the compact product layout without changing saved defaults. */
final class EquipmentInquiryRenderContext {
    private static array $variants = [];

    public static function current(): string {
        return self::$variants ? (string) end(self::$variants) : '';
    }

    public static function render(string $shortcode, string $variant): string {
        self::$variants[] = $variant;
        try {
            return do_shortcode($shortcode);
        } finally {
            array_pop(self::$variants);
        }
    }
}

/** Fluent Forms already gives each instance its own controller class; keep its HTML IDs unique too. */
function equipment_inquiry_unique_ids(string $html, string $form_id): string {
    if (!is_singular('starter_product') || !class_exists('WP_HTML_Tag_Processor')) return $html;
    $processor = new \WP_HTML_Tag_Processor($html);
    $instance = '';
    while ($processor->next_tag()) {
        if ($processor->get_tag() === 'FORM' && (string) $processor->get_attribute('data-form_id') === $form_id) {
            $instance = sanitize_html_class((string) $processor->get_attribute('data-form_instance'));
        }
        $id = (string) $processor->get_attribute('id');
        if ($instance !== '' && in_array($id, ['fluentform_' . $form_id, '_fluentform_' . $form_id . '_fluentformnonce', 'fluentform_' . $form_id . '_errors'], true)) {
            // Keep the native numeric instance suffix on the form itself so
            // Fluent Forms integrations can recognise each rendered form.
            $suffix = preg_match('/_(\d+)$/D', $instance, $match) ? $match[1] : '';
            $unique_id = $id === 'fluentform_' . $form_id && $suffix !== ''
                ? $id . '_' . $suffix : $id . '-' . $instance;
            $processor->set_attribute('id', $unique_id);
        }
    }
    return $processor->get_updated_html();
}

/** Request context supplies a default to the native form; it is never saved globally. */
function equipment_inquiry_variant(): string {
    if (EquipmentInquiryRenderContext::current() !== '') return EquipmentInquiryRenderContext::current();
    if (is_singular('starter_product')) return 'product';
    if (is_singular('starter_industry')) return 'site';
    if (is_home()) return 'consultation';
    foreach (['certifications' => 'documents', 'parts-components' => 'parts', 'oem-solution' => 'oem', 'dealer-opportunity' => 'dealer', 'contact' => 'contact'] as $template => $variant) {
        if (is_page_template('page-templates/' . $template . '.php')) return $variant;
    }
    return 'general';
}

function equipment_inquiry_product_id(): int {
    $id = is_singular('starter_product') ? (int) get_queried_object_id() : 0;
    if (!$id && is_page_template('page-templates/contact.php') && isset($_GET['product_id']) && is_scalar($_GET['product_id'])) $id = absint(wp_unslash($_GET['product_id']));
    return $id && get_post_type($id) === 'starter_product' && get_post_status($id) === 'publish' ? $id : 0;
}

/** Options come from published native products and their reviewed menu labels. */
function equipment_inquiry_product_options(): array {
    $labels = [];
    $locations = get_nav_menu_locations();
    if (!empty($locations['primary'])) foreach (wp_get_nav_menu_items($locations['primary']) ?: [] as $item) {
        if ($item->type === 'post_type' && $item->object === 'starter_product') $labels[(int) $item->object_id] = wp_specialchars_decode((string) $item->title, ENT_QUOTES);
    }
    $options = []; $map = [];
    foreach (get_posts(['post_type' => 'starter_product', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => ['menu_order' => 'ASC', 'ID' => 'ASC']]) as $product) {
        $terms = get_the_terms($product->ID, 'product_collection');
        $category = is_array($terms) && $terms ? (string) $terms[0]->name : '';
        $label = ($labels[$product->ID] ?? get_the_title($product->ID)) . ($category !== '' ? ' · ' . $category : '');
        // A native ID suffix disambiguates only duplicate labels; it is a reference, never a specification.
        $value = isset($map[$label]) ? $label . ' · Reference ' . $product->ID : $label;
        $options[] = ['label' => $value, 'value' => $value, 'calc_value' => ''];
        $map[$value] = (int) $product->ID;
    }
    return [$options, $map];
}

function equipment_inquiry_context(array $field, $form): array {
    if ((int) ($form->id ?? 0) !== (int) get_option('starter_rfq_form_id')) return $field;
    $name = (string) ($field['attributes']['name'] ?? '');
    if ($name === 'eq_inquiry_type') {
        $field['attributes']['value'] = equipment_inquiry_variant();
        return $field;
    }
    if (!in_array($name, ['product', 'eq_product_id'], true)) return $field;
    $id = equipment_inquiry_product_id();
    if (!$id) {
        if ($name === 'product' && is_page_template('page-templates/contact.php') && !isset($_GET['product_id']) && isset($_GET['product']) && is_string($_GET['product'])) {
            $field['attributes']['value'] = wp_html_excerpt(sanitize_text_field(wp_unslash($_GET['product'])), 200, '');
        }
        return $field;
    }
    if ($name === 'eq_product_id') $field['attributes']['value'] = (string) $id;
    else {
        $terms = get_the_terms($id, 'product_collection');
        $category = is_array($terms) && $terms ? (string) $terms[0]->name : '';
        $field['attributes']['value'] = get_the_title($id) . ($category !== '' ? ' · ' . $category : '');
    }
    return $field;
}
add_filter('fluentform/rendering_form', static function (object $form): object {
    if ((int) ($form->id ?? 0) !== (int) get_option('starter_rfq_form_id')) return $form;
    $definition = is_string($form->form_fields ?? null) ? json_decode($form->form_fields, true) : null;
    if (!is_array($definition)) return $form;
    $walk = static function (array $node) use (&$walk, $form): array {
        if (isset($node['element'], $node['attributes'])) $node = equipment_inquiry_context($node, $form);
        foreach ($node as $key => $value) if (is_array($value)) $node[$key] = $walk($value);
        return $node;
    };
    $definition = $walk($definition);
    $variant = equipment_inquiry_variant();
    $use_product_select = $variant === 'contact';
    if ($variant === 'product') {
        $terms = get_the_terms(equipment_inquiry_product_id(), 'product_collection');
        foreach (is_array($terms) ? $terms : [] as $term) {
            $slugs = array_merge([$term->slug], array_map(static fn(int $id): string => (string) get_term($id, 'product_collection')->slug, get_ancestors($term->term_id, 'product_collection', 'taxonomy')));
            if (array_intersect($slugs, ['wheel-loader', 'backhoe-loader', 'skid-steer-loader'])) $use_product_select = true;
        }
    }
    if ($use_product_select) {
        [$options, $map] = equipment_inquiry_product_options();
        foreach ($definition['fields'] as &$field) if (($field['attributes']['name'] ?? '') === 'product') {
            $selected = array_search(equipment_inquiry_product_id(), $map, true);
            $requested = (string) ($field['attributes']['value'] ?? '');
            if ($selected === false && $requested !== '') { array_unshift($options, ['label' => $requested, 'value' => $requested, 'calc_value' => '']); $map[$requested] = 0; }
            $field['element'] = 'select';
            unset($field['attributes']['type'], $field['attributes']['maxlength']);
            $field['attributes']['value'] = $selected !== false ? $selected : $requested;
            $field['attributes']['data-equipment-products'] = wp_json_encode($map);
            $field['settings']['advanced_options'] = $options;
            $field['settings']['placeholder'] = 'Select equipment or model';
            $field['settings']['enable_select_2'] = 'no';
            $field['settings']['enable_option_groups'] = 'no';
        }
        unset($field);
    }
    if ($variant === 'contact') {
        $order = array_flip(['eq_inquiry_type', 'full_name', 'company', 'email', 'phone', 'country', 'product', 'message', 'marketing_updates', 'inquiry_attachments', 'gdpr_note', 'eq_product_id']);
        $ordered = []; $other = [];
        foreach ($definition['fields'] as $field) { $name = $field['attributes']['name'] ?? ''; if (isset($order[$name])) $ordered[$order[$name]] = $field; else $other[] = $field; }
        ksort($ordered); $definition['fields'] = array_merge(array_values($ordered), $other);
    }
    if (in_array(equipment_inquiry_variant(), ['product', 'product-compact'], true)) {
        // The product interest leads both product forms, as in the selected designs.
        $product_fields = array_values(array_filter($definition['fields'], static fn(array $field): bool => ($field['attributes']['name'] ?? '') === 'product'));
        $other_fields = array_values(array_filter($definition['fields'], static fn(array $field): bool => ($field['attributes']['name'] ?? '') !== 'product'));
        $definition['fields'] = array_merge($product_fields, $other_fields);
    }
    // Fluent Forms keeps both serialized and parsed render copies. Neither is
    // persisted, so this visitor's product never becomes a saved form default.
    $form->form_fields = wp_json_encode($definition);
    $form->fields = $definition;
    return $form;
}, 30, 1);

add_action('wp_enqueue_scripts', static function (): void {
    if (!is_singular('starter_product') && !is_page_template('page-templates/contact.php')) return;
    wp_enqueue_script('equipment-inquiry-context', plugins_url('assets/js/inquiry-context.js', dirname(__DIR__) . '/starter-model.php'), [], (string) filemtime(dirname(__DIR__) . '/assets/js/inquiry-context.js'), true);
});
