<?php


class WP_Test_adcontrol extends WP_UnitTestCase {

	public function test_check_jetpack() {
		$ac = new AdControl();

		$this->assertInternalType( 'string', $ac->get_googleaddslots() );
	}
}
