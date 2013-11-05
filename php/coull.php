<?php

/**
 * Loads Coull script in footer
 *
 * @since 0.1
 */
class AdControl_Coull {

	/**
	 * Params from main AdControl
	 */
	private $params;

	public function __construct( $params ) {
		$this->params = $params;
		$this->init();
	}

	/**
	 * Enqueue special scripts and add action hooks
	 */
	public function init() {
		if ( current_theme_supports( 'infinite-scroll' ) &&
				class_exists( 'The_Neverending_Home_Page' ) &&
				The_Neverending_Home_Page::got_infinity() ) {
			return;
		}

		$http = is_ssl() ? 'https' : 'http';
		wp_enqueue_script(
			'coull-loader',
			$http . '://mash.network.coull.com/getaffiliatorjs?pid=8165&website_ref=' . $this->params->blog_id,
			array(),
			false,
			true
		);
	}
}
