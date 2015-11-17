<?php

/**
 * Methods for accessing data through the WPCOM REST API
 *
 * @since 0.2
 */
class AdControl_API {

	private static $status = null;

	/**
	 * Returns status of WordAds Terms of Service
	 * @return boolean true if TOS has been signed
	 *
	 * @since 0.2
	 */
	public static function get_tos_status() {
		if ( Jetpack::is_development_mode() ) {
			return true;
		}

		$endpoint = sprintf( '/sites/%d/wordads/tos', Jetpack::get_option( 'id' ) );
		$response = Jetpack_Client::wpcom_json_api_request_as_blog( $endpoint );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'api_error', __( 'Error connecting to API.', 'adcontrol' ), $response );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		return isset( $body->tos ) && 'signed' == $body->tos;
	}

	/**
	 * Returns site's WordAds status
	 * @return array boolean values for 'approved' and 'active'
	 *
	 * @since 0.2
	 */
	public static function get_wordads_status() {
		if ( Jetpack::is_development_mode() ) {
			return array(
				'approved' => true,
				'active'   => true
			);
		}

		$endpoint = sprintf( '/sites/%d/wordads/status', Jetpack::get_option( 'id' ) );
		$response = Jetpack_Client::wpcom_json_api_request_as_blog( $endpoint );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'api_error', __( 'Error connecting to API.', 'adcontrol' ), $response );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		self::$status = array(
			'approved' => $body->approved,
			'active'   => $body->active
		);

		return self::$status;
	}

	/**
	 * Returns status of WordAds approval.
	 * @return boolean true if site is WordAds approved
	 *
	 * @since 0.2
	 */
	public static function is_wordads_approved() {
		if ( is_null( self::$status ) ) {
			self::get_wordads_status();
		}

		return self::$status['approved'];
	}

	/**
	 * Returns status of WordAds active.
	 * @return boolean true if ads are active on site
	 *
	 * @since 0.2
	 */
	public static function is_wordads_active() {
		if ( is_null( self::$status ) ) {
			self::get_wordads_status();
		}

		return self::$status['active'];
	}
}
