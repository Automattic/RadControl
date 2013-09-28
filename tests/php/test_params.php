<?php
require_once( ADCONTROL_ROOT . '/php/params.php' );
require_once( ADCONTROL_ROOT . '/php/user-agent.php' );

class WP_Test_params extends WP_UnitTestCase {

	public function test_check_jetpack() {
		require_once(ABSPATH .'/wp-admin/includes/plugin.php');
		if( !is_plugin_active( 'jetpack/jetpack.php' ) ) {
			activate_plugin( 'jetpack/jetpack.php' );
		}
		$acp = new AdControl_Params();

		$slots = count( $acp->dfp_slots ); // Save original number of slots
		$acp->add_slot('test', 'name', 100, 100, 'test');

		$this->assertTrue( count( $acp->dfp_slots ) > $slots ); // There should be more slots now than originally
	}
}
