<?php

/**
 * AdControl cron tasks
 *
 * @since 0.2
 */
class AdControl_Cron {

	/**
	 * Add the actions the cron tasks will use
	 *
	 * @since 0.2
	 */
	function __construct() {
		add_action( 'adcontrol_cron_status', array( $this, 'update_wordads_status' ) );
	}

	/**
	 * Registered scheduled events on activation
	 *
	 * @since 0.2
	 */
	static function activate() {
		wp_schedule_event( time(), 'daily', 'adcontrol_cron_status' );
	}

	/**
	 * Clear scheduled hooks on deactivation
	 *
	 * @since 0.2
	 */
	static function deactivate() {
		wp_clear_scheduled_hook( 'adcontrol_cron_status' );
	}

	/**
	 * Grab WordAds status from WP.com API
	 *
	 * @since 0.2
	 */
	static function update_wordads_status() {
		AdControl_API::update_wordads_status_from_api();
	}
}

global $adcontrol_cron;
$adcontrol_cron = new AdControl_Cron();
