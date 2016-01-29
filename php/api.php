<?php

/**
 * Methods for accessing data through the WPCOM REST API
 *
 * @since 0.2
 */
class AdControl_API {

	private static $tos_signed = null;
	private static $wordads_status = null;

	/**
	 * Returns status of WordAds Terms of Service (and store it in options)
	 * @return boolean true if TOS has been signed
	 *
	 * @since 0.2
	 */
	public static function get_tos_status() {
		global $ac_tos_response;
		if ( Jetpack::is_development_mode() ) {
			return true;
		}

		// only need to check once
		if ( ! is_null( self::$tos_signed ) ) {
			return self::$tos_signed;
		}

		$endpoint = sprintf( '/sites/%d/wordads/tos', Jetpack::get_option( 'id' ) );
		$ac_tos_response = $response = Jetpack_Client::wpcom_json_api_request_as_blog( $endpoint );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'api_error', __( 'Error connecting to API.', 'adcontrol' ), $response );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		self::$tos_signed = isset( $body->tos ) && 'signed' == $body->tos;

		return self::$tos_signed;
	}

	/**
	 * Grab WordAds Terms of Service status from WP.com API and store as option
	 *
	 * @since 0.2
	 */
	static function update_tos_status_from_api() {
		$status = self::get_tos_status();
		if ( ! is_wp_error( $status ) ) {
			$options = get_option( 'adcontrol_settings', array() );
			$options['tos'] = $status;
			update_option( 'adcontrol_settings', $options );
		}
	}

	/**
	 * Returns site's WordAds status
	 * @return array boolean values for 'approved' and 'active'
	 *
	 * @since 0.2
	 */
	public static function get_wordads_status() {
		global $ac_status_response;
		if ( Jetpack::is_development_mode() ) {
			self::$wordads_status = array(
				'approved' => true,
				'active'   => true
			);

			return self::$wordads_status;
		}

		$endpoint = sprintf( '/sites/%d/wordads/status', Jetpack::get_option( 'id' ) );
		$ac_status_response = $response = Jetpack_Client::wpcom_json_api_request_as_blog( $endpoint );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'api_error', __( 'Error connecting to API.', 'adcontrol' ), $response );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		self::$wordads_status = array(
			'approved' => $body->approved,
			'active'   => $body->active
		);

		return self::$wordads_status;
	}

	/**
	 * Returns status of WordAds approval.
	 * @return boolean true if site is WordAds approved
	 *
	 * @since 0.2
	 */
	public static function is_wordads_approved() {
		if ( is_null( self::$wordads_status ) ) {
			self::get_wordads_status();
		}

		return self::$wordads_status['approved'];
	}

	/**
	 * Returns status of WordAds active.
	 * @return boolean true if ads are active on site
	 *
	 * @since 0.2
	 */
	public static function is_wordads_active() {
		if ( is_null( self::$wordads_status ) ) {
			self::get_wordads_status();
		}

		return self::$wordads_status['active'];
	}

	/**
	 * Grab WordAds status from WP.com API and store as option
	 *
	 * @since 0.2
	 */
	static function update_wordads_status_from_api() {
		$status = self::get_wordads_status();
		if ( ! is_wp_error( $status ) ) {
			$options = get_option( 'adcontrol_settings', array() );
			$options['wordads_approved'] = self::is_wordads_approved();
			$options['wordads_active'] = self::is_wordads_active();
			update_option( 'adcontrol_settings', $options );
		}
	}
}
