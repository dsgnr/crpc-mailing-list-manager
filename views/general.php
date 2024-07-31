<?php
function plugin_options_general_page_html() {
    defined( 'ABSPATH' ) or die( "Access denied !" );

    if (!current_user_can("manage_options")) {
        return;
    }

    ?>
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <p>Some generic text about the plugin, and maybe some information about where things are...</p>

<?php
}
?>