<?php

/**
 * Widget for inserting an ad into your sidebar
 *
 * @since 0.1
 */
class AdControl_Sidebar_Widget extends WP_Widget {

	private static $allowed_tags = array( 'mrec', 'wideskyscraper' );

	private $options = array();

	function __construct() {
		parent::__construct(
			'adcontrol_sidebar_widget',
			__( 'AdControl Sidebar Ad', 'adcontrol' ),
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
		$enabled = $this->option( 'fallback' );
		if ( ! $enabled )
			return false;

		if ( empty( $instance['all_set'] ) )
			return false;

		require_once( ADCONTROL_ROOT . '/php/adsense.php' );
		$pub = $instance['publisher_id'];
		$tag = $instance['tag_id'];
		$width = AdControl::$ad_tag_ids[$instance['unit']]['width'];
		$height = AdControl::$ad_tag_ids[$instance['unit']]['height'];
		$snippet = AdControl_Adsense::get_asynchronous_adsense( $pub, $tag, $width, $height );

		echo <<<HTML
		<div class="wpcnt">
			<div class="wpa">
				<a class="wpa-about" href="http://en.wordpress.com/about-these-ads/" rel="nofollow">About these ads</a>
				<div class="u {$instance['unit']}">
					$snippet
				</div>
			</div>
		</div>
HTML;
	}

	public function form( $instance ) {
		$disabled = ! $this->option( 'fallback' );
		if ( $disabled ) {
			$url = admin_url( 'options-general.php?page=adcontrol' );
			if ( $this->option( 'enable_advanced_settings' ) )
				$url .= '&tab=adcontrol_advanced_settings';

			if ( $this->option( 'enable_advanced_settings' ) )
				$msg = __( 'Enable AdSense fallback to activate.', 'adcontrol' );
			else
				$msg = __( 'Enable advanced settings to activate.', 'adcontrol' );

			echo "<p><a href='$url'>$msg</a></p>";
			return;
		}

		// publisher id
		if ( isset( $instance['publisher_id'] ) )
			$pid = $instance['publisher_id'];
		else
			$pid = $this->option( 'publisher_id', '' );

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
			<label for="<?php echo $this->get_field_id( 'publisher_id' ); ?>"><?php _e( 'Publisher ID:', 'adcontrol' ); ?></label>
			<?php
			if ( ! empty( $instance['error_pid'] ) )
				echo "<br /><small style='color:red;'>{$instance['error_pid']}</small>";
			?>
			<input class="widefat" id="<?php echo $this->get_field_id( 'publisher_id' ); ?>" name="<?php echo $this->get_field_name( 'publisher_id' ); ?>" type="text" value="<?php echo $pid; ?>" />
			<small>e.g. pub-123456789</small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'tag_id' ); ?>"><?php _e( 'Tag ID:', 'adcontrol' ); ?></label>
			<?php
			if ( ! empty( $instance['error_tid'] ) )
				echo "<br /><small style='color:red;'>{$instance['error_tid']}</small>";
			?>
			<input class="widefat" id="<?php echo $this->get_field_id( 'tag_id' ); ?>" name="<?php echo $this->get_field_name( 'tag_id' ); ?>" type="text" value="<?php echo $tid; ?>" />
			<small>e.g. 123456789</small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'unit' ); ?>"><?php _e( 'Tag Dimensions:', 'adcontrol' ); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id( 'unit' ); ?>" name="<?php echo $this->get_field_name( 'unit' ); ?>">
			<?php
			foreach ( AdControl::$ad_tag_ids as $ad_unit => $properties ) {
				if ( ! in_array( $ad_unit, self::$allowed_tags ) )
					continue;

				$selected = selected( $ad_unit, $unit, false );
				echo "<option value='$ad_unit' $selected>{$properties['tag']}</option>";
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
		if ( preg_match( '/^(pub-)?(\d+)$/', $new_instance['publisher_id'], $matches ) )
			$instance['publisher_id'] = 'pub-' . esc_attr( $matches[2] );
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

		if ( ! ( empty( $instance['publisher_id'] ) || empty( $instance['tag_id'] ) ) )
			$instance['all_set'] = true;

		return $instance;
	}
}

add_action( 'widgets_init', function(){
	register_widget( 'AdControl_Sidebar_Widget' );
});
