<?php
/* @package crpc_admin

Plugin Name: Chichester Rifle Club Admin Helper
Plugin URI:
Description: A custom plugin for Chichester Rifle Club to aid with mapping CRPC member attributes to respective mailing lists.
Version: 0.0.5
Author: dsgnr
Author URI: https://github.com/dsgnr
License: GPLv2 or later
Text Domain: crpc_admin
*/
define( "CRPC_ADMIN_PATH", plugin_dir_path( __FILE__ ) );
define( 'CRPC_ADMIN_NAME', 'crpc_admin' );
define( 'CRPC_ADMIN_MSG', 'crpc_admin_message' );
define( 'CRPC_ADMIN_MAIL_OPTIONS', [
        "full_member" => "Full Member",
        "full_member_junior_student" => "Junior Member",
        "probationary_member" => "Probationary Member",
        "prone_shooter" => "Prone Shooter",
        "benchrest_shooter" => "Benchrest Shooter",
        "lsr_shooter" => "LSR Shooter",
        "full_bore_shooter" => "Full-bore Shooter"
    ] 
);


function setup() {
    if ( is_admin() ) {
        require_once CRPC_ADMIN_PATH . 'admin/menu.php';

        /* Registers and prunes scheduled tasks on plugin activation/removal */
        register_activation_hook( __FILE__, 'crpc_mail_list_cron_activation' );
        register_deactivation_hook( __FILE__, 'crpc_mail_list_cron_deactivation' );

        function crpc_mail_list_cron_activation() {
            if ( ! wp_next_scheduled( 'crpc_admin_task_sort_mail_lists' ) ) {
                wp_schedule_event( strtotime('00:00:00'), 'daily', 'crpc_admin_task_sort_mail_lists' );
            }
        }

        function crpc_mail_list_cron_deactivation() {
            wp_clear_scheduled_hook( 'crpc_admin_task_sort_mail_lists' );
        }
    }
}

setup();
?>
