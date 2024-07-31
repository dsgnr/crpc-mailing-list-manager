<?php
function crpc_admin_mail_list_admin() {
    require_once CRPC_ADMIN_PATH . 'lib/mail_manager/options.php';

    /**
     * Dynamically creates option stores for the mail list manager
     */
    if (!class_exists(\MailPoet\API\API::class)) {
        add_settings_error(
            CRPC_ADMIN_MSG,
            CRPC_ADMIN_MSG,
            __("Unable to locate the MailPoet API... is the plugin activated?", CRPC_ADMIN_NAME),
            "error"
        );
        return;
    }

    $mailpoet_api = \MailPoet\API\API::MP("v1");
    $mailing_lists = $mailpoet_api->getlists();
    if(!count($mailing_lists)) {
        add_settings_error(
            CRPC_ADMIN_MSG,
            CRPC_ADMIN_MSG,
            __("In order to map attributes to mailing lists, the lists must first be created in the <a href='admin.php?page=mailpoet-lists' target='_blank'>MailPoet Lists Page</a>. </br>Once this is complete, the lists will appear in the dropdowns below for selection.",
            CRPC_ADMIN_NAME),
            "error"
        );
    }

    // Register a new section in the CRPC_ADMIN_NAME page.
    add_settings_section("crpc_admin_mail_mgr_admin", "", "", CRPC_ADMIN_NAME);

    // Register the various settings for the page.
    foreach (CRPC_ADMIN_MAIL_OPTIONS as $canonical_name => $friendly_name) {
        register_setting(CRPC_ADMIN_NAME, "crpc_admin_" . $canonical_name . "_mail_mgr_options");
        // Register a new field in the "crpc_admin_section_developers" section, inside the mail manager page.
        add_settings_field(
            "crpc_admin_field_" . $canonical_name,
            __($friendly_name . "s List", CRPC_ADMIN_NAME),
            "crpc_admin_field_" . $canonical_name . "_settings",
            CRPC_ADMIN_NAME,
            "crpc_admin_mail_mgr_admin",
            [
                "label_for" => "crpc_admin_field_" . $canonical_name,
                "class" => "crpc_admin_row",
                "crpc_admin_custom_data" => "custom",
                "mailing_lists" => $mailing_lists,
            ]
        );
    }
}

// Register our crpc_admin_settings_init to the admin_init action hook.
add_action("admin_init", "crpc_admin_mail_list_admin");
?>