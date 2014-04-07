<?php

/**
 * This special set of admin pages are run if the user hasn't installed Jetpack.
 */
class AdControl_No_Jetpack {

	/**
	 * Instantiate admin code
	 *
	 * @since 0.1
	 */
	function __construct() {
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_filter( 'plugin_action_links_' . ADCONTROL_BASENAME, array( $this, 'settings_link' ) );
	}

	/**
	 * Add settings link on plugin page
	 *
	 * @since 0.1
	 */
	function settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=adcontrol">Settings</a>';
		array_unshift( $links, $settings_link );
		return $links;
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
		$settings = __( 'AdControl Settings', 'adcontrol' );
		$notice = sprintf(
			__( 'AdControl requires %sJetpack%s to be installed and activated at this time.', 'adcontrol' ),
			'<a href="http://jetpack.me/support/getting-started-with-jetpack/" target="_blank">',
			'</a>'
		);
		echo <<<HTML
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2>$settings</h2>
			<p>$notice</p>
		</div>
HTML;
	}
}
new AdControl_No_Jetpack();
