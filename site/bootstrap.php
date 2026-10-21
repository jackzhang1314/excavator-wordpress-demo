<?php
/** Import the already-filled site once, into a newly created local database. */
require '/wordpress/wp-load.php';
require_once '/wordpress/wp-admin/includes/plugin.php';
require '/demo-source/relocate.php';

global $wpdb;
if (!get_option('excavator_demo_seed_version')) {
    $data = json_decode(file_get_contents('/demo-source/content.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach (['advanced-custom-fields-pro/acf.php', 'classic-editor/classic-editor.php', 'fluentform/fluentform.php', 'seo-by-rank-math/rank-math.php', 'gtranslate/gtranslate.php', 'starter-model/starter-model.php'] as $plugin) {
        $result = activate_plugin($plugin);
        if (is_wp_error($result)) throw new RuntimeException($plugin . ': ' . $result->get_error_message());
    }
    $wpdb->query('START TRANSACTION');
    try {
        // Only a new, unpublished demo database reaches this branch.
        foreach (['posts', 'postmeta', 'terms', 'termmeta', 'term_taxonomy', 'term_relationships', 'comments', 'commentmeta', 'users', 'usermeta', 'fluentform_forms', 'fluentform_form_meta'] as $table) {
            if ($wpdb->query('DELETE FROM ' . $wpdb->prefix . $table) === false) throw new RuntimeException('Could not initialize ' . $table);
        }
        foreach ($data['tables'] as $suffix => $rows) {
            if ($suffix === 'options') continue;
            foreach ($rows as $row) if ($wpdb->insert($wpdb->prefix . $suffix, $row) === false) throw new RuntimeException('Could not import ' . $suffix . ': ' . $wpdb->last_error);
        }
        foreach ($data['tables']['options'] as $row) update_option($row['option_name'], maybe_unserialize($row['option_value']));
        $users = [
            ['ID' => 1, 'user_login' => 'demo_admin', 'user_nicename' => 'demo-admin', 'display_name' => 'admin', 'password' => 'ExcavatorDemo2026!', 'role' => 'administrator'],
            ['ID' => 2, 'user_login' => 'demo_author', 'user_nicename' => 'demo-author', 'display_name' => 'Alex Morgan', 'password' => wp_generate_password(48, true, true), 'role' => 'author'],
        ];
        foreach ($users as $user) {
            $role = $user['role']; $password = $user['password']; unset($user['role'], $user['password']);
            $user += ['user_email' => $user['user_login'] . '@example.invalid', 'user_pass' => wp_hash_password($password), 'user_url' => '', 'user_registered' => current_time('mysql'), 'user_activation_key' => '', 'user_status' => 0];
            if ($wpdb->insert($wpdb->users, $user) === false) throw new RuntimeException('Could not create demo user');
            clean_user_cache($user['ID']);
            (new WP_User($user['ID']))->set_role($role);
            update_user_meta($user['ID'], 'show_admin_bar_front', 'false');
            update_user_meta($user['ID'], 'rich_editing', 'true');
            update_user_meta($user['ID'], 'syntax_highlighting', 'true');
        }
        foreach ($data['authorMeta'] ?? [] as $row) update_user_meta((int) $row['user_id'], $row['meta_key'], maybe_unserialize($row['meta_value']));
        update_option('active_plugins', ['advanced-custom-fields-pro/acf.php', 'classic-editor/classic-editor.php', 'fluentform/fluentform.php', 'seo-by-rank-math/rank-math.php', 'gtranslate/gtranslate.php', 'starter-model/starter-model.php']);
        update_option('admin_email', 'demo@example.invalid');
        update_option('users_can_register', '0');
        update_option('cron', []);
        update_option('excavator_demo_seed_version', 1, false);
        if ($wpdb->query('COMMIT') === false) throw new RuntimeException('Could not save the demo database');
    } catch (Throwable $error) {
        $wpdb->query('ROLLBACK');
        throw $error;
    }
    wp_cache_flush();
}
// Preserve the original header language selector in existing demo copies too.
// Existing user-edited translation settings take precedence over the seed.
if (!get_option('excavator_demo_language_schema')) {
    if (get_option('GTranslate', null) === null) {
        $seed = json_decode(file_get_contents('/demo-source/content.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($seed['tables']['options'] as $option) if ($option['option_name'] === 'GTranslate') update_option('GTranslate', maybe_unserialize($option['option_value']));
    }
    if (!is_plugin_active('gtranslate/gtranslate.php')) {
        $result = activate_plugin('gtranslate/gtranslate.php');
        if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
    }
    update_option('excavator_demo_language_schema', 1, false);
}
// Apply native editor defaults to an older demo that was initialized without them.
foreach ([1, 2] as $id) {
    if (!metadata_exists('user', $id, 'rich_editing')) update_user_meta($id, 'rich_editing', 'true');
    if (!metadata_exists('user', $id, 'syntax_highlighting')) update_user_meta($id, 'syntax_highlighting', 'true');
}
// Content snapshots intentionally omit operational logs and caches. Let each
// owning plugin create its empty operational tables for the imported modules.
if (!get_option('excavator_demo_operational_schema')) {
    if (class_exists('RankMath\\Installer')) \RankMath\Installer::create_tables(get_option('rank_math_modules', []));
    $attachments = $wpdb->prefix . 'wb_inquiry_files';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $attachments)) !== $attachments) {
        delete_option('wb_inquiry_files_schema');
        \WordPressBuilder\Attachments\Module::install();
    }
    update_option('excavator_demo_operational_schema', 1, false);
}
excavator_demo_relocate(rtrim(WP_HOME, '/'));
flush_rewrite_rules(false);
echo 'Local content ready';
