<?php
/** Complete Home editor for the selected industrial equipment design. */
namespace Starter\Model;
defined('ABSPATH') || exit;

function register_home_fields(callable $field, callable $tab): void {
    $image = ['return_format' => 'id', 'preview_size' => 'medium'];
    $link = ['return_format' => 'array'];
    $fields = [
        $tab('field_home_tab_hero', 'tab_home_hero', 'Hero'),
        $field('field_home_overline', 'home_overline', 'Hero eyebrow', 'text'),
        $field('field_home_description', 'home_description', 'Hero description', 'textarea', ['rows' => 3, 'instructions' => 'The native page title is the headline. The featured image is the wide hero photograph.']),
        $field('field_home_primary_link', 'home_primary_link', 'Primary action', 'link', $link),
        $field('field_home_secondary_link', 'home_secondary_link', 'Secondary action', 'link', $link),
        $tab('field_home_tab_sections', 'tab_home_sections', 'Equipment families'),
        $field('field_home_apps_title', 'applications_title', 'Section heading', 'text'),
        $field('field_home_ranges_intro', 'home_ranges_intro', 'Section introduction', 'textarea', ['rows' => 2]),
        $field('field_home_ranges_link', 'home_ranges_link', 'Equipment overview link', 'link', $link),
        $field('field_home_ranges', 'home_ranges', 'Equipment families in display order', 'taxonomy', [
            'taxonomy' => 'product_collection', 'field_type' => 'multi_select', 'return_format' => 'id',
            'add_term' => 0, 'save_terms' => 0, 'load_terms' => 0,
            'instructions' => 'Select the top-level families. Their linked category pages own names and images. Child categories are not top-level entrances.',
        ]),
        $tab('field_home_tab_applications', 'tab_home_applications', 'Industry applications'),
        $field('field_home_applications_title', 'home_applications_title', 'Section heading', 'text'),
        $field('field_home_applications_intro', 'home_applications_intro', 'Section introduction', 'textarea', ['rows' => 2]),
        $field('field_home_application_items', 'home_application_items', 'Applications', 'repeater', [
            'layout' => 'block', 'button_label' => 'Add application', 'collapsed' => 'field_home_application_title',
            'sub_fields' => [
                $field('field_home_application_title', 'title', 'Title', 'text'),
                $field('field_home_application_image', 'image', 'Photograph', 'image', $image),
                $field('field_home_application_text', 'text', 'Description', 'textarea', ['rows' => 2]),
                $field('field_home_application_link', 'link', 'Industry page link', 'link', $link),
            ],
        ]),
        $tab('field_home_tab_products', 'tab_home_products', 'Featured equipment'),
        $field('field_home_products_title', 'featured_products_title', 'Section heading', 'text'),
        $field('field_home_products_intro', 'home_products_intro', 'Section introduction', 'textarea', ['rows' => 2]),
        $field('field_home_featured_products', 'home_featured_products', 'Products in display order', 'relationship', [
            'post_type' => ['starter_product'], 'return_format' => 'id', 'filters' => ['search', 'taxonomy'],
            'instructions' => 'Select existing products and drag to reorder. Their own images and quick specifications appear here. Draft products remain private.',
        ]),
        $tab('field_home_tab_delivery', 'tab_home_delivery', 'Supply process'),
        $field('field_home_delivery_eyebrow', 'home_delivery_eyebrow', 'Eyebrow', 'text'),
        $field('field_home_delivery_title', 'home_delivery_title', 'Section heading', 'text'),
        $field('field_home_delivery_body', 'home_delivery_body', 'Introduction', 'wysiwyg', ['tabs' => 'all', 'toolbar' => 'basic', 'media_upload' => 0, 'delay' => 0]),
        $field('field_home_delivery_image', 'home_delivery_image', 'Right-hand photograph', 'image', $image),
        $field('field_home_delivery_links', 'home_delivery_links', 'Process page links', 'repeater', [
            'layout' => 'row', 'button_label' => 'Add process link', 'collapsed' => 'field_home_delivery_link',
            'sub_fields' => [
                $field('field_home_delivery_link', 'link', 'Page link', 'link', $link),
                $field('field_home_delivery_link_text', 'text', 'Supporting description', 'textarea', ['rows' => 2]),
            ],
        ]),
        $tab('field_home_tab_cta', 'tab_home_cta', 'Inquiry banner'),
        $field('field_home_cta_eyebrow', 'home_cta_eyebrow', 'Eyebrow', 'text'),
        $field('field_home_cta_title', 'home_cta_title', 'Heading', 'text'),
        $field('field_home_cta_text', 'home_cta_text', 'Introduction', 'textarea', ['rows' => 2]),
        $field('field_home_cta_link', 'home_cta_link', 'Contact action', 'link', $link),
        $field('field_home_cta_image', 'home_cta_image', 'Optional background', 'image', $image),
    ];
    acf_add_local_field_group([
        'key' => 'group_home_content', 'title' => 'Homepage content & sections', 'show_in_rest' => true,
        'label_placement' => 'top', 'instruction_placement' => 'label', 'fields' => $fields,
        'location' => [[['param' => 'page_type', 'operator' => '==', 'value' => 'front_page']]],
    ]);
}
