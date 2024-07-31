<?php
/* @package crpc_mail_list_mgr

Plugin Name: Dan's Mail List Tool 
Plugin URI:  
Description: Allows the mapping of CRPC members based on their user metadata to respective mailing lists.
Version: 0.0.3
Author: dsgnr 
Author URI: https://github.com/dsgnr 
License: GPLv2 or later 
Text Domain: crpc_mail_list_mgr 
 
/* Global vars */
$member_list_options = [
	"full_member" => "Full Member", 
	"probationary_member" => "Probationary Member", 
	"prone_shooter" => "Prone Shooter",
	"benchrest_shooter" => "Benchrest Shooter", 
	"lsr_shooter" => "LSR Shooter", 
	"full_bore_shooter" => "Full-bore Shooter"
];


function crpc_mail_list_mgr_options_page() {
	add_menu_page('CRPC Members Admin', 'CRPC Admin', 'manage_options', 'crpc-plugin-menu', '', 'dashicons-groups' );
    add_submenu_page('crpc-plugin-menu', 'General', 'General', 'manage_options', 'crpc-plugin-menu', 'plugin_options_general_page_html' );
    add_submenu_page('crpc-plugin-menu', 'CRPC Mailing List Admin', 'Mailing List Admin', 'manage_options', 'crpc-plugin-list-mgr', 'crpc_mail_list_mgr_options_page_html' );
    add_submenu_page('crpc-plugin-menu', 'Help', 'Help', 'manage_options', 'crpc-plugin-help-menu', 'plugin_options_help_page_html' );
}

// Register our crpc_mail_list_mgr_options_page to the admin_menu action hook.
add_action("admin_menu", "crpc_mail_list_mgr_options_page");

function crpc_mail_list_mgr_user_iter($mailpoet_api, $user) {
	/**
	 * Determine the current user lists and determine what lists to add/remove.
	 *
	 * @param object $mailpoet_api
	 * @param object $user
	 */
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
        $sub[$discipline] = 1;
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
		/* Determine what lists the user is already subscribed to. We do not need to add them again...
			if all([
				the mailing list is mapped,
				user is assigned to the mapping,
				the user isn't already subscribed
			]) => subscribe
		*/ 
        $mail_lists_to_add = array();
		$mail_lists_to_remove = array();
		$user_subscriptions = array();
		foreach($subscriber["subscriptions"] as $list) {
			if($list["status"] === "subscribed") {
				array_push($user_subscriptions, $list["segment_id"]);
			}
		};
		foreach ($GLOBALS["member_list_options"] as $canonical_name => $friendly_name) {
			if (count(array_keys(get_option("crpc_mail_list_mgr_" . $canonical_name . "_options") , true))) {
				foreach(get_option("crpc_mail_list_mgr_" . $canonical_name . "_options") as $list_options) {
					if(!empty($sub[$canonical_name] ?? null) && ($sub[$canonical_name] ?? null)) {
						if(!in_array($list_options, $user_subscriptions)) {
							$mail_lists_to_add = array_merge(
								$mail_lists_to_add, 
								get_option("crpc_mail_list_mgr_" . $canonical_name . "_options")
							);
						} 
					} else {
						$mail_lists_to_remove = array_merge(
							$mail_lists_to_remove, 
							get_option("crpc_mail_list_mgr_" . $canonical_name . "_options")
						);
					}
				}
			}
		}
		if(count($mail_lists_to_add)) {
			subscribe_user($mailpoet_api, $subscriber["id"], $mail_lists_to_add);
		} 
		if(count($mail_lists_to_remove)) {
			unsubscribe_user($mailpoet_api, $subscriber["id"], $mail_lists_to_remove);
		} 
		
    }
    return;
}

function subscribe_user($mailpoet_api, $user_id, $lists) {
	/**
	 * Subscribe the user from the lists provided
	 *
	 * @param object $mailpoet_api
	 * @param int $user_id
	 * @param array $lists
	 */
    try {
        $subscribe = $mailpoet_api->subscribeToLists($user_id, $lists);
    } catch(\Throwable $th) {
        return "unable to add to lists - " . $th;
    }
    return $subscribe;
}

function unsubscribe_user($mailpoet_api, $user_id, $lists) {
	/**
	 * Unsubscribe the user from the lists provided
	 *
	 * @param object $mailpoet_api
	 * @param int $user_id
	 * @param array $lists
	 */
    try {
        $unsubscribe = $mailpoet_api->unsubscribeFromLists($user_id, $lists);
    } catch(\Throwable $th) {
        return "unable to remove from lists - " . $th;
    }
    return $unsubscribe;
}

function crpc_mail_list_mgr_caller() {
	/**
	 * Iterate the WordPress users and determine whether they should be added or removed from subscriptions. 
	 */
    $mailpoet_api = \MailPoet\API\API::MP("v1");
    try {
		$users = get_users();
		foreach ($users as $user) {
        	crpc_mail_list_mgr_user_iter($mailpoet_api, $user);
    	}
		add_settings_error(
			"crpc_mail_list_mgrs_messages", 
			"crpc_mail_list_mgr_message", 
			__("Users updated in mailing lists...", 
			"crpc_mail_list_mgr") , 
			"updated"
		);
	} catch(\Throwable $th) {
    	add_settings_error(
			"crpc_mail_list_mgrs_messages", 
			"crpc_mail_list_mgr_message", 
			__(print_r($th), "crpc_mail_list_mgr"),
			"error"
		);
	}
}

/**
 * Developers section callback function.
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function crpc_mailer_section_dev_callback( $args ) {
    
	?>
	<p>This is a custom plugin to assist with mapping user metadata to respective CRPC mailing lists.
	</p>
	<p>Assign the correct mailing list from the dropdown options below.</p>
	<?php
}

function crpc_mail_list_mgr_settings_init() {
	/**
	 * Dynamically creates option stores
	 */
    if (!class_exists(\MailPoet\API\API::class)) {
        add_settings_error(
			"crpc_mail_list_mgrs_messages", 
			"crpc_mail_list_mgr_message", 
			__("Unable to locate the MailPoet API... is the plugin activated?", "crpc_mail_list_mgr"),
			"error"
		);
        return;
    }

	$mailpoet_api = \MailPoet\API\API::MP("v1");
    $mailing_lists = $mailpoet_api->getlists();
	if(!count($mailing_lists)) {
		add_settings_error(
			"crpc_mail_list_mgrs_messages", 
			"crpc_mail_list_mgr_message", 
			__("In order to map attributes to mailing lists, the lists must first be created in the <a href='admin.php?page=mailpoet-lists' target='_blank'>MailPoet Lists Page</a>. </br>Once this is complete, the lists will appear in the dropdowns below for selection.", 
			"crpc_mail_list_mgr"),
			"error"
		);
	}

    // Register a new section in the "crpc_mail_list_mgr" page.
    add_settings_section(
		"crpc_mail_list_mgr_section_developers", 
		__('Dan\'s CRPC mailing list management tool', "crpc_mail_list_mgr"), 
		"crpc_mailer_section_dev_callback", 
		"crpc_mail_list_mgr"
	);

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
				"crpc_mail_list_mgr_custom_data" => "custom",
				"mailing_lists" => $mailing_lists,
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
    select_fields($args, $option);
}

function select_fields($args, $option_type) {
	/**
	 * Dynamically creates the dropdown options for the $option_type
	 * - the "label_for" key value is used for the "for" attribute of the <label>.
	 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
	 *
	 * @param array $args
	 * @param string $option_type
	 */
    $options = get_option("crpc_mail_list_mgr_" . $option_type . "_options"); ?>
	<select
		id="<?php echo esc_attr($args["label_for"]); ?>"
		data-custom="<?php echo esc_attr($args["crpc_mail_list_mgr_custom_data"]); ?>"
		name="crpc_mail_list_mgr_<?php echo esc_attr($option_type); ?>_options[<?php echo esc_attr($args["label_for"]); ?>]">
		<option value='' >Pick one...</option>
		<?php foreach ($args["mailing_lists"] as $list) { ?>
		<option value=<?php echo $list["id"]; ?> <?php echo isset($options[$args["label_for"]]) ? selected($options[$args["label_for"]], $list["id"], false) : ""; ?>>
			<?php esc_html_e($list["name"], "crpc_mail_list_mgr"); ?>
		</option>
		<?php } ?>
	</select>
	<?php
}
function plugin_options_general_page_html() {
	if (!current_user_can("manage_options")) {
        return;
    }

	?>
	<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
	<p>Some generic text about the plugin, and maybe some information about where things are...</p>

<?php
}

function plugin_options_help_page_html() {
	if (!current_user_can("manage_options")) {
        return;
    }

	?>
	<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
	<p>For any assistance with this site contact the club secretary, or contact Dan for help with this plugin.</p>
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
        add_settings_error(
			"crpc_mail_list_mgrs_messages", 
			"crpc_mail_list_mgr_message", 
			__("Settings Saved", "crpc_mail_list_mgr"),
			"updated"
		);
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
  	<form action="admin.php?page=crpc-plugin-list-mgr" method="post">

		<?php // this is a WordPress security feature - see: https://codex.wordpress.org/WordPress_Nonces
			wp_nonce_field("crpc_mail_list_mgr_clicked"); 
		?>

		<input type="hidden" value="true" name="crpc_mail_list_mgr" />
		<?php if (class_exists(\MailPoet\API\API::class)) { submit_button("Run Subscription Sorter!", "secondary");} ?>
	</form>

  </div>

	<?php 
	// show error/update messages
	settings_errors("crpc_mail_list_mgrs_messages");
}
