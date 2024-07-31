<?php
function plugin_options_help_page_html() {
    defined( 'ABSPATH' ) or die( "Access denied !" );

    if (!current_user_can("manage_options")) {
        return;
    }

    ?>
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <p>For any assistance with this site contact the club secretary, or contact Dan for help with this plugin.</p>
<?php
}
?>