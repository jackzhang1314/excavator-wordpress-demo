<?php
/** Restore native toolbar defaults once for the two bundled demo accounts. */
function excavator_demo_migrate_toolbar_default(): void {
    if (get_option('excavator_demo_toolbar_schema') === '1') return;
    foreach ([1 => 'demo_admin', 2 => 'demo_author'] as $id => $login) {
        $user = get_user_by('id', $id);
        if ($user && $user->user_login === $login) update_user_meta($id, 'show_admin_bar_front', 'true');
    }
    // Later changes made in the native Profile screen remain the user's choice.
    update_option('excavator_demo_toolbar_schema', '1', false);
}

/** The native Profile form requires a nickname before it can save settings. */
function excavator_demo_migrate_profile_defaults(): void {
    if (get_option('excavator_demo_profile_schema') === '1') return;
    foreach ([1 => 'demo_admin', 2 => 'demo_author'] as $id => $login) {
        $user = get_user_by('id', $id);
        if ($user && $user->user_login === $login && get_user_meta($id, 'nickname', true) === '') {
            update_user_meta($id, 'nickname', $user->display_name ?: $user->user_login);
        }
    }
    update_option('excavator_demo_profile_schema', '1', false);
}
