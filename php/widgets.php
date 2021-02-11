<?php

/**
 * Widget for inserting an ad into your sidebar
 *
 * @since 0.1
 * TODO: sanitize all the output
 */
class AdControl_Sidebar_Widget extends WP_Widget {

	private static $allowed_tags = array( 'mrec', 'wideskyscraper' );
	private static $num_widgets = 0;

	function __construct() {
		parent::__construct(
			'adcontrol_sidebar_widget',
			__( 'AdControl Widget', 'adcontrol' ),
			array( 'description' => __( 'Insert an AdControl ad wherever you can place a widget.', 'adcontrol' ) )
		);
	}

	public function widget( $args, $instance ) {
		global $adcontrol;
		if ( ! AdControl::check_jetpack() || $adcontrol->should_bail() ) {
			return false;
		}

		if ( ! isset( $instance['unit'] ) ) {
			$instance['unit'] = 'mrec';
		}

		self::$num_widgets++;
		$about = __( 'Advertisements', 'adcontrol' );
		$width = AdControl::$ad_tag_ids[$instance['unit']]['width'];
		$height = AdControl::$ad_tag_ids[$instance['unit']]['height'];
		$unit_id = 1 == self::$num_widgets ? 3 : self::$num_widgets + 3; // 2nd belowpost is '4'
		$section_id = 0 === $adcontrol->params->blog_id ?
			ADCONTROL_API_TEST_ID :
			$adcontrol->params->blog_id . $unit_id;

		$snippet = '';
		if ( $adcontrol->option( 'wordads_house', true ) ) {
			$unit = 'mrec';
			if ( 'leaderboard' == $instance['unit'] && ! $this->params->mobile_device ) {
				$unit = 'leaderboard';
			} else if ( 'wideskyscraper' == $instance['unit'] ) {
				$unit = 'widesky';
			}

			$snippet = $adcontrol->get_house_ad( $unit );
		} else {
			$snippet = $adcontrol->get_ad_snippet( $section_id, $height, $width );
		}

		echo <<< HTML
		<div class="wpcnt">
			<div class="wpa">
				<span class="wpa-about">$about</span>
				<div class="u {$instance['unit']}">
					$snippet
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

add_action( 'widgets_init', 'adcontrol_register_widgets' );
function adcontrol_register_widgets() {
	register_widget( "AdControl_Sidebar_Widget" );
}
