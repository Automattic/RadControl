<?php

class WordAds_Admin {

	/**
	 * Instantiate admin code
	 *
	 * @since 0.1
	 */
	function __construct() {
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
	}

	/**
	 * Add the options page to settings admin menu
	 *
	 * @since 0.1
	 */
	function add_options_page() {
		add_options_page(
			'WordAds',
			'WordAds',
			'manage_options',
			'wordads',
			array( $this, 'options_page' )
		);
	}

	/**
	 * Code for the options page
	 *
	 * @since 0.1
	 */
	function options_page() {
		echo <<<HTML
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2>WordAds Settings</h2>
		</div>
HTML;
	}
}

global $wordads_admin;
$wordads_admin = new WordAds_Admin();
