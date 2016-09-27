<?php

class AdControl_Params {

	/**
	 * Setup parameters for serving the ads
	 *
	 * @since 0.1
	 */
	public function __construct() {
		$this->options = array_merge(
			get_option( 'adcontrol_settings',  array() ),
			get_option( 'adcontrol_advanced_settings', array() )
		);
		$this->url = ( is_ssl() ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST']  . $_SERVER['REQUEST_URI'];
		if ( ! ( false === strpos( $this->url, '?' ) ) && ! isset( $_GET['p'] ) ) {
			$this->url = substr( $this->url, 0, strpos( $this->url, '?' ) );
		}
		$this->cloudflare = self::is_cloudflare();
		$this->blog_id = Jetpack::get_option( 'id', 0 );
		$this->mobile_device = jetpack_is_mobile( 'any', true );
		$this->theme = wp_get_theme()->Name;
		$this->targeting_tags = array(
			'AdControl' => 1,
			'BlogId'    => Jetpack::is_development_mode() ? 0 : Jetpack::get_option( 'id' ),
			'Domain'    => esc_js( parse_url( home_url(), PHP_URL_HOST ) ),
			'PageURL'   => esc_js( $this->url ),
			'LangId'    => false !== strpos( get_bloginfo( 'language' ), 'en' ) ? 1 : 0, // TODO something else?
			'AdSense'   => empty( $this->options['adsense_fallback_set'] ) ? 0 : 1,
			'AdSafe'    => 1, // TODO
		);
	}

	/**
	 * Convenience function to collect tags and categories of current page
	 *
	 * @since 0.1
	 */
	static function get_tags_and_categories() {
		global $tags_and_cats;

		// If we've already looked up tags and categories during this page,
		// we'll have an array (may be empty if post has no tags or cat, or if not a post page),
		if ( ! is_array( $tags_and_cats ) ) {
			$tags_and_cats = array();
			foreach ( get_the_category() as $cat ) {
				if ( 1 != $cat->cat_ID ) { // don't add 'Uncategorized'
					$tags_and_cats[] = strtolower( $cat->category_nicename );
				}
			}

			foreach ( (array) get_the_tags() as $tag ) {
				if ( ! empty($tag) ) {
					$tags_and_cats[] = strtolower( $tag->slug );
				}
			}
		}
		return $tags_and_cats;
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
	 * @return boolean true if site is being served via CloudFlare
	 *
	 * @since 1.1.1
	 */
	public static function is_cloudflare() {
		if ( defined( 'ADCONTROL_CLOUDFLARE' ) ) {
			return true;
		}
		if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			return true;
		}
		if ( isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
			return true;
		}
		if ( isset( $_SERVER['HTTP_CF_VISITOR'] ) ) {
			return true;
		}

		return false;
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

		if ( ! empty( $this->mobile_device ) ) {
			return $this->mobile_device;
		}

		if ( $agent_info->is_ipad() ) {
			return 'ipad';
		}

		return 'desktop';
	}

	/**
	 * @return string The type of page that is being loaded
	 *
	 * @since 0.1
	 */
	public function get_page_type() {
		if ( ! empty( $this->page_type ) ) {
			return $this->page_type;
		}

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
	 *
	 * @since 0.1
	 */
	public static function is_static_home() {
		return is_front_page() &&
			'page' == get_option( 'show_on_front' ) &&
			get_option( 'page_on_front' );
	}

	/**
	 * Logic for if we should show an ad
	 *
	 * @since 0.1
	 */
	public static function should_show() {
		global $wp_query;
		if ( is_single() || ( is_page() && ! is_home() ) ) {
			return true;
		}

		// TODO this would be a good place for allowing the user to specify
		if ( ( is_home() || is_archive() || is_search() ) && 0 == $wp_query->current_post ) {
			return true;
		}

		return false;
	}

	/**
	 * Logic for if we should show a mobile ad
	 *
	 * @since 0.1
	 */
	public static function should_show_mobile() {
		global $wp_query;

		if ( ! in_the_loop() || ! did_action( 'wp_head' ) ) {
			return false;
		}

		if ( is_single() || ( is_page() && ! is_home() ) ) {
			return true;
		}

		if ( ( is_home() || is_archive() ) && 0 == $wp_query->current_post ) {
			return true;
		}

		return false;
	}
}
