<?php

class AdControl_Admin {
	private $valid_settings = array(
		'show_to_logged_in',
		'tos',
	);
	private $active_tab = "settings";
	private $tabs = array(
		'settings' => 'Settings',
		'earnings' => 'Earnings'
	);
	private $options = array();
	private $revenue_states = array( 'active', 'paused',' withdrawn' );
	private $status;
	private $show_revenue = true;
	private $action;

	/**
	 * @since 0.1
	 */
	function __construct() {
		$this->blog_id = Jetpack::get_option( 'id', 0 );
		$this->options = get_option( 'adcontrol_userdash_options', array() );
		$this->status = 'active'; // TODO

		add_action( 'admin_menu', array( &$this, 'admin_menu' ), 11 );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
	}

	/**
	 * @since 0.1
	 */
	function get_option( $key ) {
		// Options are limited to those specified in $this->valid_settings
		if ( ! in_array( $key, $this->valid_settings ) )
			return;
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : null;
	}

	/**
	 * @since 0.1
	 */
	function admin_tabs() {
	    $this->active_tab = isset( $_GET['tab'] ) ? esc_attr( $_GET['tab'] ) : 'settings';

	    screen_icon();
	    echo '<h2 class="nav-tab-wrapper">';
	    foreach ( $this->tabs as $tab_key => $tab_caption ) {
	        $active = ( $tab_key == $this->active_tab ? ' nav-tab-active' : '' );
	        if ( 'earnings' == $tab_key && $this->show_revenue || 'earnings' != $tab_key )
		        echo '<a class="nav-tab ' . $active . '" href="?page=adcontrol&tab=' . $tab_key . '">' . $tab_caption . '</a>';
	    }
	    echo '</h2>';
	}

	/**
	 * @since 0.1
	 */
	function userdash_show_page() {
		$this->admin_tabs();
		if( 'earnings' == $this->active_tab )
			$this->userdash_show_revenue();
		else
			$this->userdash_show_settings();
	}

	/**
	 * @since 0.1
	 */
	function userdash_show_settings() {
		echo '<div class="wrap">';
		$prompts = $actions = array();
		if ( $this->is_paused() )
			echo '<div class="updated" id="wpcom-tip"><p><strong>' . __( 'WordAds is paused. Please choose which visitors should see ads.' ) . '</strong></p></div>';
		?>
		<form action="options.php" method="post" id="wordads_settings">
			<?php
			settings_fields( 'adcontrol_userdash_options' );
			do_settings_sections( 'adcontrol_userdash' );
			?>
			<p><input name="adcontrol_userdash_submit" type="submit" class="button-primary" value="<?php _e( 'Save WordAds Settings' ); ?>" /></p>
		</form>
		<?php
		echo '</div>';
	}

	/**
	 * @since 0.1
	 */
	function userdash_show_revenue() {
		$msg = sprintf(
			__( 'Please login to our %ssecure servers on WordPress.com%s to see your revenue details.', 'adcontrol' ),
			'<a href="https://wordpress.com/#!/settings/earnings/">',
			'</a>'
		);
		echo $msg;
	}

	/**
	 * @since 0.1
	 */
	function validate( $settings ) {
		$to_save = array();

		if ( 'no' == $settings[ 'show_to_logged_in' ] ) {
			$to_save[ 'show_to_logged_in' ] = 'no';
		} elseif ( 'pause' == $settings[ 'show_to_logged_in' ] ) {
			$to_save[ 'show_to_logged_in' ] = 'pause';
		} else {
			$to_save[ 'show_to_logged_in' ] = 'yes';
		}

		if ( 'signed' == $settings[ 'tos' ] || 'signed' == $this->get_option( 'tos' ) )
			$to_save[ 'tos' ] = 'signed';
		else
			add_settings_error( 'tos', 'tos', __( 'You must agree to the Terms of Service.' ) );

		return $to_save;
	}

	/**
	 * @since 0.1
	 */
	function admin_menu() {
		global $submenu_file;
		add_options_page(
			__( 'AdControl Settings', 'adcontrol' ),
			'AdControl',
			'manage_options',
			'adcontrol',
			array( &$this, 'userdash_show_page' )
		);

		$tab = ( isset( $_GET['page'] ) && array_key_exists( $_GET['page'], $this->tabs ) ? $_GET['page'] : '' );
		if ( ! empty ( $tab ) )
			$submenu_file = 'adcontrol';
	}

	/**
	 * @since 0.1
	 */
	function admin_init() {

		register_setting(
			'adcontrol_userdash_options',
			'adcontrol_userdash_options',
			array( &$this, 'validate' )
		);

		// Config section of the form
		add_settings_section(
			'adcontrol_userdash_config_section',
			__( 'Configuration Options', 'adcontrol' ),
			'__return_null',
			'adcontrol_userdash'
		);

		add_settings_field(
			'adcontrol_userdash_show_to_logged_in_id',
			__( 'Show ads to:' ),
			array( &$this, 'setting_show_to_logged_in' ),
			'adcontrol_userdash',
			'adcontrol_userdash_config_section',
			array( 'label_for' => 'radio_show_to_logged_in' )
		);

		// TOS section of the form
		add_settings_section(
			'adcontrol_userdash_tos_section',
			__( 'WordAds Terms of Service', 'adcontrol' ),
			'__return_null',
			'adcontrol_userdash'
		);

		add_settings_field(
			'adcontrol_userdash_tos_id',
			sprintf( __( 'I have read and agree to the %sWordAds Terms of Service' ), '<br /><a href="http://wordpress.com/tos-wordads/" target="_blank">' ) . '</a>',
			array( &$this, 'setting_tos' ),
			'adcontrol_userdash',
			'adcontrol_userdash_tos_section',
			array( 'label_for' => 'chk_agreement' )
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
		<p><input type="radio" name="adcontrol_userdash_options[show_to_logged_in]" id="radio_show_to_logged_in" value="yes" <?php checked( $show_to_logged_in, 'yes' ); ?>/>
		<label for="radio_show_to_logged_in"> <?php _e( 'Every visitor' ); ?></label></p>
		<p><input type="radio" name="adcontrol_userdash_options[show_to_logged_in]" id="radio_hide_from_logged_in" value="no" <?php checked( $show_to_logged_in, 'no' ); ?>/>
		<label for="radio_hide_from_logged_in"><?php _e( 'Every visitor, except logged-in users (fewer impressions)' ); ?></label></p>
		<p><input type="radio" name="adcontrol_userdash_options[show_to_logged_in]" id="radio_hide_from_everyone" value="pause" <?php checked( $show_to_logged_in, 'pause' ); ?>/>
		<label for="radio_hide_from_everyone"><?php _e( 'Do not show any ads' ); ?></label></p>
<?php
	}

	/**
	 * @since 0.1
	 */
	function setting_tos() {
		if ( 'signed' != $this->get_option( 'tos' ) )
			echo '<p><input type="checkbox" name="adcontrol_userdash_options[tos]" id="chk_agreement" value="signed" />';
		else
			echo '<span class="checkmark"></span>' .  __( 'Thank you for accepting the WordAds Terms of Service' );
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
