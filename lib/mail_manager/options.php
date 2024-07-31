<?php
function crpc_admin_field_full_member_settings($args) {
    create_mail_list_options($args, "full_member");
}

function crpc_admin_field_probationary_member_settings($args) {
    create_mail_list_options($args, "probationary_member");
}

function crpc_admin_field_prone_shooter_settings($args) {
    create_mail_list_options($args, "prone_shooter");
}

function crpc_admin_field_benchrest_shooter_settings($args) {
    create_mail_list_options($args, "benchrest_shooter");
}

function crpc_admin_field_lsr_shooter_settings($args) {
    create_mail_list_options($args, "lsr_shooter");
}

function crpc_admin_field_full_bore_shooter_settings($args) {
    create_mail_list_options($args, "full_bore_shooter");
}

function create_mail_list_options($args, $option) {
    /**
     * Get the mailing lists, and populate the select fields
     *
     * @param array $args
     * @param string $option
     */
    mail_list_select_options($args, $option);
}

function mail_list_select_options($args, $option_type) {
    /**
     * Dynamically creates the dropdown options for the $option_type
     * - the "label_for" key value is used for the "for" attribute of the <label>.
     * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
     *
     * @param array $args
     * @param string $option_type
     */
    $options = get_option("crpc_admin_" . $option_type . "_mail_mgr_options"); ?>
    <select
        id="<?php echo esc_attr($args["label_for"]); ?>"
        data-custom="<?php echo esc_attr($args["crpc_admin_custom_data"]); ?>"
        name="crpc_admin_<?php echo esc_attr($option_type); ?>_mail_mgr_options[<?php echo esc_attr($args["label_for"]); ?>]">
        <option value='' >Pick one...</option>
        <?php foreach ($args["mailing_lists"] as $list) { ?>
        <option value=<?php echo $list["id"]; ?> <?php echo isset($options[$args["label_for"]]) ? selected($options[$args["label_for"]], $list["id"], false) : ""; ?>>
            <?php esc_html_e($list["name"], CRPC_ADMIN_NAME); ?>
        </option>
        <?php } ?>
    </select>
    <?php
}
?>