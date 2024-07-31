<?php
/**
 * @package crpc_mail_list_mgr
 */
/*
Plugin Name: Dan's Mail List Tool 
Plugin URI:  
Description: Automatically adds CRPC members to respective mailing lists.
Version: 0.0.1 
Author: dsgnr 
Author URI: https://github.com/dsgnr 
License: GPLv2 or later 
Text Domain: crpc_mail_list_mgr 
 
/**
 * Global vars
 */
$member_list_options = [
	"full_member" => "Full Member", 
	"probationary_member" => "Probationary Member", 
	"prone_shooter" => "Prone Shooter",
	"benchrest_shooter" => "Benchrest Shooter", 
	"lsr_shooter" => "LSR Shooter", 
	"full_bore_shooter" => "Full-bore Shooter"
];


function crpc_mail_list_mgr_options_page() {
	/**
	 * Add the top level menu page.
	 */
    add_menu_page("Mailing List Settings", "Mailing List Options", "manage_options", "crpc_mail_list_mgr", "crpc_mail_list_mgr_options_page_html");
}

// Register our crpc_mail_list_mgr_options_page to the admin_menu action hook.
add_action("admin_menu", "crpc_mail_list_mgr_options_page");

function crpc_mail_list_mgr_user_add($mailpoet_api, $user) {
    $sub = [
		"email" => sanitize_email($user->user_email) , 
		"first_name" => sanitize_text_field($user->first_name) , 
		"last_name" => sanitize_text_field($user->last_name) , 
		"full_member" => $user->{'_wpmem_products_full-member'},
		"probationary_member" => $user->{'_wpmem_products_probationary-member'}
	];

    // Loop the users disciplines and add them to the sub list
    $disciplines = explode("|", $user->disciplines);
    foreach ($disciplines as $discipline) {
        $sub[$discipline . "_shooter"] = 1;
    }

    // See if the user exists first.
    try {
        $subscriber = $mailpoet_api->getSubscriber($sub["email"]);
    } catch(\Throwable $th) {
        // If the user doesn't exist, create them.
        $subscriber = $mailpoet_api->addSubscriber($sub);
        return $th;
    }

    // Determine which lists to add the user to and subscribe them
    if ($subscriber) {
        $mail_lists = [];
        if (count(array_keys(get_option("crpc_mail_list_mgr_full_member_options") , true)) && !empty($sub["full_member"]) && $sub["full_member"]) {
            $mail_lists = array_merge($mail_lists, get_option("crpc_mail_list_mgr_full_member_options"));
        }
        if (count(array_keys(get_option("crpc_mail_list_mgr_probationary_member_options") , true)) && !empty($sub["probationary_member"]) && $sub["probationary_member"]) {
            $mail_lists = array_merge($mail_lists, get_option("crpc_mail_list_mgr_probationary_member_options"));
        }
        if (count(array_keys(get_option("crpc_mail_list_mgr_prone_shooter_options") , true)) && !empty($sub["prone_shooter"]) && $sub["prone_shooter"]) {
            $mail_lists = array_merge($mail_lists, get_option("crpc_mail_list_mgr_prone_shooter_options"));
        }
        if (count(array_keys(get_option("crpc_mail_list_mgr_lsr_shooter_options") , true)) && !empty($sub["lsr_shooter"]) && $sub["lsr_shooter"]) {
            $mail_lists = array_merge($mail_lists, get_option("crpc_mail_list_mgr_lsr_shooter_options"));
        }
        if (count(array_keys(get_option("crpc_mail_list_mgr_benchrest_shooter_options") , true)) && !empty($sub["benchrest_shooter"]) && $sub["benchrest_shooter"]) {
            $mail_lists = array_merge($mail_lists, get_option("crpc_mail_list_mgr_benchrest_shooter_options"));
        }
        if (count(array_keys(get_option("crpc_mail_list_mgr_full_bore_shooter_options") , true)) && !empty($sub["full-bore_shooter"] && $sub["full-bore_shooter"])) {
            $mail_lists = array_merge($mail_lists, get_option("crpc_mail_list_mgr_full_bore_shooter_options"));
        }
        subscribe_user($mailpoet_api, $subscriber["id"], $mail_lists);
    }
    return;
}

function subscribe_user($mailpoet_api, $user_id, $lists) {
    // add users to the lists.
    try {
        $subscribe = $mailpoet_api->subscribeToLists($user_id, $lists);
    } catch(\Throwable $th) {
        return "unable to add to lists - " . $th;
    }
    return $subscribe;
}

function crpc_mail_list_mgr_caller() {
    $mailpoet_api = \MailPoet\API\API::MP("v1");
    try {
		$users = get_users();
		foreach ($users as $user) {
        	crpc_mail_list_mgr_user_add($mailpoet_api, $user);
    	}
		add_settings_error("crpc_mail_list_mgrs_messages", "crpc_mail_list_mgr_message", __("Users updated in mailing lists...", "crpc_mail_list_mgr") , "updated");
	} catch(\Throwable $th) {
    	add_settings_error("crpc_mail_list_mgrs_messages", "crpc_mail_list_mgr_message", __(print_r($th), "crpc_mail_list_mgr") , "error");
	}
}

function crpc_mail_list_mgr_settings_init() {
    if (!class_exists(\MailPoet\API\API::class)) {
        add_settings_error(
			"crpc_mail_list_mgrs_messages", 
			"crpc_mail_list_mgr_message", 
			__("Unable to locate the MailPoet API... is the plugin activated?", "crpc_mail_list_mgr"),
			"error"
		);
        return;
    }

    // Register a new section in the "crpc_mail_list_mgr" page.
    add_settings_section("crpc_mail_list_mgr_section_developers", __('Dan\'s CRPC mailing list management tool', "crpc_mail_list_mgr") , "", "crpc_mail_list_mgr");

    // Register the various settings for the page.
    foreach ($GLOBALS["member_list_options"] as $canonical_name => $friendly_name) {
        register_setting("crpc_mail_list_mgr", "crpc_mail_list_mgr_" . $canonical_name . "_options");
        // Register a new field in the "crpc_mail_list_mgr_section_developers" section, inside the "crpc_mail_list_mgr" page.
        add_settings_field(
			"crpc_mail_list_mgr_field_" . $canonical_name, 
			__($friendly_name . "s List", "crpc_mail_list_mgr"), 
			"crpc_mail_list_mgr_field_" . $canonical_name . "_settings",
			"crpc_mail_list_mgr", 
			"crpc_mail_list_mgr_section_developers", 
			[
				"label_for" => "crpc_mail_list_mgr_field_" . $canonical_name, 
				"class" => "crpc_mail_list_mgr_row", 
				"crpc_mail_list_mgr_custom_data" => "custom"
			]
		);
    }
}

// Register our crpc_mail_list_mgr_settings_init to the admin_init action hook.
add_action("admin_init", "crpc_mail_list_mgr_settings_init");

function crpc_mail_list_mgr_field_full_member_settings($args) {
    create_options($args, "full_member");
}

function crpc_mail_list_mgr_field_probationary_member_settings($args) {
    create_options($args, "probationary_member");
}

function crpc_mail_list_mgr_field_prone_shooter_settings($args) {
    create_options($args, "prone_shooter");
}

function crpc_mail_list_mgr_field_benchrest_shooter_settings($args) {
    create_options($args, "benchrest_shooter");
}

function crpc_mail_list_mgr_field_lsr_shooter_settings($args) {
    create_options($args, "lsr_shooter");
}

function crpc_mail_list_mgr_field_full_bore_shooter_settings($args) {
    create_options($args, "full_bore_shooter");
}

function create_options($args, $option) {
	/**
	 * Get the mailing lists, and populate the select fields
	 *
	 * @param array $args
	 * @param string $option
	 */
    $mailpoet_api = \MailPoet\API\API::MP("v1");
    $mailing_lists = $mailpoet_api->getlists();
    select_fields($args, $mailing_lists, $option);
}

function select_fields($args, $lists, $option_type) {
	/**
	 * Dynamically creates the dropdown options for the $option_type
	 * - the "label_for" key value is used for the "for" attribute of the <label>.
	 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
	 *
	 * @param array $args
	 * @param array $lists
	 * @param string $option_type
	 */
    $options = get_option("crpc_mail_list_mgr_" . $option_type . "_options"); ?>
	<select
		id="<?php echo esc_attr($args["label_for"]); ?>"
		data-custom="<?php echo esc_attr($args["crpc_mail_list_mgr_custom_data"]); ?>"
		name="crpc_mail_list_mgr_<?php echo esc_attr($option_type); ?>_options[<?php echo esc_attr($args["label_for"]); ?>]">
		<option value='' >Pick one...</option>
		<?php foreach ($lists as $list) { ?>
		<option value=<?php echo $list["id"]; ?> <?php echo isset($options[$args["label_for"]]) ? selected($options[$args["label_for"]], $list["id"], false) : ""; ?>>
			<?php esc_html_e($list["name"], "crpc_mail_list_mgr"); ?>
		</option>
		<?php } ?>
	</select>
	<?php
}

function crpc_mail_list_mgr_options_page_html() {
	/**
	 * Init the UI
	 */

    // check user capabilities
    if (!current_user_can("manage_options")) {
        return;
    }

    // check if the user have submitted the settings
    // WordPress will add the "settings-updated" $_GET parameter to the url
    if (isset($_GET["settings-updated"])) {
        // add settings saved message with the class of "updated"
        add_settings_error("crpc_mail_list_mgrs_messages", "crpc_mail_list_mgr_message", __("Settings Saved", "crpc_mail_list_mgr") , "updated");
    }
	?>
	<div class="wrap">
		<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
		<form action="options.php" method="post">
			<?php
				// output security fields for the registered setting "crpc_mail_list_mgr"
				settings_fields("crpc_mail_list_mgr");
				// output setting sections and their fields
				// (sections are registered for "crpc_mail_list_mgr", each field is registered to a specific section)
				do_settings_sections("crpc_mail_list_mgr");
				// output save settings button
				if (class_exists(\MailPoet\API\API::class)) {
					submit_button("Save Settings");
				}
			?>
		</form>
	</div>
	<?php // Check whether the button has been pressed AND also check the nonce
    if (isset($_POST["crpc_mail_list_mgr"]) && check_admin_referer("crpc_mail_list_mgr_clicked")) {
        // the button has been pressed AND we've passed the security check
        crpc_mail_list_mgr_caller();
    } ?>
  	<form action="options-general.php?page=crpc_mail_list_mgr" method="post">

		<?php // this is a WordPress security feature - see: https://codex.wordpress.org/WordPress_Nonces
			wp_nonce_field("crpc_mail_list_mgr_clicked"); 
		?>

		<input type="hidden" value="true" name="crpc_mail_list_mgr" />
		<?php if (class_exists(\MailPoet\API\API::class)) { submit_button("Run Subscription Sorter!");} ?>
	</form>

  </div>

	<?php 
	// show error/update messages
	settings_errors("crpc_mail_list_mgrs_messages");
}
