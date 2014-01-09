<?php

/**
 * Widget for inserting an ad into your sidebar
 *
 * @since 0.1
 * TODO: sanitize all the output
 */
class AdControl_Sidebar_Widget extends WP_Widget {

	private static $allowed_tags = array( 'mrec', 'wideskyscraper' );

	private $options = array();

	function __construct() {
		parent::__construct(
			'adcontrol_sidebar_widget',
			__( 'AdControl AdSense Sidebar', 'adcontrol' ),
			array( 'description' => __( 'Place an AdControl ad in your sidebar', 'adcontrol' ) )
		);

		$this->options = array_merge(
			get_option( 'adcontrol_settings',  array() ),
			get_option( 'adcontrol_advanced_settings', array() )
		);
	}

	/**
	 * Convenience function for grabbing options from params->options
	 * @param  string $option the option to grab
	 * @param  mixed  $default (optional)
	 * @return option or $default if not set
	 *
	 * @since 0.1
	 */
	function option( $option, $default = false ) {
		if ( ! isset( $this->options[$option] ) )
			return $default;

		return $this->options[$option];
	}

	public function widget( $args, $instance ) {
		if ( ! AdControl::check_jetpack() || ac_is_mobile() )
			return false;

		if ( empty( $instance['all_set'] ) || ! $this->option( 'enable_advanced_settings' ) )
			return false;

		require_once( ADCONTROL_ROOT . '/php/networks/adsense.php' );
		$about = __( 'About these ads', 'adcontrol' );
		$pub = $instance['adsense_publisher_id'];
		$tag = $instance['tag_id'];
		$width = AdControl::$ad_tag_ids[$instance['unit']]['width'];
		$height = AdControl::$ad_tag_ids[$instance['unit']]['height'];
		$snippet = AdControl_Adsense::get_asynchronous_adsense( $pub, $tag, $width, $height );
		echo '
		<div class="wpcnt">
			<div class="wpa">
				<a class="wpa-about" href="http://en.wordpress.com/about-these-ads/" rel="nofollow">', esc_html( $about ) ,'</a>
				<div class="u ' . esc_attr( $instance['unit'] ) . '">
					',$snippet,'
				</div>
			</div>
		</div>
';
	}

	public function form( $instance ) {
		if ( ! $this->option( 'enable_advanced_settings' ) ) {
			$url = admin_url( 'options-general.php?page=adcontrol' );
			$msg = __( 'Enable advanced settings to activate.', 'adcontrol' );
			echo "<p><a href='" , esc_url( $url ) ,"' target='_blank'>" . esc_html( $msg ) . '</a></p>';
			return;
		}

		// publisher id
		if ( isset( $instance['adsense_publisher_id'] ) )
			$pid = $instance['adsense_publisher_id'];
		else
			$pid = $this->option( 'adsense_publisher_id', '' );

		// tag id
		if ( isset( $instance['tag_id'] ) )
			$tid = $instance['tag_id'];
		else
			$tid = '';

		// ad unit type
		if ( isset( $instance['unit'] ) )
			$unit = $instance['unit'];
		else
			$unit = 'mrec';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'adsense_publisher_id' ) ); ?>"><?php _e( 'Publisher ID:', 'adcontrol' ); ?></label>
		<?php
			if ( ! empty( $instance['error_pid'] ) )
				echo "<br /><small style='color:red;'>", esc_html( $instance['error_pid'] ) ,'</small>';
		?>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'adsense_publisher_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'adsense_publisher_id' ) ); ?>" type="text" value="<?php echo esc_attr( $pid ); ?>" />
			<small>e.g. pub-123456789</small>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'tag_id' ) ); ?>"><?php _e( 'Tag ID:', 'adcontrol' ); ?></label>
		<?php
			if ( ! empty( $instance['error_tid'] ) )
				echo "<br /><small style='color:red;'>", esc_html( $instance['error_tid'] ), '</small>';
		?>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'tag_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'tag_id' ) ); ?>" type="text" value="<?php echo esc_attr( $tid ); ?>" />
			<small>e.g. 123456789</small>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'unit' ) ); ?>"><?php _e( 'Tag Dimensions:', 'adcontrol' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'unit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'unit' ) ); ?>">
		<?php
		foreach ( AdControl::$ad_tag_ids as $ad_unit => $properties ) {
				if ( ! in_array( $ad_unit, self::$allowed_tags ) )
					continue;
				$selected = selected( $ad_unit, $unit, false );
				echo "<option value='", esc_attr( $ad_unit ) ,"' ", esc_attr( $selected ) , '>', esc_html( $properties['tag'] ) , '</option>';
			}
		?>
			</select>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['error_pid'] = false;
		$instance['error_tid'] = false;
		$instance['all_set'] = false;

		$matches = array();
		if ( preg_match( '/^(pub-)?(\d+)$/', $new_instance['adsense_publisher_id'], $matches ) )
			$instance['adsense_publisher_id'] = 'pub-' . esc_attr( $matches[2] );
		else
			$instance['error_pid'] = __( 'Publisher ID must be of form "pub-123456789"', 'adcontrol' );

		if ( is_numeric( $new_instance['tag_id'] ) )
			$instance['tag_id'] = esc_attr( $new_instance['tag_id'] );
		else
			$instance['error_tid'] = __( 'Tag ID must be of form "123456789"', 'adcontrol' );

		if ( in_array( $new_instance['unit'], self::$allowed_tags ) )
			$instance['unit'] = $new_instance['unit'];
		else
			$instance['unit'] = 'mrec';

		if ( ! ( empty( $instance['adsense_publisher_id'] ) || empty( $instance['tag_id'] ) ) )
			$instance['all_set'] = true;

		return $instance;
	}
}

add_action(
	'widgets_init',
	create_function(
		'',
		'return register_widget( "AdControl_Sidebar_Widget" );'
	)
);
