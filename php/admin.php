<?php

class AdControl_Admin {
	private $valid_settings = array(
		'show_to_logged_in',
		'tos',
		'fallback',
		'publisher_id',
		'tag_id',
		'tag_unit',
		'enable_advanced_settings',
	);
	private $active_tab = 'adcontrol_settings';
	private $tabs = array(
		'adcontrol_settings' => 'AdControl Settings',
		'adcontrol_earnings' => 'Earnings',
		'adcontrol_advanced_settings' => 'Advanced Settings',
	);
	private $options = array();
	private $options_advanced = array();
	private $basic_settings_key = 'adcontrol_settings';
	private $advanced_settings_key = 'adcontrol_advanced_settings';
	private $plugin_options_key = 'adcontrol';
	private $revenue_states = array( 'active', 'paused', 'withdrawn' );
	private $status;
	private $show_revenue = true;
	private $action;

	/**
	 * @since 0.1
	 */
	function __construct() {
		$this->blog_id = Jetpack::get_option( 'id', 0 );
		$this->options = get_option( $this->basic_settings_key, array() );
		$this->options_advanced = get_option( $this->advanced_settings_key, array() );
		$this->status = 'active'; // TODO
		add_action( 'admin_menu', array( &$this, 'admin_menu' ), 11 );
		add_action( 'admin_init', array( &$this, 'register_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_advanced_settings' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_scripts' ) );
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
		$this->active_tab = isset( $_GET['tab'] ) ? esc_attr( $_GET['tab'] ) : 'adcontrol_settings';
		screen_icon();

		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $this->tabs as $tab_key => $tab_caption ) {
			$active = ( $tab_key == $this->active_tab ? ' nav-tab-active' : '' );
            if ( 'adcontrol_advanced_settings' == $tab_key && ! $this->options['enable_advanced_settings'] == 1 )
                continue;
			if ( ( 'adcontrol_earnings' == $tab_key && ! $this->show_revenue ) )
				continue;
			echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->plugin_options_key . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
		}
		echo '</h2>';
	}

	/**
	 * @since 0.1
	 */
	function userdash_show_page() {
		$this->admin_tabs();
		if ( 'adcontrol_earnings' == $this->active_tab )
			$this->userdash_show_revenue();
		else
			$this->userdash_show_settings();
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
		echo '</div>';
	}

	/**
	 * @since 0.1
	 */
	function userdash_show_revenue() {
		$msg = sprintf(
			__( 'Please login to our %ssecure servers on WordPress.com%s to see your revenue details.', $this->plugin_options_key ),
			'<a href="https://wordpress.com/#!/settings/earnings/">',
			'</a>'
		);
		echo $msg;
	}

	/**
	 * @since 0.1
	 */
	function validate_settings( $settings ) {
		$to_save = array();

		if ( 'signed' == $settings[ 'tos' ] || 'signed' == $this->get_option( 'tos' ) )
			$to_save[ 'tos' ] = 'signed';
		else
			add_settings_error( 'tos', 'tos', __( 'You must agree to the Terms of Service.' ) );

		if ( 'no' == $settings[ 'show_to_logged_in' ] ) {
			$to_save[ 'show_to_logged_in' ] = 'no';
		} elseif ( 'pause' == $settings[ 'show_to_logged_in' ] ) {
			$to_save[ 'show_to_logged_in' ] = 'pause';
		} else {
			$to_save[ 'show_to_logged_in' ] = 'yes';
		}

		$to_save['enable_advanced_settings'] = ( $settings['enable_advanced_settings'] ) ? 1 : 0;


		return $to_save;
	}

   /**
     * @since 0.1
     */
    function validate_advanced_settings( $settings ) {
        $to_save = array();

        $to_save['fallback'] = absint( $settings['fallback'] );

        if ( ! $settings['fallback'] )
            return $to_save;

        $matches = array();
        if ( preg_match( '/^(pub-)?(\d+)$/', $settings['publisher_id'], $matches ) )
            $to_save[ 'publisher_id' ] = 'pub-' . esc_attr( $matches[2] );
        else
            add_settings_error( 'publisher_id', 'publisher_id', __( 'Publisher ID must be of form "pub-123456789"' ) );

        if ( is_numeric( $settings['tag_id'] ) )
            $to_save[ 'tag_id' ] = esc_attr( $settings['tag_id'] );
        else
            add_settings_error( 'tag_id', 'tag_id', __( 'Tag ID must be of form "123456789"' ) );

        $to_save[ 'tag_unit' ] = esc_attr( $settings['tag_unit'] );

        if ( isset( $to_save['tag_id'] ) && isset( $to_save['publisher_id'] ) )
            $to_save[ 'adsense_set' ] = 1;
        else
            $to_save[ 'adsense_set' ] = 0;
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
			$this->plugin_options_key,
			array( &$this, 'userdash_show_page' )
		);

		$tab = ( isset( $_GET['page'] ) && array_key_exists( $_GET['page'], $this->tabs ) ? $_GET['page'] : '' );
		if ( ! empty ( $tab ) )
			$submenu_file = 'adcontrol';
	}

	/**
	 * @since 0.1
	 */
	function register_settings() {
		register_setting(
			$this->basic_settings_key,
			$this->basic_settings_key,
			array( &$this, 'validate_settings' )
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
			array( &$this, 'validate_advanced_settings' )
		);

		$this->init_advanced_settings();
	}

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
			array( &$this, 'setting_show_to_logged_in' ),
			$this->basic_settings_key,
			$section_name,
			array( 'label_for' => 'radio_show_to_logged_in' )
		);

		add_settings_field(
			'adcontrol_userdash_enable_advanced_settings',
			__( 'Enable Advanced Settings:', 'adcontrol' ),
			array( &$this, 'setting_enable_advanced_settings' ),
			$this->basic_settings_key,
			$section_name,
			array( 'label_for' => 'enable_advanced_settings' )
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
			sprintf( __( 'I have read and agree to the %sWordAds Terms of Service', 'adcontrol' ), '<br /><a href="http://wordpress.com/tos-wordads/" target="_blank">' ) . '</a>',
			array( &$this, 'setting_tos' ),
			$this->basic_settings_key,
			$section_name,
			array( 'label_for' => 'chk_agreement' )
		);
	}

	private function init_advanced_settings() {
		$section = 'adcontrol_advanced_settings';
		// Config section of the form
		add_settings_section(
			$section,
			__( 'Advanced Configuration Options', 'adcontrol' ),
			'__return_null',
			$this->advanced_settings_key
		);
		// AdSense section
		add_settings_section(
			'adcontrol_userdash_adsense_section',
			__( 'AdSense Options', 'adcontrol' ),
			'__return_null',
			$this->advanced_settings_key
		);

		add_settings_field(
			'adcontrol_userdash_adsense_fallback',
			__( 'Include AdSense fallback?', 'adcontrol' ),
			array( &$this, 'setting_adsense_fallback' ),
			$this->advanced_settings_key,
			$section,
			array( 'label_for' => 'adsense_fallback' )
		);

		add_settings_field(
			'adcontrol_userdash_publisher_id',
			__( 'Publisher ID:', 'adcontrol' ),
			array( &$this, 'setting_publisher_id' ),
			$this->advanced_settings_key,
			$section,
			array( 'label_for' => 'publisher_id' )
		);

		add_settings_field(
			'adcontrol_userdash_tag_id',
			__( 'Tag ID:', 'adcontrol' ),
			array( &$this, 'setting_tag_id' ),
			$this->advanced_settings_key,
			$section,
			array( 'label_for' => 'tag_id' )
		);

		add_settings_field(
			'adcontrol_userdash_tag_unit',
			__( 'Tag Dimensions:', 'adcontrol' ),
			array( &$this, 'setting_tag_unit' ),
			$this->advanced_settings_key,
			$section,
			array( 'label_for' => 'tag_unit' )
		);
	}

	/**
	 * @since 0.1
	 */
	function setting_show_to_logged_in() {
		$readonly = ''; // TODO

		$show_to_logged_in = $this->get_option( 'show_to_logged_in' );
		if ( ! in_array( $show_to_logged_in, array( 'yes', 'no', 'pause' ) ) )
			$show_to_logged_in = 'yes';
?>
		<p><input type="radio" name="<?php echo esc_attr( $this->basic_settings_key ); ?>[show_to_logged_in]" id="radio_show_to_logged_in" value="yes" <?php checked( $show_to_logged_in, 'yes' ); ?>/>
		<label for="radio_show_to_logged_in"> <?php _e( 'Every visitor' ); ?></label></p>
		<p><input type="radio" name="<?php echo esc_attr( $this->basic_settings_key ); ?>[show_to_logged_in]" id="radio_hide_from_logged_in" value="no" <?php checked( $show_to_logged_in, 'no' ); ?>/>
		<label for="radio_hide_from_logged_in"><?php _e( 'Every visitor, except logged-in users (fewer impressions)' ); ?></label></p>
		<p><input type="radio" name="<?php echo esc_attr( $this->basic_settings_key ); ?>[show_to_logged_in]" id="radio_hide_from_everyone" value="pause" <?php checked( $show_to_logged_in, 'pause' ); ?>/>
		<label for="radio_hide_from_everyone"><?php _e( 'Do not show any ads' ); ?></label></p>
<?php
	}

	/**
	 * @since 0.1
	 */
	function setting_adsense_fallback() {
		$checked = checked( $this->get_option( 'fallback' ), 1, false );
		echo '<input id="adsense_fallback" type="checkbox" name="' . $this->advanced_settings_key . '[fallback]" value="1"' . $checked . ' />';
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
	function setting_publisher_id() {
		$pid = $this->get_option( 'publisher_id' );
		$disabled = disabled( $this->get_option( 'fallback' ), 0, false );
		echo "<input class='adsense_opt' $disabled type='text' name='" . $this->advanced_settings_key . "[publisher_id]' value='$pid' /> ";
		_e( 'e.g. pub-123456789', 'adsense' );
	}

	/**
	 * @since 0.1
	 */
	function setting_tag_id() {
		$tid = $this->get_option( 'tag_id' );
		$disabled = disabled( $this->get_option( 'fallback' ), 0, false );
		echo "<input class='adsense_opt' $disabled type='text' name='" . ( $this->advanced_settings_key ) . "[tag_id]' value='$tid' /> ";
		_e( 'e.g. 123456789', 'adsense' );
	}

	/**
	 * Callback for units option
	 *
	 * @since 0.1
	 */
	function setting_tag_unit() {
		$tag = $this->get_option( 'tag_unit' );
		$disabled = disabled( $this->get_option( 'fallback' ), 0, false );
		echo '<select class="adsense_opt" ' . $disabled . ' id="tag_unit" name="' . $this->advanced_settings_key . '[tag_unit]">';
		foreach ( AdControl::$ad_tag_ids as $unit => $properties ) {
			$selected = selected( $unit, $tag, false );
			echo "<option value='$unit' $selected>{$properties['tag']}</option>";
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
			echo '<span class="checkmark"></span>' .  __( 'Thank you for accepting the WordAds Terms of Service', 'adcontrol' );
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
