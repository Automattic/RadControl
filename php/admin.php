<?php

/**
 * The standard set of admin pages for the user if Jetpack is installed
 */
class AdControl_Admin {

	private $valid_settings = array(
		'show_to_logged_in',
		'tos',
		'leaderboard',
		'leaderboard_mobile',
		'wordads_approved',
		'wordads_active',
	);
	private $active_tab = 'adcontrol_settings';
	private $options = array();
	private $options_advanced = array();
	private $basic_settings_key = 'adcontrol_settings';
	private $advanced_settings_key = 'adcontrol_advanced_settings';
	private $plugin_options_key = 'adcontrol';
	private $show_revenue = true;

	/**
	 * @since 0.1
	 */
	function __construct() {
		global $pagenow;

		if ( isset( $_GET['page'] ) && 'adcontrol' == $_GET['page'] && ( ! isset( $_GET['tab'] ) || 'adcontrol_settings' == $_GET['tab'] ) ) {
			AdControl_API::update_wordads_status_from_api();
			AdControl_API::update_tos_status_from_api();
		}

		$this->blog_id = Jetpack::get_option( 'id', 0 );
		$this->options = get_option( $this->basic_settings_key, array() );
		$this->options_advanced = get_option( $this->advanced_settings_key, array() );

		if ( $this->get_option( 'wordads_approved' ) && $this->get_option( 'tos' ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 11 );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		} else if ( $this->get_option( 'wordads_approved' ) ) {
			add_action( 'admin_menu', array( $this, 'tos_menu' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'not_approved_menu' ) );
		}

		add_filter( 'plugin_action_links_' . ADCONTROL_BASENAME, array( $this, 'settings_link' ) );

		// Alert admins of missing steps on appropriate plugin pages
		if ( current_user_can( 'manage_options' ) && 'plugins.php' == $pagenow &&
				( ! $this->get_option( 'tos' ) || ! $this->get_option( 'wordads_approved' ) ) ) {
			add_action( 'admin_notices', array( $this, 'alert_setup_steps' ) );
		}

		if ( isset( $_GET['ac_debug'] ) ) {
			add_action( 'admin_notices', array( $this, 'debug_output' ) );
		}
	}

	/**
	 * Output the API connection debug
	 * @since 1.0.4
	 */
	function debug_output() {
		global $ac_tos_response, $ac_status_response;
		$response = 'tos' == $_GET['ac_debug'] ? $ac_tos_response : $ac_status_response;
		if ( empty( $response ) ) {
			$response = 'No response from API :(';
		} else {
			$response = print_r( $response, 1 );
		}

		$tos = $this->get_option( 'tos' ) ?
			'<span style="color:green;">Yes</span>' :
			'<span style="color:red;">No</span>';
		$status = $this->get_option( 'wordads_approved' ) ?
			'<span style="color:green;">Yes</span>' :
			'<span style="color:red;">No</span>';

		$type = $this->get_option( 'tos' ) && $this->get_option( 'wordads_approved' ) ?
			'updated' :
			'error';

		echo <<<HTML
		<div class="notice $type is-dismissible">
			<p>TOS: $tos | Status: $status</p>
			<pre>$response</pre>
		</div>
HTML;
	}

	/**
	 * Alert admin of required steps to finish setup
	 * @since 0.2
	 */
	function alert_setup_steps() {
		$requires = __( 'AdControl still requires the following actions to activate:', 'adcontrol' );
		$missing = '';
		if ( ! $this->get_option( 'wordads_approved' ) ) {
			$notice = sprintf(
				__( 'We are still waiting for your %sapplication%s to be approved. In the mean time, feel free to %scontact us%s with any questions.', 'adcontrol' ),
				'<a href="https://wordads.co/signup/" target="_blank">', '</a>',
				'<a href="https://wordads.co/contact/" target="_blank">', '</a>'
			);

			$missing .= "<li>$notice</li>";
		}

		if ( ! $this->get_option( 'tos' ) ) {
			$notice = sprintf(
				__( 'Please accept the %sWordAds Terms of Service%s in your %sSettings%s.', 'adcontrol' ),
					'<a href="https://wordpress.com/tos-wordads/" target="_blank">', '</a>',
					'<a href="https://wordpress.com/ads/settings/' . $this->blog_id . '" target="_blank">', '</a>'
			);

			$missing .= "<li>$notice</li>";
		}

		echo <<<HTML
		<div class="notice error is-dismissible">
			<p>$requires</p>
			<ul style="list-style:inherit;padding-left:40px;">
				$missing
			</ul>
		</div>
HTML;
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
	 * @since 0.1
	 */
	function get_option( $key, $default = null ) {
		// Options are limited to those specified in $this->valid_settings
		if ( ! in_array( $key, $this->valid_settings ) ) {
			return;
		}

		$option = $default;
		if ( isset( $this->options[ $key ] ) ) {
			$option = $this->options[ $key ];
		} elseif ( isset( $this->options_advanced[ $key ] ) ) {
			$option = $this->options_advanced[ $key ];
		}
		return $option;
	}

	/**
	 * @since 0.1
	 */
	function admin_scripts( $hook ) {
		if ( 'settings_page_adcontrol' != $hook ) {
			return;
		}

		wp_enqueue_script(
			'adcontrol-admin',
			ADCONTROL_URL . 'js/admin.js',
			array( 'jquery' ),
			'2013-07-22',
			true
		);
	}

	/**
	 * @since 0.1
	 */
	function userdash_show_page() {
		$this->userdash_show_settings();
	}

	/**
	 * @since 0.2
	 */
	function userdash_tos() {
		$settings = __( 'AdControl Settings', 'adcontrol' );
		$notice = sprintf(
			__( 'Please accept the %sWordAds Terms of Service%s in your %sSettings%s.', 'adcontrol' ),
			'<a href="https://wordpress.com/tos-wordads/" target="_blank">', '</a>',
			'<a href="https://wordpress.com/ads/settings/' . $this->blog_id . '" target="_blank">', '</a>'
		);
		echo <<<HTML
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2>$settings</h2>
			<p>$notice</p>
		</div>
HTML;
	}

	/**
	 * @since 0.2
	 */
	function userdash_not_approved() {
		$settings = __( 'AdControl Settings', 'adcontrol' );
		$notice = sprintf(
			__( 'We are still waiting for your %sapplication%s to be approved. In the mean time, feel free to %scontact us%s with any questions.', 'adcontrol' ),
			'<a href="https://wordads.co/signup/" target="_blank">', '</a>',
			'<a href="https://wordads.co/contact/" target="_blank">', '</a>'
		);
		echo <<<HTML
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2>$settings</h2>
			<p>$notice</p>
		</div>
HTML;
	}

	/**
	 * @since 0.1
	 */
	function userdash_show_settings() {
		$href = 'https://wordpress.com/ads/earnings/' . $this->blog_id;
		if ( $this->is_paused() ) {
			echo '<div class="updated"><p><strong>' . __( 'WordAds is paused. Please choose which visitors should see ads.', 'adcontrol' ) . '</strong></p></div>';
		}

		if ( 0 !== $this->blog_id ):
		?>
		<p>
			<em><?php _e( 'Please login to our secure servers on WordPress.com to see your earnings details.', 'adcontrol' ); ?></em>
			<a class="button-secondary" href="<?php echo $href; ?>" target="_blank" style="margin-left:5px;height:22px;line-height:20px;">
				<?php _e( 'Show Me', 'adcontrol' ); ?>
			</a>
		</p>
		<?php endif ?>
		<form action="options.php" method="post" id="<?php echo esc_attr( $this->active_tab ); ?>">
			<?php
			wp_nonce_field( 'update-options' );
			settings_fields( $this->active_tab );
			do_settings_sections( $this->active_tab );
			submit_button( __( 'Save Changes', 'adcontrol' ), 'primary' );
			?>
		</form>

		<?php
	}

	/**
	 * @since 0.1
	 */
	function validate_settings( $settings ) {
		$to_save = array();
		if ( 'no' == $settings[ 'show_to_logged_in' ] ) {
			$to_save[ 'show_to_logged_in' ] = 'no';
		} else if ( 'pause' == $settings[ 'show_to_logged_in' ] ) {
			$to_save[ 'show_to_logged_in' ] = 'pause';
		} else {
			$to_save[ 'show_to_logged_in' ] = 'yes';
		}

		$to_save['leaderboard'] =
			isset( $settings['leaderboard'] ) && $settings['leaderboard'] ? 1 : 0;

		$to_save['leaderboard_mobile'] =
			isset( $settings['leaderboard_mobile'] ) && $settings['leaderboard_mobile'] ? 1 : 0;

		return $to_save;
	}

	/**
	 * @since 0.1
	 */
	function admin_menu() {
		add_options_page(
			__( 'AdControl Settings', 'adcontrol' ),
			'AdControl',
			'manage_options',
			'adcontrol',
			array( $this, 'userdash_show_page' )
		);
	}

	/**
	 * Add notice the user is awaiting on approval
	 *
	 * @since 0.2
	 */
	function tos_menu() {
		add_options_page(
			'AdControl',
			'AdControl',
			'manage_options',
			'adcontrol',
			array( $this, 'userdash_tos' )
		);
	}

	/**
	 * Add notice the user is awaiting on approval
	 *
	 * @since 0.2
	 */
	function not_approved_menu() {
		add_options_page(
			'AdControl',
			'AdControl',
			'manage_options',
			'adcontrol',
			array( $this, 'userdash_not_approved' )
		);
	}

	/**
	 * @since 0.1
	 */
	function register_settings() {
		register_setting(
			$this->basic_settings_key,
			$this->basic_settings_key,
			array( $this, 'validate_settings' )
		);

		$this->init_settings();
	}

	/**
	 * @since 0.1
	 */
	private function init_settings() {
		$section_name = 'adcontrol_settings';
		// Config section of the form
		add_settings_section(
			$section_name,
			__( 'Configuration Options', 'adcontrol' ),
			'__return_null',
			$this->basic_settings_key
		);

		add_settings_field(
			'adcontrol_userdash_show_to_logged_in_id',
			__( 'Show ads to:', 'adcontrol' ),
			array( $this, 'setting_show_to_logged_in' ),
			$this->basic_settings_key,
			$section_name,
			array( 'label_for' => 'radio_show_to_logged_in' )
		);

		add_settings_field(
			'adcontrol_userdash_leaderboard_id',
			__( 'Enable header unit:', 'adcontrol' ),
			array( $this, 'setting_leaderboard' ),
			$this->basic_settings_key,
			$section_name,
			array( 'label_for' => 'leaderboard' )
		);

		add_settings_field(
			'adcontrol_userdash_leaderboard_mobile_id',
			__( 'Enable mobile header unit:', 'adcontrol' ),
			array( $this, 'setting_leaderboard_mobile' ),
			$this->basic_settings_key,
			$section_name,
			array( 'label_for' => 'leaderboard_mobile' )
		);

		// TOS section of the form
		$section_name = 'adcontrol_section_general_tos';
		add_settings_section(
			$section_name,
			__( 'WordAds Terms of Service', 'adcontrol' ),
			'__return_null',
			$this->basic_settings_key
		);

		add_settings_field(
			'adcontrol_userdash_tos_id',
			sprintf( __( 'I have read and agree to the %sWordAds Terms of Service%s', 'adcontrol' ), '<br /><a href="http://wordpress.com/tos-wordads/" target="_blank">', '</a>' ),
			array( $this, 'setting_tos' ),
			$this->basic_settings_key,
			$section_name,
			array( 'label_for' => 'chk_agreement' )
		);
	}

	/**
	 * @since 0.1
	 */
	function setting_show_to_logged_in() {
		$show_to_logged_in = $this->get_option( 'show_to_logged_in' );
		if ( ! in_array( $show_to_logged_in, array( 'yes', 'no', 'pause' ) ) )
			$show_to_logged_in = 'yes';
		?>
		<p><input type="radio" name="<?php echo esc_attr( $this->basic_settings_key ); ?>[show_to_logged_in]" id="radio_show_to_logged_in" value="yes" <?php checked( $show_to_logged_in, 'yes' ); ?>/>
		<label for="radio_show_to_logged_in"> <?php _e( 'Every visitor', 'adcontrol' ); ?></label></p>
		<p><input type="radio" name="<?php echo esc_attr( $this->basic_settings_key ); ?>[show_to_logged_in]" id="radio_hide_from_logged_in" value="no" <?php checked( $show_to_logged_in, 'no' ); ?>/>
		<label for="radio_hide_from_logged_in"><?php _e( 'Every visitor, except logged-in users (fewer impressions)', 'adcontrol' ); ?></label></p>
		<p><input type="radio" name="<?php echo esc_attr( $this->basic_settings_key ); ?>[show_to_logged_in]" id="radio_hide_from_everyone" value="pause" <?php checked( $show_to_logged_in, 'pause' ); ?>/>
		<label for="radio_hide_from_everyone"><?php _e( 'Do not show any ads', 'adcontrol' ); ?></label></p>
		<?php
	}

	/**
	 * @since 1.0
	 */
	function setting_leaderboard() {
		$checked = checked( $this->get_option( 'leaderboard' ), 1, false );
		echo '<p><input type="checkbox" name="' . $this->basic_settings_key . '[leaderboard]" id="leaderboard" value="1" ' . $checked . ' /></p>';
	}

	/**
	 * @since 1.0.3
	 */
	function setting_leaderboard_mobile() {
		$checked = checked( $this->get_option( 'leaderboard_mobile', $this->get_option( 'leaderboard', 0 ) ), 1, false );
		echo '<p><input type="checkbox" name="' . $this->basic_settings_key . '[leaderboard_mobile]" id="leaderboard_mobile" value="1" ' . $checked . ' /></p>';
	}

	/**
	 * @since 0.1
	 */
	function setting_tos() {
		if ( 'signed' != $this->get_option( 'tos' ) ) {
			echo '<p><input type="checkbox" name="' . $this->basic_settings_key . '[tos]" id="chk_agreement" value="signed" /></p>';
		} else {
			echo '<span class="checkmark"></span>' .  __( 'Thank you for accepting the WordAds Terms of Service', 'adcontrol' );
		}
	}

	/**
	 * @since 0.1
	 */
	function is_paused() {
		return ( 'pause' == $this->get_option( 'show_to_logged_in' ) );
	}
}

global $adcontrol_admin;
$adcontrol_admin = new AdControl_Admin();
