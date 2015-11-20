<?php

/**
 * The standard set of admin pages for the user if Jetpack is installed
 */
class AdControl_Admin {

	private $valid_settings = array(
		'show_to_logged_in',
		'show_on_frontpage',
		'show_on_ssl',
		'country',
		'paypal',
		'tos',
		'application_submitted',
		'adsense_publisher_id',
		'adsense_fallback',
		'adsense_leader',
		'adsense_fallback_tag_id',
		'adsense_fallback_tag_unit',
		'adsense_leader_tag_id',
		'adsense_leader_tag_unit',
		'amazon_match_buy',
		'enable_advanced_settings',
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
	private $tabs = array();

	/**
	 * @since 0.1
	 */
	function __construct() {
		$this->tabs = array(
			'adcontrol_settings'          => __( 'AdControl Settings', $this->plugin_options_key ),
			'adcontrol_advanced_settings' => __( 'Advanced Settings', $this->plugin_options_key ),
			'adcontrol_earnings'          => __( 'Earnings', $this->plugin_options_key ),
		);

		// check status on first admin load
		if ( ! isset( $_GET['tab'] ) ) {
			AdControl_Cron::update_wordads_status_from_api();
		}

		$this->blog_id = Jetpack::get_option( 'id', 0 );
		$this->options = get_option( $this->basic_settings_key, array() );
		$this->options_advanced = get_option( $this->advanced_settings_key, array() );

		if ( $this->get_option( 'wordads_approved' ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 11 );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_init', array( $this, 'register_advanced_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'not_approved_menu' ) );
		}

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
	 * @since 0.1
	 */
	function get_option( $key ) {
		// Options are limited to those specified in $this->valid_settings
		if ( ! in_array( $key, $this->valid_settings ) )
			return;
		$option = null;
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
		if ( 'settings_page_adcontrol' != $hook )
			return;

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
	function admin_tabs() {
		if ( isset( $_GET['tab'] ) ) {
			$this->active_tab = esc_attr( $_GET['tab'] );
		} else {
			$this->active_tab = 'adcontrol_settings';
		}
		screen_icon();

		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $this->tabs as $tab_key => $tab_caption ) {
			if ( ! isset( $this->options['enable_advanced_settings'] ) )
				continue;
			if ( 'adcontrol_advanced_settings' == $tab_key && ! $this->options['enable_advanced_settings'] == 1 )
				continue;
			if ( 'adcontrol_earnings' == $tab_key && ! $this->show_revenue )
				continue;
			$active = ( $tab_key == $this->active_tab ) ? ' nav-tab-active' : '';
			echo "<a class=\"nav-tab{$active}\" href=\"?page={$this->plugin_options_key}&tab={$tab_key}\">$tab_caption</a>";
		}
		echo '</h2>';
	}

	/**
	 * @since 0.1
	 */
	function userdash_show_page() {
		$this->admin_tabs();
		if ( 'adcontrol_earnings' == $this->active_tab ) {
			$this->userdash_show_revenue();
		} else {
			$this->userdash_show_settings();
		}
	}

	/**
	 * @since 0.2
	 */
	function userdash_not_approved() {
		$settings = __( 'AdControl Settings', 'adcontrol' );
		$notice = sprintf(
			__( 'We are still waiting for your application to be approved. In the mean time, feel free to %scontact us%s with any questions.', 'adcontrol' ),
			'<a href="https://wordads.co/contact/" target="_blank">',
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

	/**
	 * @since 0.1
	 */
	function userdash_show_settings() {
		if ( $this->is_paused() )
			echo '<div class="updated" id="wpcom-tip"><p><strong>' . __( 'WordAds is paused. Please choose which visitors should see ads.', $this->plugin_options_key ) . '</strong></p></div>';
		?>
		<form action="options.php" method="post" id="<?php echo esc_attr( $this->active_tab ); ?>">
			<?php
			wp_nonce_field( 'update-options' );
			settings_fields( $this->active_tab );
			do_settings_sections( $this->active_tab );
			submit_button( __( 'Save Changes' ), 'primary' );
			?>
		</form>
		<?php
	}

	/**
	 * @since 0.1
	 */
	function userdash_show_revenue() {
		$msg = sprintf(
			__( 'Please login to our %ssecure servers on WordPress.com%s to see your earnings details.', $this->plugin_options_key ),
			'<a href="https://wordpress.com/ads/earnings/' . $this->blog_id . '">',
			'</a>'
		);
		echo $msg;
	}

	/**
	 * @since 0.1
	 */
	function validate_settings( $settings ) {
		$to_save = array();
		if ( 'signed' == $this->get_option( 'tos' ) || ( isset( $settings[ 'tos' ] ) && 'signed' == $settings[ 'tos' ] ) ) {
			$to_save[ 'tos' ] = 'signed';
		} else {
			add_settings_error( 'tos', 'tos', __( 'You must agree to the Terms of Service.', $this->plugin_options_key ) );
		}

		if ( isset( $settings['country'] ) && 2 == strlen( $settings['country'] ) ) {
			$to_save[ 'country' ] = $settings[ 'country' ];
		} else {
			add_settings_error( 'country', 'country', __( 'Please select a country.', $this->plugin_options_key ) );
		}

		$to_save[ 'paypal' ] = $settings[ 'paypal' ];

		if ( 'no' == $settings[ 'show_to_logged_in' ] ) {
			$to_save[ 'show_to_logged_in' ] = 'no';
		} elseif ( 'pause' == $settings[ 'show_to_logged_in' ] ) {
			$to_save[ 'show_to_logged_in' ] = 'pause';
		} else {
			$to_save[ 'show_to_logged_in' ] = 'yes';
		}

		$to_save['enable_advanced_settings'] =
			isset( $settings['enable_advanced_settings'] ) && $settings['enable_advanced_settings'] ? 1 : 0;

		if ( ! $this->get_option( 'application_submitted' ) && isset( $to_save[ 'tos' ] ) && isset( $to_save[ 'country' ] ) /* && 0 != Jetpack::get_option( 'id', 0 ) */ ) {
			$response = wp_remote_post(
				ADCONTROL_APPLICATION_URL,
				array(
					'body' => array(
						'wordads-form' => '1',
						'adcontrol'    => '1',
						'jetpack_id'   => Jetpack::get_option( 'id', 0 ),
						'country'      => $to_save[ 'country' ],
						'paypal'       => $to_save[ 'paypal' ]
					)
				)
			);

			if ( is_wp_error( $response ) ) {
				$to_save[ 'tos' ] = '';
				add_settings_error(
					'tos',
					'tos',
					sprintf(
						__( 'Error submitting AdControl application: %s', $this->plugin_options_key ),
						$response->get_error_message()
					)
				);
				return $to_save;
			}

			$response_body = json_decode( $response['body'] );
			if ( empty( $response_body->success ) ) {
				$to_save[ 'tos' ] = '';
				add_settings_error(
					'tos',
					'tos',
					sprintf(
						__( 'Error submitting AdControl application: %s', $this->plugin_options_key ),
						$response_body->reason
					)
				);
			} else {
				$to_save[ 'application_submitted' ] = 1;
			}
		}

		return $to_save;
	}

	/**
	 * @since 0.1
	 */
	function validate_advanced_settings( $settings ) {
		$to_save = array();
		$to_save[ 'adsense_fallback_set' ] = 0;
		$to_save[ 'adsense_leader_set' ] = 0;

		if ( ! empty( $settings['adsense_publisher_id'] ) ) {
			$matches = array();
			if ( preg_match( '/^(pub-)?(\d+)$/', $settings['adsense_publisher_id'], $matches ) )
				$to_save[ 'adsense_publisher_id' ] = 'pub-' . esc_attr( $matches[2] );
			else
				add_settings_error( 'adsense_publisher_id', 'adsense_publisher_id', __( 'Publisher ID must be of form "pub-123456789"', $this->plugin_options_key ) );
		}

		if ( ! empty( $settings['adsense_fallback'] ) ) {
			$to_save['adsense_fallback'] = absint( $settings['adsense_fallback'] );

			if ( ! empty( $settings['adsense_fallback_tag_id'] ) && is_numeric( $settings['adsense_fallback_tag_id'] ) )
				$to_save[ 'adsense_fallback_tag_id' ] = esc_attr( $settings['adsense_fallback_tag_id'] );
			else
				add_settings_error( 'adsense_fallback_tag_id', 'adsense_fallback_tag_id', __( 'Tag ID must be of form "123456789"', $this->plugin_options_key ) );

			$to_save[ 'adsense_fallback_tag_unit' ] = esc_attr( $settings['adsense_fallback_tag_unit'] );

			if ( isset( $to_save[ 'adsense_publisher_id' ] ) && isset( $to_save['adsense_fallback_tag_id'] ) )
				$to_save[ 'adsense_fallback_set' ] = 1;
		}

		if ( ! empty( $settings['adsense_leader'] ) ) {
			$to_save['adsense_leader'] = absint( $settings['adsense_leader'] );

			if ( ! empty( $settings['adsense_leader_tag_id'] ) && is_numeric( $settings['adsense_leader_tag_id'] ) )
				$to_save[ 'adsense_leader_tag_id' ] = esc_attr( $settings['adsense_leader_tag_id'] );
			else
				add_settings_error( 'adsense_leader_tag_id', 'adsense_leader_tag_id', __( 'Tag ID must be of form "123456789"', $this->plugin_options_key ) );

			$to_save[ 'adsense_leader_tag_unit' ] = esc_attr( $settings['adsense_leader_tag_unit'] );

			if ( isset( $to_save[ 'adsense_publisher_id' ] ) && isset( $to_save['adsense_leader_tag_id'] ) )
				$to_save[ 'adsense_leader_set' ] = 1;
		}

		if ( ! empty( $settings['amazon_match_buy'] ) ) {
			$to_save['amazon_match_buy'] = absint( $settings['amazon_match_buy'] );
		}

		return $to_save;
	}

	/**
	 * @since 0.1
	 */
	function admin_menu() {
		add_options_page(
			__( 'AdControl Settings', $this->plugin_options_key ),
			'AdControl',
			'manage_options',
			$this->plugin_options_key,
			array( $this, 'userdash_show_page' )
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
	function register_advanced_settings() {
		register_setting(
			$this->advanced_settings_key,
			$this->advanced_settings_key,
			array( $this, 'validate_advanced_settings' )
		);

		$this->init_advanced_settings();
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
			__( 'Show ads to:', $this->plugin_options_key ),
			array( $this, 'setting_show_to_logged_in' ),
			$this->basic_settings_key,
			$section_name,
			array( 'label_for' => 'radio_show_to_logged_in' )
		);

		add_settings_field(
			'adcontrol_userdash_enable_advanced_settings',
			__( 'Enable Advanced Settings:', $this->plugin_options_key ),
			array( $this, 'setting_enable_advanced_settings' ),
			$this->basic_settings_key,
			$section_name,
			array( 'label_for' => 'enable_advanced_settings' )
		);

		// TOS section of the form
		$section_name = 'adcontrol_section_general_tos';
		add_settings_section(
			$section_name,
			__( 'WordAds Terms of Service', $this->plugin_options_key ),
			'__return_null',
			$this->basic_settings_key
		);

		add_settings_field(
			'adcontrol_userdash_tos_id',
			sprintf( __( 'I have read and agree to the %sWordAds Terms of Service%s', $this->plugin_options_key ), '<br /><a href="http://wordpress.com/tos-wordads/" target="_blank">', '</a>' ),
			array( $this, 'setting_tos' ),
			$this->basic_settings_key,
			$section_name,
			array( 'label_for' => 'chk_agreement' )
		);
	}

	/**
	 * @since 0.1
	 */
	private function init_advanced_settings() {
		$section = 'adcontrol_adsense_settings';
		// AdSense section
		add_settings_section(
			$section,
			__( 'AdSense Options', $this->plugin_options_key ),
			'__return_null',
			$this->advanced_settings_key
		);

		add_settings_field(
			'adcontrol_userdash_publisher_id',
			__( 'Publisher ID:', $this->plugin_options_key ),
			array( $this, 'setting_publisher_id' ),
			$this->advanced_settings_key,
			$section,
			array( 'label_for' => 'adsense_publisher_id' )
		);

		// TODO future release
		// add_settings_field(
		// 	'adcontrol_userdash_adsense_fallback',
		// 	__( 'Include AdSense fallback?', $this->plugin_options_key ),
		// 	array( $this, 'setting_adsense_fallback' ),
		// 	$this->advanced_settings_key,
		// 	$section,
		// 	array( 'label_for' => 'adsense_fallback' )
		// );

		// add_settings_field(
		// 	'adcontrol_userdash_fallback_tag_id',
		// 	__( 'Tag ID:', $this->plugin_options_key ),
		// 	array( $this, 'setting_fallback_tag_id' ),
		// 	$this->advanced_settings_key,
		// 	$section,
		// 	array( 'label_for' => 'adsense_fallback_tag_id' )
		// );

		// add_settings_field(
		// 	'adcontrol_userdash_fallback_tag_unit',
		// 	__( 'Tag Dimensions:', $this->plugin_options_key ),
		// 	array( $this, 'setting_fallback_tag_unit' ),
		// 	$this->advanced_settings_key,
		// 	$section,
		// 	array( 'label_for' => 'adsense_fallback_tag_unit' )
		// );

		add_settings_field(
			'adcontrol_userdash_adsense_leader',
			__( 'Include AdSense leader?', $this->plugin_options_key ),
			array( $this, 'setting_adsense_leader' ),
			$this->advanced_settings_key,
			$section,
			array( 'label_for' => 'adsense_leader' )
		);

		add_settings_field(
			'adcontrol_userdash_leader_tag_id',
			__( 'Tag ID:', $this->plugin_options_key ),
			array( $this, 'setting_leader_tag_id' ),
			$this->advanced_settings_key,
			$section,
			array( 'label_for' => 'adsense_leader_tag_id' )
		);

		add_settings_field(
			'adcontrol_userdash_leader_tag_unit',
			__( 'Tag Dimensions:', $this->plugin_options_key ),
			array( $this, 'setting_leader_tag_unit' ),
			$this->advanced_settings_key,
			$section,
			array( 'label_for' => 'adsense_leader_tag_unit' )
		);

		// TODO still include?
		// $section = 'adcontrol_amazon_match_buy_settings';
		// // Amazon section
		// add_settings_section(
		// 	$section,
		// 	__( 'Amazon Matchbuy', $this->plugin_options_key ),
		// 	'__return_null',
		// 	$this->advanced_settings_key
		// );

		// add_settings_field(
		// 	'adcontrol_userdash_amazon_match_buy',
		// 	__( 'Enable Amazon Matchbuy?', $this->plugin_options_key ),
		// 	array( $this, 'setting_amazon_match_buy' ),
		// 	$this->advanced_settings_key,
		// 	$section,
		// 	array( 'label_for' => 'amazon_match_buy' )
		// );

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
		<label for="radio_show_to_logged_in"> <?php _e( 'Every visitor', $this->plugin_options_key ); ?></label></p>
		<p><input type="radio" name="<?php echo esc_attr( $this->basic_settings_key ); ?>[show_to_logged_in]" id="radio_hide_from_logged_in" value="no" <?php checked( $show_to_logged_in, 'no' ); ?>/>
		<label for="radio_hide_from_logged_in"><?php _e( 'Every visitor, except logged-in users (fewer impressions)', $this->plugin_options_key ); ?></label></p>
		<p><input type="radio" name="<?php echo esc_attr( $this->basic_settings_key ); ?>[show_to_logged_in]" id="radio_hide_from_everyone" value="pause" <?php checked( $show_to_logged_in, 'pause' ); ?>/>
		<label for="radio_hide_from_everyone"><?php _e( 'Do not show any ads', $this->plugin_options_key ); ?></label></p>
		<?php
	}

	/**
	 * @since 0.1
	 */
	function setting_adsense_fallback() {
		$checked = checked( $this->get_option( 'adsense_fallback' ), 1, false );
		echo '<input id="adsense_fallback" type="checkbox" name="' . $this->advanced_settings_key . '[adsense_fallback]" value="1"' . $checked . ' />';
	}

	/**
	 * @since 0.1
	 */
	function setting_adsense_leader() {
		$checked = checked( $this->get_option( 'adsense_leader' ), 1, false );
		echo '<input id="adsense_leader" type="checkbox" name="' . $this->advanced_settings_key . '[adsense_leader]" value="1"' . $checked . ' />';
	}

	/**
	 * @since 0.1
	 */
	function setting_amazon_match_buy() {
		$checked = checked( $this->get_option( 'amazon_match_buy' ), 1, false );
		echo '<input id="amazon_match_buy" type="checkbox" name="' . $this->advanced_settings_key . '[amazon_match_buy]" value="1"' . $checked . ' />';
		_e( 'Site needs to be approved by Amazon', 'adcontrol' );
	}

	/**
	 * @since 0.1
	 */
	function setting_publisher_id() {
		$pid = $this->get_option( 'adsense_publisher_id' );
		echo "<input type='text' name='" . $this->advanced_settings_key . "[adsense_publisher_id]' value='$pid' /> ";
		_e( 'e.g. pub-123456789', $this->plugin_options_key );
		echo '<div class="aligncenter" style="width:290px;"><hr /></div>';
	}

	/**
	 * @since 0.1
	 */
	function setting_enable_advanced_settings() {
		$checked = checked( $this->get_option( 'enable_advanced_settings' ), 1, false );
		echo '<input id="enable_advanced_settings" type="checkbox" name="' . $this->basic_settings_key . '[enable_advanced_settings]" value="1"' . $checked . ' />';
	}

	/**
	 * @since 0.1
	 */
	function setting_fallback_tag_id() {
		$tid = $this->get_option( 'adsense_fallback_tag_id' );
		$disabled = disabled( ! $this->get_option( 'adsense_fallback' ), true, false );
		echo "<input class='adsense_fallback_opt' ", esc_attr( $disabled ) ,"type='text' name='" , esc_attr( $this->advanced_settings_key ) , "[adsense_fallback_tag_id]' value='" , esc_attr( $tid ), "' /> ";
		_e( 'e.g. 123456789', $this->plugin_options_key );
	}

	/**
	 * @since 0.1
	 */
	function setting_leader_tag_id() {
		$tid = $this->get_option( 'adsense_leader_tag_id' );
		$disabled = disabled( ! $this->get_option( 'adsense_leader' ), true, false );
		echo "<input class='adsense_leader_opt' ", esc_attr( $disabled ), " type='text' name='" , esc_attr( $this->advanced_settings_key ) , "[adsense_leader_tag_id]' value='" , esc_attr( $tid ), "' /> ";
		_e( 'e.g. 123456789', $this->plugin_options_key );
	}

	/**
	 * Callback for units option
	 *
	 * @since 0.1
	 */
	function setting_fallback_tag_unit() {
		$tag = $this->get_option( 'adsense_fallback_tag_unit' );
		$disabled = disabled( ! $this->get_option( 'adsense_fallback' ), true, false );
		echo '<select class="adsense_fallback_opt" ' . $disabled . ' id="adsense_fallback_tag_unit" name="' . $this->advanced_settings_key . '[adsense_fallback_tag_unit]">';
		foreach ( AdControl::$ad_tag_ids as $unit => $properties ) {
			if ( 'mrec' != $unit ) // TODO only want mrec for now
				continue;

			$selected = selected( $unit, $tag, false );
			echo "<option value='", esc_attr( $unit ) , "' ", esc_attr( $selected ) , '>', esc_html( $properties['tag'] ) , '</option>';
		}
		echo '</select>';
		echo '<div class="aligncenter" style="width:290px;"><hr /></div>';
	}

	/**
	 * Callback for units option
	 *
	 * @since 0.1
	 */
	function setting_leader_tag_unit() {
		$tag = $this->get_option( 'adsense_leader_tag_unit' );
		$disabled = disabled( ! $this->get_option( 'adsense_leader' ), true, false );
		echo '<select class="adsense_leader_opt" ' . $disabled . ' id="adsense_leader_tag_unit" name="' . $this->advanced_settings_key . '[adsense_leader_tag_unit]">';
		foreach ( AdControl::$ad_tag_ids as $unit => $properties ) {
			if ( 'leaderboard' != $unit ) // TODO only want leader for now
				continue;

			$selected = selected( $unit, $tag, false );
			echo "<option value='", esc_attr( $unit ) , "' ", esc_attr( $selected ) , '>', esc_html( $properties['tag'] ) , '</option>';
		}
		echo '</select>';
	}

	/**
	 * @since 0.1
	 */
	function setting_tos() {
		if ( 'signed' != $this->get_option( 'tos' ) )
			echo '<p><input type="checkbox" name="' . $this->basic_settings_key . '[tos]" id="chk_agreement" value="signed" /></p>';
		else
			echo '<span class="checkmark"></span>' .  __( 'Thank you for accepting the WordAds Terms of Service', $this->plugin_options_key );
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
