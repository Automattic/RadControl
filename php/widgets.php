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
			__( 'AdControl Widget', 'adcontrol' ),
			array( 'description' => __( 'Insert an AdControl ad wherever you can place a widget.', 'adcontrol' ) )
		);

		$this->options = array_merge(
			get_option( 'adcontrol_settings',  array() )
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
		global $adcontrol;
		if ( ! AdControl::check_jetpack() || $adcontrol->should_bail() ) {
			return false;
		}

		$about = __( 'About these ads', 'adcontrol' );
		$section_id = 0 === $adcontrol->params->blog_id ? ADCONTROL_API_TEST_ID : $adcontrol->params->blog_id . '3';
		$width = AdControl::$ad_tag_ids[$instance['unit']]['width'];
		$height = AdControl::$ad_tag_ids[$instance['unit']]['height'];
		$data_tags = ( $adcontrol->params->cloudflare ) ? ' data-cfasync="false"' : '';
		echo <<< HTML
		<div class="wpcnt">
			<div class="wpa">
				<a class="wpa-about" href="http://en.wordpress.com/about-these-ads/" rel="nofollow">$about</a>
				<div class="u {$instance['unit']}">
					<script$data_tags type='text/javascript'>
						(function(g){g.__ATA.initAd({sectionId:$section_id, width:$width, height:$height});})(window);
					</script>
				</div>
			</div>
		</div>
HTML;
	}

	public function form( $instance ) {
		// ad unit type
		if ( isset( $instance['unit'] ) ) {
			$unit = $instance['unit'];
		} else {
			$unit = 'mrec';
		}
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'unit' ) ); ?>"><?php _e( 'Tag Dimensions:', 'adcontrol' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'unit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'unit' ) ); ?>">
		<?php
		foreach ( AdControl::$ad_tag_ids as $ad_unit => $properties ) {
				if ( ! in_array( $ad_unit, self::$allowed_tags ) ) {
					continue;
				}

				$splits = explode( '_', $properties['tag'] );
				$unit_pretty = "{$splits[0]} {$splits[1]}";
				$selected = selected( $ad_unit, $unit, false );
				echo "<option value='", esc_attr( $ad_unit ) ,"' ", $selected, '>', esc_html( $unit_pretty ) , '</option>';
			}
		?>
			</select>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		if ( in_array( $new_instance['unit'], self::$allowed_tags ) ) {
			$instance['unit'] = $new_instance['unit'];
		} else {
			$instance['unit'] = 'mrec';
		}

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
