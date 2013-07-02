<?php

class AdControl_Params {

	/**
	 * Setup parameters for serving the ads
	 *
	 * @since 0.1
	 */
	public function __construct() {
		$this->mobile_device = ac_is_mobile( 'any', true );
		$this->theme = wp_get_theme()->Name;
		$this->targeting_tags = array(
			'WordAds'	=>	1,
			
		);
	}

	/**
	 * @return boolean true if the user is browsing on a mobile device (iPad not included)
	 *
	 * @since 0.1
	 */
	public function is_mobile() {
		return ! empty( $this->mobile_device );
	}

	/**
	 * @return boolean true if user is browsing in iOS device
	 *
	 * @since 0.1
	 */
	public function is_ios() {
		return in_array( $this->get_device(), array( 'ipad', 'iphone', 'ipod' ) );
	}

	/**
	 * Returns the user's device (see user-agent.php) or 'desktop'
	 * @return string user device
	 *
	 * @since 0.1
	 */
	public function get_device() {
		global $agent_info;

		if ( ! empty( $this->mobile_device ) )
			return $this->mobile_device;

		if ( $agent_info::is_ipad() )
			return 'ipad';

		return 'desktop';
	}

	/**
	 * @return string The type of page that is being loaded
	 *
	 * @since 0.1
	 */
	public function get_page_type() {
		if ( ! empty( $this->page_type ) )
			return $this->page_type;

		if ( self::is_static_home() ) {
			$this->page_type = 'static_home';
		} else if ( is_home() ) {
			$this->page_type = 'home';
		} else if ( is_page() ) {
			$this->page_type = 'page';
		} else if ( is_single() ) {
			$this->page_type = 'post';
		} else if ( is_search() ) {
			$this->page_type = 'search';
		} else if ( is_category() ) {
			$this->page_type = 'category';
		} else if ( is_archive() ) {
			$this->page_type = 'archive';
		} else {
			$this->page_type = 'wtf';
		}

		return $this->page_type;
	}

	/**
	 * Returns true if page is static home
	 * @return boolean true if page is static home
	 */
	public static function is_static_home() {
		return is_front_page() &&
			'page' == get_option( 'show_on_front' ) &&
			get_option( 'page_on_front' );
	}
}
