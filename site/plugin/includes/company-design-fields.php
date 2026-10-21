<?php
/** Editable modules for the selected company-page design. */
namespace Starter\Model;
defined('ABSPATH') || exit;

/** Keep native clears consistent with the declared REST schema of our fields. */
function normalize_owned_empty_rest_value($formatted, $post_id, array $field, $raw, string $format) {
    $key = (string) ($field['key'] ?? '');
    $owned = false;
    foreach (['field_eq_', 'field_home_', 'field_about_', 'field_tc_', 'field_i_', 'field_p_', 'field_f_', 'field_contact_', 'field_starter_author_'] as $prefix) {
        if (str_starts_with($key, $prefix)) { $owned = true; break; }
    }
    // Formatting only: preserve saved metadata, populated media/relations and numeric 0.
    return $owned && $format === 'light' && in_array($formatted, ['', false, null], true) ? null : $formatted;
}
foreach (['link', 'image', 'file', 'gallery', 'relationship', 'post_object'] as $type) {
    add_filter('acf/rest/format_value_for_rest/type=' . $type, __NAMESPACE__ . '\\normalize_owned_empty_rest_value', 10, 5);
}

function company_card_field(callable $field, string $key, string $name, string $label, int $max = 8): array {
    return $field($key, $name, $label, 'repeater', [
        'layout' => 'block', 'max' => $max, 'button_label' => 'Add card', 'collapsed' => $key . '_title',
        'instructions' => 'Add, remove and drag to reorder. A title and either an image or description are required. Empty cards and sections are hidden.',
        'sub_fields' => [
            $field($key . '_title', 'title', 'Title', 'text'),
            $field($key . '_description', 'description', 'Description', 'textarea', ['rows' => 3]),
            $field($key . '_icon', 'icon', 'Line icon (optional)', 'select', ['choices' => ['' => 'No icon', 'quote' => 'Requirements', 'document' => 'Document', 'model' => 'Equipment', 'globe' => 'Market', 'calendar' => 'Date', 'gear' => 'Configuration', 'review' => 'Technical review', 'factory' => 'Production', 'service' => 'Service', 'chart' => 'Market support', 'check' => 'Agreement', 'cooperation' => 'Cooperation'], 'default_value' => '']),
            $field($key . '_image', 'image', 'Photograph', 'image', ['return_format' => 'id', 'preview_size' => 'medium']),
            $field($key . '_link', 'link', 'Optional link', 'link', ['return_format' => 'array']),
        ],
    ]);
}

function company_pair_field(callable $field, string $key, string $name, string $label, int $max = 12): array {
    return $field($key, $name, $label, 'repeater', [
        'layout' => 'table', 'max' => $max, 'button_label' => 'Add row', 'collapsed' => $key . '_label',
        'sub_fields' => [
            $field($key . '_label', 'label', 'Label', 'text'),
            $field($key . '_value', 'value', 'Description / value', 'textarea', ['rows' => 2]),
        ],
    ]);
}

function about_design_fields(callable $field, callable $tab): array {
    $image = static fn (string $key, string $name, string $label): array => $field($key, $name, $label, 'image', ['return_format' => 'id', 'preview_size' => 'medium']);
    return [
        $tab('field_about_tab_design_hero', 'tab_about_design_hero', 'Hero & company facts'),
        $field('field_about_hero_title', 'about_hero_title', 'Hero headline', 'text'),
        $field('field_about_hero_intro', 'about_hero_intro', 'Hero introduction', 'textarea', ['rows' => 3]),
        $image('field_about_hero_image', 'about_hero_image', 'Hero photograph'),
        $field('field_about_hero_caption', 'about_hero_caption', 'Photograph caption', 'text'),
        $field('field_about_company_title', 'about_company_title', 'Company introduction heading', 'text'),
        company_pair_field($field, 'field_about_company_facts', 'about_company_facts', 'Company facts', 8),
        $tab('field_about_tab_collaboration', 'tab_about_collaboration', 'Working together'),
        $field('field_about_process_title', 'about_process_title', 'Collaboration heading', 'text'),
        $field('field_about_process_intro', 'about_process_intro', 'Collaboration introduction', 'textarea', ['rows' => 3]),
        company_card_field($field, 'field_about_process_cards', 'about_process_cards', 'Collaboration steps', 6),
        $tab('field_about_tab_team', 'tab_about_team', 'Team & background'),
        $field('field_about_team_title', 'about_team_title', 'Team heading', 'text'),
        $field('field_about_team_intro', 'about_team_intro', 'Team introduction', 'textarea', ['rows' => 4]),
        $image('field_about_team_image', 'about_team_image', 'Team photograph'),
        $field('field_about_team_caption', 'about_team_caption', 'Team photograph caption', 'text'),
        $field('field_about_background_title', 'about_background_title', 'Background heading', 'text'),
        $field('field_about_background_intro', 'about_background_intro', 'Background introduction', 'textarea', ['rows' => 3]),
        company_pair_field($field, 'field_about_timeline', 'about_timeline', 'Background timeline', 12),
        $image('field_about_background_image', 'about_background_image', 'Background photograph'),
        $tab('field_about_tab_equipment', 'tab_about_equipment', 'Equipment & inquiry'),
        $field('field_about_range_title', 'about_range_title', 'Equipment range heading', 'text'),
        $field('field_about_show_range', 'about_show_range', 'Show the five equipment families', 'true_false', ['ui' => 1, 'default_value' => 0]),
        $field('field_about_inquiry_title', 'about_inquiry_title', 'Inquiry band heading', 'text'),
        $field('field_about_inquiry_intro', 'about_inquiry_intro', 'Inquiry band introduction', 'textarea', ['rows' => 2]),
    ];
}
