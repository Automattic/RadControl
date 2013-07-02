<?php

class AdControl_No_Jetpack {

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
			'AdControl',
			'AdControl',
			'manage_options',
			'adcontrol',
			array( $this, 'options_page' )
		);
	}

	/**
	 * Code for the options page
	 *
	 * @since 0.1
	 */
	function options_page() {
		$notice = __( 'AdControl requires Jetpack to be installed and activated at this time.', 'adcontrol' );
		echo <<<HTML
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2>AdControl Settings</h2>
			<p>$notice</p>
		</div>
HTML;
	}
}
new AdControl_No_Jetpack();
