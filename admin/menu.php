<?php
function crpc_admin_options_page() {
    require_once CRPC_ADMIN_PATH . 'views/general.php';
    require_once CRPC_ADMIN_PATH . 'views/mail_manager.php';
    require_once CRPC_ADMIN_PATH . 'views/help.php';
    
    add_menu_page('CRPC Members Admin', 'CRPC Admin', 'manage_options', 'crpc-admin-general', '', 'dashicons-groups' );
    add_submenu_page('crpc-admin-general', 'General', 'General', 'manage_options', 'crpc-admin-general', 'plugin_options_general_page_html' );
    add_submenu_page('crpc-admin-general', 'CRPC Mailing List Admin', 'Mailing List Admin', 'manage_options', 'crpc-admin-mail-mgr', 'crpc_admin_mail_manager_page_html' );
    add_submenu_page('crpc-admin-general', 'Help', 'Help', 'manage_options', 'crpc-admin-help', 'plugin_options_help_page_html' );
}

// Register our crpc_admin_options_page to the admin_menu action hook.
add_action("admin_menu", "crpc_admin_options_page");
?>