<?php
/** Preserve PHP serialization and JSON when moving a saved local site to another port. */
function excavator_demo_replace($value, array $from, string $to) {
    if (is_array($value)) {
        foreach ($value as $key => $item) $value[$key] = excavator_demo_replace($item, $from, $to);
        return $value;
    }
    if (!is_string($value)) return $value;
    if (is_serialized($value)) return serialize(excavator_demo_replace(unserialize($value, ['allowed_classes' => false]), $from, $to));
    if (in_array(substr($value, 0, 1), ['{', '['], true)) {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) return wp_json_encode(excavator_demo_replace($decoded, $from, $to), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    return str_replace($from, $to, $value);
}

function excavator_demo_relocate(string $base): void {
    global $wpdb;
    $old = (string) $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name='excavator_demo_origin'");
    if ($old === $base) return;
    $from = ['https://demo.invalid'];
    if (preg_match('~^http://127\.0\.0\.1:\d+$~D', $old)) $from[] = $old;
    $tables = [
        'posts' => ['ID', ['post_content', 'post_excerpt', 'guid']],
        'postmeta' => ['meta_id', ['meta_value']],
        'termmeta' => ['meta_id', ['meta_value']],
        'options' => ['option_id', ['option_value']],
        'usermeta' => ['umeta_id', ['meta_value']],
        'fluentform_forms' => ['id', ['form_fields', 'appearance_settings', 'conditions']],
        'fluentform_form_meta' => ['id', ['value']],
    ];
    foreach ($tables as $suffix => [$id, $columns]) {
        $table = $wpdb->prefix . $suffix;
        foreach ($wpdb->get_results('SELECT ' . implode(',', array_merge([$id], $columns)) . " FROM $table", ARRAY_A) as $row) {
            $change = [];
            foreach ($columns as $column) {
                $next = excavator_demo_replace($row[$column], $from, $base);
                if ($next !== $row[$column]) $change[$column] = $next;
            }
            if ($change && $wpdb->update($table, $change, [$id => $row[$id]]) === false) throw new RuntimeException('Could not relocate local content: ' . $suffix);
        }
    }
    wp_cache_flush();
    update_option('home', $base);
    update_option('siteurl', $base);
    update_option('excavator_demo_origin', $base, false);
    update_option('blog_public', '0');
}
