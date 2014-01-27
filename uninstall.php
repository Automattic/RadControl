<?php

if( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

delete_option( 'adcontrol_settings' );
delete_option( 'adcontrol_advanced_settings' );

// drops mic
