<?php

/*
Plugin Name: AdControl for WordPress
Plugin URI: http://automattic.com
Description: Harness the power of WordPress.com's advertising partners for your own blog.
Author: Automattic
Version: 0.1-alpha
Author URI: http://automattic.com

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define( 'ADCONTROL_VERSION', '0.1-alpha' );
define( 'ADCONTROL_ROOT' , dirname( __FILE__ ) );
define( 'ADCONTROL_FILE_PATH' , ADCONTROL_ROOT . '/' . basename( __FILE__ ) );
define( 'ADCONTROL_URL' , plugins_url( '/', __FILE__ ) );

class AdControl {

	private $params = null;

	/**
	 * Instantiate the plugin
	 *
	 * @since 0.1
	 */
	function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Code to run on WordPress 'init' hook
	 *
	 * @since 0.1
	 */
	function init() {
		// TODO requires Jetpack for now
		if ( ! self::check_jetpack() )
			return;

		// bail on infinite scroll
		if ( current_theme_supports( 'infinite-scroll' ) &&
				class_exists( 'The_Neverending_Home_Page' ) &&
				The_Neverending_Home_Page::got_infinity() ) {
			return;
		}

		load_plugin_textdomain(
			'adcontrol',
			false,
			plugin_basename( dirname( __FILE__ ) ) . '/languages/'
		);

		require_once( ADCONTROL_ROOT . '/php/user-agent.php' );
		require_once( ADCONTROL_ROOT . '/php/admin.php' );
		require_once( ADCONTROL_ROOT . '/php/params.php' );

		$this->params = new AdControl_Params();
		if ( $this->params->is_mobile() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_mobile_scripts' ) );
			add_filter( 'the_content', array( $this, 'insert_mobile_ad' ) );
			add_filter( 'the_excerpt', array( $this, 'insert_mobile_ad' ) );
		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_filter( 'the_content', array( $this, 'insert_ad' ) );
			add_filter( 'the_excerpt', array( $this, 'insert_ad' ) );

			$slot_name = 'Wordads_MIS_Mrec_Below_adsafe'; // TODO check adsafe
			$this->params->add_slot( 'belowpost', $slot_name, 400, 267, 3443918307802676 );
		}
	}

	/**
	 * Register scripts and styles
	 *
	 * @since 0.1
	 */
	function enqueue_scripts() {
		// JS
		wp_enqueue_script(
			'wa-adclk',
			ADCONTROL_URL . 'js/adclk.js',
			array( 'jquery' ),
			'2013-06-21',
			true
		);

		$data = array(
			'theme' => $this->params->theme,
			'slot'  => 'belowpost', // TODO add other slots?
		);
		wp_localize_script( 'wa-adclk', 'wa_adclk', $data );

		add_action( 'wp_head', array( $this, 'insert_head_wordads' ) );
		add_action( 'wp_head', array( $this, 'insert_head_gam' ) ); // TODO still GAM?

		// CSS
		wp_enqueue_style(
			'genericon-font',
			ADCONTROL_URL . 'css/genericons/genericons.css',
			false,
			'2.0'
		);

		wp_enqueue_style(
			'adcontrol',
			ADCONTROL_URL . 'css/adcontrol.css',
			array( 'genericon-font' ),
			'2013-06-24'
		);
	}

	/**
	 * Register mobile scripts and styles
	 *
	 * @since 0.1
	 */
	function enqueue_mobile_scripts() {
		// JS
		wp_enqueue_script(
			'wa-adclk',
			ADCONTROL_URL . 'js/adclk.js',
			array( 'jquery' ),
			'2013-06-21',
			true
		);

		$data = array(
			'theme' => $this->params->theme,
			'slot'  => 'belowpost', // TODO add other slots?
		);
		wp_localize_script( 'wa-adclk', 'wa_adclk', $data );

		wp_enqueue_script(
			'mopub',
			'http://ads.mopub.com/js/client/mopub.js',
			array(),
			false,
			true
		);
	}

	function insert_head_wordads() {
		$part = ( is_home() || is_archive() ) ? 'index' : 'permalink';
		$domain = esc_js( $_SERVER['HTTP_HOST'] );
		$current_page_url = esc_url( $this->params->url );

		echo <<<HTML
		<script type="text/javascript">
		var wpcom_ads = { bid: {$this->params->blog_id}, pt: '$part', wa: 1, domain: '$domain', url: '$current_page_url', };
		</script>
HTML;
	}

	function insert_head_gam() {
		$about = __( 'About these ads', 'adcontrol' );
		echo <<<HTML
		<script type="text/javascript" src="http://partner.googleadservices.com/gampad/google_service.js"></script>
		<script type="text/javascript">
			GS_googleAddAdSenseService("ca-pub-3443918307802676");
			GS_googleEnableAllServices();
		</script>
		<script type="text/javascript">
			{$this->params->get_dfp_targetting()}
		</script>
		<script type="text/javascript">
			{$this->get_google_add_slots()}
		</script>
		<script type="text/javascript">
			GA_googleFetchAds();
		</script>
		<script type="text/javascript">
		jQuery( window ).load( function() {
			jQuery( "a.wpadvert-about" ).text( "$about" );
		} );
		</script>
HTML;
	}

	function get_google_add_slots() {
		$slots = '';
		if ( isset( $this->params->dfp_slots['top.name'] ) )
			$slots .= "GA_googleAddSlot('ca-pub-{$this->params->dfp_slots['top.id']}', '{$this->params->dfp_slots['top.name']}');\n";
		if ( isset( $this->params->dfp_slots['side.name'] ) )
			$slots .= "GA_googleAddSlot('ca-pub-{$this->params->dfp_slots['side.id']}', '{$this->params->dfp_slots['side.name']}');\n";
		if ( isset( $this->params->dfp_slots['inpost.name'] ) )
			$slots .= "GA_googleAddSlot('ca-pub-{$this->params->dfp_slots['inpost.id']}', '{$this->params->dfp_slots['inpost.name']}');\n";
		if ( isset( $this->params->dfp_slots['belowpost.name'] ) )
			$slots .= "GA_googleAddSlot('ca-pub-{$this->params->dfp_slots['belowpost.id']}', '{$this->params->dfp_slots['belowpost.name']}');\n";

		return $slots;
	}

	/**
	 * Insert the ad onto the page
	 *
	 * @since 0.1
	 */
	function insert_ad( $content ) {
		$dfp_script = 'GA_googleFillSlot("' . $this->params->dfp_slots['belowpost.name'] . '");' . "\n";
		// The class="wpadvert" is required to hide clicks in this div from click tracking
		$aux_class = '';
		$dfp_under = <<<GAM
		<div class="wpcnt">
		<div class="wpa{$aux_class}">

			<a class="wpa-about" href="http://en.wordpress.com/about-these-ads/" rel="nofollow">About these ads</a>

			<div class="u"></div>

		</div>
		</div>
GAM;

		$ad = <<<HTML
		<div class="wpcnt">
			<div class="wpa">
				<a class="wpa-about" href="http://en.wordpress.com/about-these-ads/" rel="nofollow">About these ads</a>
				<div class="u">
					<script type='text/javascript'>
					$dfp_script
					</script>
				</div>
			</div>
		</div>
HTML;

	return $content . $ad;
	}

	function insert_mobile_ad( $content ) {
		if ( ! $this->params->should_show_mobile() )
			return $content;

		// TODO check adsafe
		$mopub_under = <<<HTML
		<div class="mpb" style="text-align: center; margin: 0px auto; width: 100%">
			<div><a class="wpadvert-about" style="padding: 0 1px; display: block; font: 9px/1 sans-serif; text-decoration: underline;" href="http://en.wordpress.com/about-these-ads/" rel="nofollow">About these ads</a></div>
			<script type="text/javascript">
				var mopub_ad_unit="agltb3B1Yi1pbmNyDQsSBFNpdGUY5_TTFQw";
				var mopub_ad_width=300;
				var mopub_ad_height=250;
				var mopub_keywords="adsafe";
				jQuery( window ).load( function() {
					if ( jQuery(".mpb script[src*='shareth.ru']").length > 0 || jQuery(".mpb iframe[src*='viewablemedia.net']").length > 0 ) {
						jQuery( '.mpb iframe' ).css( {'width':'400px','height':'267px'} );
					} else if ( jQuery(".mpb script[src*='googlesyndication.com']").length > 0 ) {
						jQuery( '.mpb iframe' ).css( {'width':'350px','height':'250px'} );
					}
				});
			</script>
			<script src="http://ads.mopub.com/js/client/mopub.js"></script>
		</div>
HTML;

		return $content . $mopub_under;
	}

	/**
	 * Enforce Jetpack activated. Otherwise, load special no-jetpack admin.
	 *
	 * @return true if Jetpack is active and activated
	 *
	 * @since 0.1
	 */
	private static function check_jetpack() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( ! is_plugin_active( 'jetpack/jetpack.php' ) || ! ( Jetpack::is_active() || Jetpack::is_development_mode() ) ) {
			require_once( ADCONTROL_ROOT . '/php/no-jetpack.php' );
			return false;
		}

		return true;
	}
}

global $adcontrol;
$adcontrol = new AdControl();
