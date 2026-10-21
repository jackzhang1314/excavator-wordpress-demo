<?php
/** Additional editable content required by approved product designs 05 and 06. */
namespace Starter\Model;
defined('ABSPATH') || exit;

function equipment_product_fields(callable $field, callable $tab, callable $tables): array {
    return [
        $tab('field_eq_product_tab_application', 'tab_product_application', 'Application overview'),
        $field('field_eq_product_overview_title', 'product_overview_title', 'Overview section title', 'text'),
        $field('field_eq_product_overview_cards', 'product_overview_cards', 'Application cards', 'repeater', [
            'layout' => 'block', 'button_label' => 'Add application card', 'max' => 6, 'collapsed' => 'field_eq_product_card_title',
            'instructions' => 'Each card needs a title and a photograph or useful description. Use images of the actual model; clear the collection to hide the whole section.',
            'sub_fields' => [
                $field('field_eq_product_card_title', 'title', 'Title', 'text'),
                $field('field_eq_product_card_image', 'image', 'Photograph', 'image', ['return_format' => 'id', 'preview_size' => 'medium']),
                $field('field_eq_product_card_description', 'description', 'Description', 'textarea', ['rows' => 3]),
            ],
        ]),
        $tab('field_eq_product_tab_configuration', 'tab_product_configuration', 'Configuration'),
        $field('field_eq_product_spec_image', 'product_specification_image', 'Specification photograph or drawing', 'image', ['return_format' => 'id', 'preview_size' => 'medium', 'instructions' => 'Use a verified drawing or a photograph of this model. No file means no image column.']),
        $field('field_eq_product_spec_caption', 'product_specification_caption', 'Specification image caption', 'text', ['instructions' => 'Identify a concept image or state the verified drawing scope. Do not infer dimensions from a photograph.']),
        $tables('field_eq_product_configuration_tables', 'product_configuration_tables', 'Standard and optional configuration'),
        $tab('field_eq_product_tab_attachments', 'tab_product_attachments', 'Attachments'),
        $field('field_eq_product_attachments', 'product_attachments', 'Compatible attachments', 'repeater', [
            'layout' => 'block', 'button_label' => 'Add attachment', 'max' => 12, 'collapsed' => 'field_eq_product_attachment_title',
            'instructions' => 'These are this model’s attachments, not additional primary equipment families. State the verified compatibility scope. A title alone does not create a card.',
            'sub_fields' => [
                $field('field_eq_product_attachment_title', 'title', 'Attachment name', 'text'),
                $field('field_eq_product_attachment_image', 'image', 'Photograph', 'image', ['return_format' => 'id', 'preview_size' => 'medium']),
                $field('field_eq_product_attachment_description', 'description', 'Compatibility / description', 'textarea', ['rows' => 3]),
                $field('field_eq_product_attachment_link', 'link', 'Optional information link', 'link', ['return_format' => 'array']),
            ],
        ]),
    ];
}
