<?php

/**
 * Methods for accessing data through the WPCOM REST API
 *
 * @since 0.2
 */
class AdControl_API {

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
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		return isset( $body->tos ) && 'signed' == $body->tos;
	}
}
