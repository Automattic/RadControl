<?php

/**
 * Widget for inserting an ad into your sidebar
 *
 * @since 0.1
 */
class AdControl_Sidebar_Widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'adcontrol_sidebar_widget',
			__( 'AdControl Sidebar Ad', 'adcontrol' ),
			array( 'description' => __( 'Place an AdControl ad in your sidebar', 'adcontrol' ) )
		);
	}

	public function widget( $args, $instance ) {
		global $adcontrol;

		$sidebar = true; // TODO check for sidebar ad
		if ( ! $sidebar )
			return false;

		// TODO real sidebar slot
		echo $adcontrol->get_ad( 'side' );
	}

	public function form( $instance ) {
		echo '<h1>Widget goes here</h1>';
	}

	public function update( $new_instance, $old_instance ) {
		return array();
	}
}

add_action( 'widgets_init', function(){
	register_widget( 'AdControl_Sidebar_Widget' );
});
