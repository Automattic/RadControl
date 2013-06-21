<?php

class WordAds_Admin {

	function __construct() {
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
	}

	function add_options_page() {
		add_options_page(
			'WordAds',
			'WordAds',
			'manage_options',
			'wordads',
			array( $this, 'options_page' )
		);
	}

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
