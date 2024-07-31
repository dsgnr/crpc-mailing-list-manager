<?php
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

function set_user_mail_subscriptions($mailpoet_api, $user) {
    /**
     * Determine the current user lists and determine what lists to add/remove.
     *
     * @param object $mailpoet_api
     * @param object $user
     */
    require_once CRPC_ADMIN_PATH . 'lib/mail_manager/subscriptions.php';

    $sub = [
        "email" => sanitize_email($user->user_email),
        "first_name" => sanitize_text_field($user->first_name),
        "last_name" => sanitize_text_field($user->last_name),
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
        foreach (CRPC_ADMIN_MAIL_OPTIONS as $canonical_name => $friendly_name) {
            if (count(array_keys(get_option("crpc_admin_" . $canonical_name . "_mail_mgr_options") , true))) {
                foreach(get_option("crpc_admin_" . $canonical_name . "_mail_mgr_options") as $list_options) {
                    if(!empty($sub[$canonical_name] ?? null) && ($sub[$canonical_name] ?? null)) {
                        if(!in_array($list_options, $user_subscriptions)) {
                            $mail_lists_to_add = array_merge(
                                $mail_lists_to_add,
                                get_option("crpc_admin_" . $canonical_name . "_mail_mgr_options")
                            );
                        }
                    } else {
                        $mail_lists_to_remove = array_merge(
                            $mail_lists_to_remove,
                            get_option("crpc_admin_" . $canonical_name . "_mail_mgr_options")
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
?>