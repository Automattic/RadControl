<?php

/**
 * A collection of ajax calls for AdControl
 */
class AdControl_Ajax {

	/**
	 * Grab whatever fallback we need
	 *
	 * @since 0.1
	 */
	static function load_fallback() {
		$options = array_merge(
			get_option( 'adcontrol_settings',  array() ),
			get_option( 'adcontrol_advanced_settings', array() )
		);

		if ( $options['adsense_fallback_set'] ) {
			require_once( ADCONTROL_ROOT . '/php/networks/adsense.php' );
			$pub = $options['adsense_publisher_id'];
			$tag = $options['adsense_fallback_tag_id'];
			$unit = $options['adsense_fallback_tag_unit'];
			$width = AdControl::$ad_tag_ids[$unit]['width'];
			$height = AdControl::$ad_tag_ids[$unit]['height'];
			$url = isset( $_GET['url'] ) ? $_GET['url'] : home_url();
			echo AdControl_Adsense::get_synchronous_adsense(
				$pub, $tag, $width, $height, $url
			);
		} else {
			echo ' ';
		}

		die();
	}
}

// actions for the ajax
add_action( 'wp_ajax_adcontrol_load_fallback', array( 'AdControl_Ajax', 'load_fallback' ) );
add_action( 'wp_ajax_nopriv_adcontrol_load_fallback', array( 'AdControl_Ajax', 'load_fallback' ) );
