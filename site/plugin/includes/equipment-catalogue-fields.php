<?php
/** Complete archive editor, held by the existing single Business Profile. */
namespace Starter\Model;
defined('ABSPATH') || exit;
function register_equipment_catalogue_fields(callable $field, callable $tab): void {
    $cards = static function (string $key, string $name, string $label) use ($field): array {
        return $field($key, $name, $label, 'repeater', ['layout' => 'block', 'max' => 8, 'collapsed' => $key . '_title', 'button_label' => 'Add card',
            'sub_fields' => [
                $field($key . '_title', 'title', 'Title', 'text'),
                $field($key . '_description', 'description', 'Description', 'textarea', ['rows' => 3]),
                $field($key . '_image', 'image', 'Photograph', 'image', ['return_format' => 'id', 'preview_size' => 'medium']),
                $field($key . '_link', 'link', 'Link', 'link', ['return_format' => 'array']),
            ],
        ]);
    };
    acf_add_local_field_group([
        'key' => 'group_equipment_catalogue', 'title' => 'Equipment overview content', 'show_in_rest' => false,
        'fields' => [
            $tab('field_eq_archive_tab_hero', 'tab_equipment_hero', 'Equipment hero'),
            $field('field_eq_archive_title', 'equipment_archive_title', 'Equipment headline', 'text'),
            $field('field_product_archive_description', 'product_archive_description', 'Equipment introduction', 'textarea', ['rows' => 3]),
            $field('field_eq_archive_image', 'equipment_archive_image', 'Equipment hero photograph', 'image', ['return_format' => 'id', 'preview_size' => 'medium']),
            $tab('field_eq_archive_tab_advice', 'tab_equipment_advice', 'Task advice'),
            $field('field_eq_advice_title', 'equipment_advice_title', 'Advice heading', 'text'),
            $field('field_eq_advice_description', 'equipment_advice_description', 'Advice introduction', 'textarea', ['rows' => 3]),
            $cards('field_eq_task_cards', 'equipment_task_cards', 'Task cards'),
            $tab('field_eq_archive_tab_conditions', 'tab_equipment_conditions', 'Working conditions'),
            $field('field_eq_conditions_title', 'equipment_conditions_title', 'Working conditions heading', 'text'),
            $field('field_eq_conditions_description', 'equipment_conditions_description', 'Working conditions introduction', 'textarea', ['rows' => 3]),
            $cards('field_eq_condition_cards', 'equipment_condition_cards', 'Working conditions cards'),
            $tab('field_eq_archive_tab_cta', 'tab_equipment_cta', 'Inquiry band'),
            $field('field_eq_cta_title', 'equipment_cta_title', 'Inquiry heading', 'text'),
            $field('field_eq_cta_text', 'equipment_cta_text', 'Inquiry introduction', 'textarea', ['rows' => 3]),
            $field('field_eq_cta_image', 'equipment_cta_image', 'Inquiry background photograph', 'image', ['return_format' => 'id', 'preview_size' => 'medium']),
        ],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'starter_profile']]],
    ]);
}
