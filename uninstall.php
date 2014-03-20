<?php
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

if ( ! is_multisite() ) {
	delete_option( 'adcontrol_settings' );
	delete_option( 'adcontrol_advanced_settings' );
} else {
	global $wpdb;
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	$original_blog_id = get_current_blog_id();
	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );
		delete_option( 'adcontrol_settings' );
		delete_option( 'adcontrol_advanced_settings' );
	}

	switch_to_blog( $original_blog_id );
}

// drops mic
