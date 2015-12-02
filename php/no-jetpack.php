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
		global $pagenow;

		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_filter( 'plugin_action_links_' . ADCONTROL_BASENAME, array( $this, 'settings_link' ) );

		// Alert admins of missing steps on appropriate plugin pages
		if ( current_user_can( 'manage_options' ) && 'plugins.php' == $pagenow ) {
			add_action( 'admin_notices', array( $this, 'alert_jetpack' ) );
		}
	}

	/**
	 * Give an admin notice that they need to install and connect Jetpack
	 * @since 0.2
	 */
	function alert_jetpack() {
		$notice = sprintf(
			__( 'AdControl requires %sJetpack%s to be installed and connected at this time. %sHelp getting started.%s', 'adcontrol' ),
			'<a href="http://jetpack.me/" target="_blank">', '</a>',
			'<a href="http://jetpack.me/support/getting-started-with-jetpack/" target="_blank">', '</a>'
		);

        echo <<<HTML
        <div class="notice error is-dismissible">
        	<p>$notice</p>
        </div>
HTML;
	}

	/**
	 * Add settings link on plugin page
	 *
	 * @since 0.1
	 */
	function settings_link( $links ) {
		$settings_link = '<a href="http://jetpack.me/" target="_blank">Install Jetpack</a>';
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
			__( 'AdControl requires %sJetpack%s to be installed and connected at this time. %sHelp getting started.%s', 'adcontrol' ),
			'<a href="http://jetpack.me/" target="_blank">', '</a>',
			'<a href="http://jetpack.me/support/getting-started-with-jetpack/" target="_blank">', '</a>'
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
