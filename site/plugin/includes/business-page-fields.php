<?php
/** Complete editors for the selected documentation, cooperation and support pages. */
namespace Starter\Model;
defined('ABSPATH') || exit;

function business_hero_fields(callable $field, callable $tab, string $prefix): array {
    return [
        $tab('field_eq_' . $prefix . '_tab_hero', 'tab_' . $prefix . '_hero', 'Hero'),
        $field('field_eq_' . $prefix . '_hero_title', $prefix . '_hero_title', 'Hero headline', 'text'),
        $field('field_eq_' . $prefix . '_hero_overline', $prefix . '_hero_overline', 'Hero overline', 'text'),
        $field('field_eq_' . $prefix . '_hero_intro', $prefix . '_hero_intro', 'Hero introduction', 'textarea', ['rows' => 3]),
        $field('field_eq_' . $prefix . '_hero_image', $prefix . '_hero_image', 'Hero photograph', 'image', ['return_format' => 'id', 'preview_size' => 'medium']),
        $field('field_eq_' . $prefix . '_hero_caption', $prefix . '_hero_caption', 'Hero photograph caption', 'text'),
    ];
}
function business_section_fields(callable $field, callable $tab, string $prefix, string $section, string $label): array {
    $name = $prefix . '_' . $section;
    return [
        $tab('field_eq_' . $name . '_tab', 'tab_' . $name, $label),
        $field('field_eq_' . $name . '_title', $name . '_title', 'Section heading', 'text'),
        $field('field_eq_' . $name . '_intro', $name . '_intro', 'Section introduction', 'textarea', ['rows' => 3]),
    ];
}
function business_table_field(callable $field, string $name, array $labels): array {
    $columns = [];
    foreach (['first', 'second', 'third'] as $index => $column) $columns[] = $field('field_eq_' . $name . '_' . $column, $column, $labels[$index], 'text');
    return $field('field_eq_' . $name, $name, 'Table rows', 'repeater', ['layout' => 'table', 'button_label' => 'Add row', 'max' => 30, 'sub_fields' => $columns]);
}
function business_inquiry_fields(callable $field, callable $tab, string $prefix): array {
    return array_merge(business_section_fields($field, $tab, $prefix, 'inquiry', 'Inquiry'), [
        $field('field_eq_' . $prefix . '_show_form', $prefix . '_show_form', 'Show shared inquiry form', 'true_false', ['ui' => 1, 'default_value' => 0, 'instructions' => 'Uses the existing Fluent Forms intake, private attachments and inquiry attribution.']),
        $field('field_eq_' . $prefix . '_cta_title', $prefix . '_cta_title', 'Closing inquiry heading', 'text'),
        $field('field_eq_' . $prefix . '_cta_intro', $prefix . '_cta_intro', 'Closing inquiry introduction', 'textarea', ['rows' => 2]),
        $field('field_eq_' . $prefix . '_cta_image', $prefix . '_cta_image', 'Closing background image', 'image', ['return_format' => 'id', 'preview_size' => 'medium']),
    ]);
}
function production_design_fields(callable $field, callable $tab): array {
    $fields = business_hero_fields($field, $tab, 'production');
    foreach (['process' => ['Manufacturing process', 5], 'areas' => ['Key inspection areas', 4], 'evidence' => ['Supporting evidence', 4]] as $section => [$label, $max]) {
        $fields = array_merge($fields, business_section_fields($field, $tab, 'production', $section, $label), [company_card_field($field, 'field_eq_production_' . $section . '_cards', 'production_' . $section . '_cards', $label, $max)]);
    }
    return array_merge($fields, business_section_fields($field, $tab, 'production', 'checklist', 'Inspection checklist'), [business_table_field($field, 'production_checklist_rows', ['Checkpoint', 'Record', 'Scope'])], business_inquiry_fields($field, $tab, 'production'));
}
function register_business_page_designs(callable $field, callable $tab): void {
    $groups = [];
    $documents = array_merge(business_hero_fields($field, $tab, 'documents'), business_section_fields($field, $tab, 'documents', 'list', 'Document list'));
    $doc_fields = [];
    foreach (['title' => 'Document title', 'type' => 'Document type', 'family' => 'Product family', 'models' => 'Applicable models', 'market' => 'Market', 'validity' => 'Validity', 'revision' => 'Revision'] as $name => $label) $doc_fields[] = $field('field_eq_document_' . $name, $name, $label, 'text');
    $doc_fields[] = $field('field_eq_document_status', 'status', 'Verification status', 'select', ['choices' => ['pending' => 'Pending verification', 'available' => 'Available'], 'default_value' => 'pending']);
    $doc_fields[] = $field('field_eq_document_file', 'file', 'Verified document', 'file', ['return_format' => 'id', 'mime_types' => 'pdf,png,jpg,jpeg,webp', 'instructions' => 'Download appears only for an actual uploaded file with Available status. A pending row remains an information request.']);
    $documents[] = $field('field_eq_document_rows', 'document_rows', 'Document entries', 'repeater', ['layout' => 'block', 'button_label' => 'Add document', 'max' => 50, 'collapsed' => 'field_eq_document_title', 'sub_fields' => $doc_fields]);
    $documents = array_merge($documents, business_section_fields($field, $tab, 'documents', 'scope', 'Document scope'), [company_card_field($field, 'field_eq_documents_scope_cards', 'documents_scope_cards', 'Scope explanations', 3)], business_inquiry_fields($field, $tab, 'documents'));
    $documents[] = $field('field_eq_documents_request_title', 'documents_request_title', 'Document request explanation heading', 'text');
    $documents[] = $field('field_eq_documents_request_body', 'documents_request_body', 'Document request explanation', 'wysiwyg', ['tabs' => 'all', 'toolbar' => 'full', 'media_upload' => 1]);
    $documents[] = $field('field_eq_documents_fleet_image', 'documents_fleet_image', 'Equipment banner photograph', 'image', ['return_format' => 'id', 'preview_size' => 'medium']);
    $groups['certifications'] = ['Certifications & documents', $documents];

    $parts = business_hero_fields($field, $tab, 'parts');
    foreach (['support' => ['Support areas', 2], 'components' => ['Component categories', 5], 'identify' => ['Part identification', 4], 'requests' => ['Common requests', 5], 'service' => ['Maintenance & after-sales', 2]] as $section => [$label, $max]) {
        $parts = array_merge($parts, business_section_fields($field, $tab, 'parts', $section, $label), [company_card_field($field, 'field_eq_parts_' . $section . '_cards', 'parts_' . $section . '_cards', $label, $max)]);
    }
    $groups['parts-components'] = ['Parts & components', array_merge($parts, business_inquiry_fields($field, $tab, 'parts'))];

    $oem = business_hero_fields($field, $tab, 'oem');
    foreach (['customize' => ['Customization topics', 4], 'process' => ['OEM process', 5], 'evidence' => ['Related documents', 4]] as $section => [$label, $max]) {
        $oem = array_merge($oem, business_section_fields($field, $tab, 'oem', $section, $label), [company_card_field($field, 'field_eq_oem_' . $section . '_cards', 'oem_' . $section . '_cards', $label, $max)]);
    }
    $oem[] = $field('field_eq_oem_customization_image', 'oem_customization_image', 'Equipment configuration photograph', 'image', ['return_format' => 'id', 'preview_size' => 'medium']);
    $oem = array_merge($oem, business_section_fields($field, $tab, 'oem', 'table', 'Discussion table'), [business_table_field($field, 'oem_table_rows', ['Request', 'Engineering review', 'Market requirements'])], business_inquiry_fields($field, $tab, 'oem'));
    $groups['oem-solution'] = ['OEM solution', $oem];

    $dealer = array_merge(business_hero_fields($field, $tab, 'dealer'), [$field('field_eq_dealer_range_title', 'dealer_range_title', 'Equipment range heading', 'text'), $field('field_eq_dealer_show_range', 'dealer_show_range', 'Show the equipment range', 'true_false', ['ui' => 1])]);
    foreach (['support' => ['Partnership support', 4], 'process' => ['Partner discussion process', 4], 'exchange' => ['Information exchange', 2]] as $section => [$label, $max]) {
        $dealer = array_merge($dealer, business_section_fields($field, $tab, 'dealer', $section, $label), [company_card_field($field, 'field_eq_dealer_' . $section . '_cards', 'dealer_' . $section . '_cards', $label, $max)]);
    }
    $groups['dealer-opportunity'] = ['Dealer opportunity', array_merge($dealer, business_inquiry_fields($field, $tab, 'dealer'))];

    $service = array_merge(business_hero_fields($field, $tab, 'service'), business_section_fields($field, $tab, 'service', 'process', 'Service process'), [company_pair_field($field, 'field_eq_service_steps', 'service_steps', 'Service stages', 12)]);
    $service = array_merge($service, business_section_fields($field, $tab, 'service', 'table', 'Responsibilities'), [business_table_field($field, 'service_table_rows', ['Stage', 'Information needed', 'Documents to confirm'])]);
    foreach (['packing' => ['Process photographs', 2], 'related' => ['Related information', 3]] as $section => [$label, $max]) $service = array_merge($service, business_section_fields($field, $tab, 'service', $section, $label), [company_card_field($field, 'field_eq_service_' . $section . '_cards', 'service_' . $section . '_cards', $label, $max)]);
    $service = array_merge($service, business_section_fields($field, $tab, 'service', 'faq', 'Frequently asked questions'), [company_pair_field($field, 'field_eq_service_faq_rows', 'service_faq_rows', 'Questions and answers', 12), $field('field_eq_service_body', 'service_body', 'Additional service details', 'wysiwyg', ['tabs' => 'all', 'toolbar' => 'full', 'media_upload' => 1])], business_inquiry_fields($field, $tab, 'service'));
    $groups['service-support'] = ['Payment, delivery & support', $service];
    $policy = array_merge(business_hero_fields($field, $tab, 'policy'), [
        $tab('field_eq_policy_tab_questions', 'tab_policy_questions', 'Questions'),
        $field('field_eq_faq_sections', 'faq_sections', 'Question topics', 'repeater', ['layout' => 'block', 'button_label' => 'Add topic', 'max' => 12, 'collapsed' => 'field_eq_faq_topic_title', 'sub_fields' => [
            $field('field_eq_faq_topic_title', 'title', 'Topic title', 'text'),
            $field('field_eq_faq_topic_intro', 'intro', 'Topic introduction', 'textarea', ['rows' => 2]),
            $field('field_eq_faq_topic_rows', 'questions', 'Questions', 'repeater', ['layout' => 'block', 'max' => 20, 'button_label' => 'Add question', 'collapsed' => 'field_eq_faq_question', 'sub_fields' => [
                $field('field_eq_faq_question', 'question', 'Question', 'text'),
                $field('field_eq_faq_answer', 'answer', 'Answer', 'textarea', ['rows' => 4]),
            ]]),
        ]]),
        $tab('field_eq_policy_tab_reading', 'tab_policy_reading', 'Policy reading'),
        $field('field_eq_policy_reading_title', 'policy_reading_title', 'Policy reading heading', 'text'),
        $field('field_eq_policy_reading_intro', 'policy_reading_intro', 'Policy reading introduction', 'textarea', ['rows' => 3]),
        $field('field_eq_policy_body', 'policy_body', 'Policy text', 'wysiwyg', ['tabs' => 'all', 'toolbar' => 'full', 'media_upload' => 1, 'instructions' => 'The editable full policy text. Empty content hides the entire reading section and its contents menu.']),
        $field('field_eq_policy_reading_image', 'policy_reading_image', 'Policy photograph', 'image', ['return_format' => 'id', 'preview_size' => 'medium']),
        $tab('field_eq_policy_tab_support', 'tab_policy_support', 'Support card'),
        $field('field_eq_policy_support_title', 'policy_support_title', 'Support heading', 'text'),
        $field('field_eq_policy_support_intro', 'policy_support_intro', 'Support introduction', 'textarea', ['rows' => 3]),
        $field('field_eq_policy_support_image', 'policy_support_image', 'Support photograph', 'image', ['return_format' => 'id', 'preview_size' => 'medium']),
    ], business_inquiry_fields($field, $tab, 'policy'));
    $groups['faq-policy'] = ['FAQs & policies', $policy];
    foreach ($groups as $slug => [$title, $fields]) acf_add_local_field_group([
        'key' => 'group_eq_' . str_replace('-', '_', $slug), 'title' => $title, 'show_in_rest' => true, 'fields' => $fields,
        'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/' . $slug . '.php']]],
    ]);
}

function contact_design_fields(callable $field, callable $tab): array {
    return [
        $tab('field_eq_contact_tab_design', 'tab_contact_design', 'Contact paths & next steps'),
        $field('field_eq_contact_image', 'contact_image', 'Contact photograph', 'image', ['return_format' => 'id', 'preview_size' => 'medium']),
        $field('field_eq_contact_paths_title', 'contact_paths_title', 'Contact paths heading', 'text'),
        company_card_field($field, 'field_eq_contact_path_cards', 'contact_path_cards', 'Contact paths', 3),
        company_pair_field($field, 'field_eq_contact_channels', 'contact_channels', 'Public contact information', 5),
        $field('field_eq_contact_next_title', 'contact_next_title', 'Next steps heading', 'text'),
        $field('field_eq_contact_next_intro', 'contact_next_intro', 'Next steps introduction', 'textarea', ['rows' => 3]),
        company_card_field($field, 'field_eq_contact_next_cards', 'contact_next_cards', 'Next steps', 3),
        $field('field_eq_contact_range_title', 'contact_range_title', 'Equipment banner heading', 'text'),
        $field('field_eq_contact_range_intro', 'contact_range_intro', 'Equipment banner introduction', 'textarea', ['rows' => 3]),
        $field('field_eq_contact_range_image', 'contact_range_image', 'Equipment banner photograph', 'image', ['return_format' => 'id', 'preview_size' => 'medium']),
    ];
}
