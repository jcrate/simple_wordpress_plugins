<?php

/*
Plugin Name: Login Redirector
Plugin URI: http://www.sefa.com
Description: Takes user to their primary site when they log in.
Author: Jim Crate
Version: 1.0
Author URI: http://www.sefa.com
Network: true
*/


function mulr_allowed_redirect_hosts($content) {
	$allowed_domains = [];
	$sites = get_sites();
	foreach ($sites as $site) {
		$allowed_domains[] = $site->domain;
	}
	// error_log("allowed_redirect_hosts: ". implode(', ', $allowed_domains));
	return $allowed_domains;
}
add_filter( 'allowed_redirect_hosts' , 'mulr_allowed_redirect_hosts' , 10 );

function mulr_login_redirect( $redirect_to, $user )
{
	// error_log("login_redirect: {$redirect_to}, user id {$user->ID}");
	if ($user->ID != 0) {
		$user_info = get_userdata($user->ID);
        if ($user_info->primary_blog) {
            $primary_url = get_blogaddress_by_id($user_info->primary_blog);
			// error_log("login_redirect: primary blog {$user_info->primary_blog}, url {$primary_url}");
            if ($primary_url) {
				$redirect_to = $primary_url;
				// error_log("validated redirect: ". wp_validate_redirect($primary_url));
            }
        }
    }
    return $redirect_to;
}
add_filter('woocommerce_login_redirect','mulr_login_redirect', 10, 3);

?>

