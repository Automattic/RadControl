<?php

/**
 * A collection of ajax calls for AdControl
 */
class AdControl_Ajax {

	static function load_fallback() {
		$options = array_merge(
			get_option( 'adcontrol_settings',  array() ),
			get_option( 'adcontrol_advanced_settings', array() )
		);

		if ( $options['adsense_set'] ) {
			require_once( ADCONTROL_ROOT . '/php/adsense.php' );
			$pub = $options['publisher_id'];
			$tag = $options['tag_id'];
			$unit = $options['tag_unit'];
			$width = AdControl::$ad_tag_ids[$unit]['width'];
			$height = AdControl::$ad_tag_ids[$unit]['height'];
			$url = isset( $_GET['url'] ) ? esc_url( $_GET['url'] ) : home_url();
			echo AdControl_Adsense::get_synchronous_adsense(
				$pub,
				$tag,
				$width,
				$height,
				$url
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
