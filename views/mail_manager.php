<?php

require_once CRPC_ADMIN_PATH . 'admin/mail_manager.php';

function crpc_admin_mail_manager_page_html() {
    /**
     * Init the UI
     */
    defined( 'ABSPATH' ) or die( "Access denied !" );

    // check user capabilities
    if (!current_user_can("manage_options")) {
        return;
    }

    // check if the user have submitted the settings
    // WordPress will add the "settings-updated" $_GET parameter to the url
    if (isset($_GET["settings-updated"])) {
        // add settings saved message with the class of "updated"
        add_settings_error(
            CRPC_ADMIN_MSG,
            CRPC_ADMIN_MSG,
            __("Settings Saved", CRPC_ADMIN_NAME),
            "updated"
        );
    }

    // Check whether the button has been pressed AND also check the nonce
    if (isset($_POST[CRPC_ADMIN_NAME]) && check_admin_referer(CRPC_ADMIN_NAME . '_adhoc_mail_sorter_clicked')) {
        // the button has been pressed AND we've passed the security check
        require_once CRPC_ADMIN_PATH . 'lib/mail_manager/tasks.php';
        crpc_admin_mail_list_action();
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="admin.php?page=crpc-admin-mail-mgr" method="post">
            <h2>Scheduled Sorting</h2>
            <p><b>The subscription sorter runs once daily as a scheduled task. </br>
            This can help where new members are onboarded, or a member adds/removes disciplines. </br>
            The next scheduled run time is <?php print_r(gmdate("l jS F Y h:i:s A", wp_next_scheduled( 'crpc_admin_task_sort_mail_lists' ))); ?>.</b></p>
            Alternatively, you may run it now by clicking the <b>'Sort Them!'</b> button below.
            <?php // this is a WordPress security feature - see: https://codex.wordpress.org/WordPress_Nonces
                wp_nonce_field(CRPC_ADMIN_NAME . '_adhoc_mail_sorter_clicked');
            ?>
            <input type="hidden" value="true" name="<?php echo CRPC_ADMIN_NAME ?>" />
            <?php
                if (class_exists(\MailPoet\API\API::class)) {
                    submit_button("Sort Them!", "secondary");
                }
            ?>
        </form>
        <hr>
        <form action="options.php" method="post">
            <h2>Mailing List Mapping</h2>
            <p>
                In order to know what mailing lists to assign members to, a collection of member attributes from WP-Members has been compiled. </br>
                This consists of the members' membership level, and their chosen disciplines. </br>
                Assign the correct mailing list that corresponds to the member attribute from the dropdown options below.
            </p>
                <?php
                    // output security fields for the registered setting CRPC_ADMIN_NAME
                    settings_fields(CRPC_ADMIN_NAME);
                    // output setting sections and their fields
                    // (sections are registered for CRPC_ADMIN_NAME, each field is registered to a specific section)
                    do_settings_sections(CRPC_ADMIN_NAME);
                    // output save settings button
                    if (class_exists(\MailPoet\API\API::class)) {
                        submit_button("Save Settings");
                    }
                ?>
        </form>

    </div>
    <?php
    // show error/update messages
    settings_errors(CRPC_ADMIN_MSG);
}
?>