<?php
/** A document listing distinguishes metadata from actual downloadable evidence. */
namespace Starter\Theme;
defined('ABSPATH') || exit;
function eq_document_rows(): array {
    $raw = get_field('document_rows'); $rows = [];
    foreach (is_array($raw) ? $raw : [] as $row) {
        if (!is_array($row) || trim((string) ($row['title'] ?? '')) === '') continue;
        foreach (['type', 'family', 'models', 'market', 'validity', 'revision'] as $key) $row[$key] = trim((string) ($row[$key] ?? ''));
        $file = (int) ($row['file'] ?? 0);
        $mime = $file ? get_post_mime_type($file) : '';
        $row['download'] = $file && ($row['status'] ?? '') === 'available' && in_array($mime, ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'], true) ? (wp_get_attachment_url($file) ?: '') : '';
        $rows[] = $row;
    }
    return $rows;
}
function eq_document_directory(): void {
    $all = eq_document_rows(); if (!$all) return;
    $choices = []; $filters = [];
    foreach (['type' => 'Document type', 'family' => 'Product family', 'market' => 'Market'] as $key => $label) {
        $choices[$key] = [];
        foreach ($all as $row) if ($row[$key] !== '' && strtolower($row[$key]) !== 'to confirm') $choices[$key][$row[$key]] = $row[$key];
        $filters[$key] = isset($_GET['doc_' . $key]) && is_string($_GET['doc_' . $key]) ? sanitize_text_field(wp_unslash($_GET['doc_' . $key])) : '';
    }
    $rows = array_values(array_filter($all, static function (array $row) use ($filters): bool {
        foreach ($filters as $key => $value) if ($value !== '' && $row[$key] !== $value) return false;
        return true;
    }));
    $sort = isset($_GET['doc_sort']) && $_GET['doc_sort'] === 'title' ? 'title' : '';
    if ($sort) usort($rows, static fn (array $a, array $b): int => strnatcasecmp($a['title'], $b['title']));
    echo '<section class="eq-section eq-document-directory"><div class="eq-shell eq-document-layout"><form id="eq-document-filter-form" class="eq-document-filters" method="get" action="' . esc_url(get_permalink()) . '"><h2>Filter documents</h2>';
    foreach (['type' => 'Document type', 'family' => 'Product family', 'market' => 'Market'] as $key => $label) {
        $all_label = $key === 'family' ? 'product families' : strtolower($label) . 's';
        echo '<label for="doc-' . esc_attr($key) . '">' . esc_html($label) . '</label><select id="doc-' . esc_attr($key) . '" name="doc_' . esc_attr($key) . '"><option value="">All ' . esc_html($all_label) . '</option>';
        foreach ($choices[$key] as $value) echo '<option value="' . esc_attr($value) . '"' . selected($filters[$key], $value, false) . '>' . esc_html($value) . '</option>';
        echo '</select>';
    }
    echo '<button class="button" type="submit">Apply filters</button><a class="eq-clear-link" href="' . esc_url(get_permalink()) . '">Clear all</a></form><div><header class="eq-document-list-heading"><h2>' . esc_html(eq_text('documents_list_title') ?: 'Documents') . ' <span>(' . count($rows) . ')</span></h2><label class="eq-document-sort" for="doc-sort">Sort by<select id="doc-sort" form="eq-document-filter-form" name="doc_sort"><option value="">As listed</option><option value="title"' . selected($sort, 'title', false) . '>Title A–Z</option></select></label></header><div class="eq-document-rows">';
    if (!$rows) echo '<p class="eq-empty-results">No documents match these filters.</p>';
    foreach ($rows as $row) {
        echo '<article class="eq-document-row"><div class="eq-document-icon" aria-hidden="true"><svg viewBox="0 0 40 48" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 2h20l10 10v34H5zM25 2v11h10M12 22h16M12 29h16M12 36h11"/></svg></div><div class="eq-document-name"><span class="eq-document-status' . ($row['download'] ? ' is-available' : '') . '">' . ($row['download'] ? 'Available' : 'Pending verification') . '</span><h3>' . esc_html($row['title']) . '</h3></div><dl>';
        foreach (['models' => 'Applicable models', 'market' => 'Market', 'validity' => 'Validity', 'revision' => 'Revision'] as $key => $label) echo '<div><dt>' . esc_html($label) . '</dt><dd>' . esc_html($row[$key] !== '' ? $row[$key] : 'To confirm') . '</dd></div>';
        echo '</dl>';
        if ($row['download']) echo '<a class="eq-document-action" href="' . esc_url($row['download']) . '" aria-label="' . esc_attr('Open ' . $row['title']) . '">↓</a>';
        elseif (get_field('documents_show_form')) echo '<a class="eq-document-action" href="#product-inquiry" aria-label="' . esc_attr('Request information about ' . $row['title']) . '">→</a>';
        echo '</article>';
    }
    echo '</div></div></div></section>';
}
