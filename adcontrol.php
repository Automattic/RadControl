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
define( 'ADCONTROL_DFP_ID',  '3443918307802676' );
define( 'ADCONTROL_MOPUB_ID', '9ba30f9603ef4828aa35dd8199a961f5' );

class AdControl {

	private $params = null;

	public static $ad_tag_ids = array(
		'mrec' => array(
			'tag'       => '300x250_mediumrectangle',
			'height'    => '250',
			'width'     => '300',
		),
		'lrec' => array(
			'tag'       => '336x280_largerectangle',
			'height'    => '280',
			'width'     => '336',
		),
		'leaderboard' => array(
			'tag'       => '728x90_leaderboard',
			'height'    => '90',
			'width'     => '728',
		),
		'wideskyscraper' => array(
			'tag'       => '160x600_wideskyscraper',
			'height'    => '600',
			'width'     => '160',
		),
	);

	/**
	 * Instantiate the plugin
	 *
	 * @since 0.1
	 */
	function __construct() {
		add_action( 'init', array( &$this, 'init' ) );
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

		if ( is_admin() ) {
			require_once( ADCONTROL_ROOT . '/php/admin.php' );
			return;
		}

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
		require_once( ADCONTROL_ROOT . '/php/params.php' );

		$this->params = new AdControl_Params();

		// check reasons to bail
		if ( 'signed' != $this->params->options['tos'] )
			return; // only show ads for folks that have signed the TOS
		if ( 'pause' == $this->params->options['show_to_logged_in'] )
			return; // don't show if paused
		if ( ! is_super_admin() && 'no' == $this->params->options['show_to_logged_in'] && is_user_logged_in() )
			return; // don't show to logged in users (if that option is selected)
		if ( $this->params->is_mobile() && is_ssl() )
			return; // Not support mobile ads over SSL at the moment
		$this->insert_adcode();
	}

	private function insert_adcode() {
		// check for mobile, then insert ads
		if ( $this->params->is_mobile() ) {
			add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_mobile_scripts' ) );
			add_filter( 'the_content', array( &$this, 'insert_mobile_ad' ) );
			add_filter( 'the_excerpt', array( &$this, 'insert_mobile_ad' ) );
		} else {
			$slot_name = 'Adcontrol_4_org_300'; // TODO check adsafe
			$this->params->add_slot( 'belowpost', $slot_name, 400, 267, 3443918307802676 );
			add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
			add_filter( 'the_content', array( &$this, 'insert_ad' ) );
			add_filter( 'the_excerpt', array( &$this, 'insert_ad' ) );
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
			'slot'  => 'belowpost', // TODO add other slots?
		);
		wp_localize_script( 'wa-adclk', 'wa_adclk', $data );

		add_action( 'wp_head', array( &$this, 'insert_head_wordads' ) );
		add_action( 'wp_head', array( &$this, 'insert_head_gam' ) ); // TODO still GAM?

		// CSS
		wp_enqueue_style(
			'noticon-font',
			'//s0.wordpress.com/i/noticons/noticons.css',
			false,
			'2013-08-28'
		);

		wp_enqueue_style(
			'adcontrol',
			ADCONTROL_URL . 'css/adcontrol.css',
			array( 'noticon-font' ),
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
		var wpcom_ads = { bid: {$this->params->blog_id}, pt: '$part', ac: 1, domain: '$domain', url: '$current_page_url', };
		</script>
HTML;
	}

	function insert_head_gam() {
		$about = __( 'About these ads', 'adcontrol' );
		$dfp_id = ADCONTROL_DFP_ID;
		echo <<<HTML
		<script type="text/javascript" src="http://partner.googleadservices.com/gampad/google_service.js"></script>
		<script type="text/javascript">
			GS_googleAddAdSenseService("ca-pub-$dfp_id");
			GS_googleEnableAllServices();
		</script>
		<script type="text/javascript">
			{$this->params->get_dfp_targetting()}
		</script>
		<script type="text/javascript">
			{$this->get_googleaddslots()}
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

	function get_googleaddslots() {
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
		if ( ! $this->params->should_show() )
			return $content;

		$dfp_script = '<script type="text/javascript">GA_googleFillSlot("' . $this->params->dfp_slots['belowpost.name'] . '");</script>';
		$adsense = '';
		if ( $this->params->options['adsense_set'] ) {
			$pub = $this->params->options['publisher_id'];
			$tag = $this->params->options['tag_id'];
			$unit = $this->params->options['tag_unit'];
			$width = self::$ad_tag_ids[$unit]['width'];
			$height = self::$ad_tag_ids[$unit]['height'];
			$adsense = self::get_asynchronous_adsense( $pub, $tag, $width, $height );
		}

		$ad = <<<HTML
		<div class="wpcnt">
			<div class="wpa">
				<a class="wpa-about" href="http://en.wordpress.com/about-these-ads/" rel="nofollow">About these ads</a>
				<div class="u">
					$dfp_script
					$adsense
				</div>
			</div>
		</div>
HTML;

	return $content . $ad;
	}

	/**
	 * Insert mopub onto the page
	 *
	 * @since 0.1
	 */
	function insert_mobile_ad( $content ) {
		if ( ! $this->params->should_show_mobile() )
			return $content;

		// TODO check adsafe
		$mopub_id = ADCONTROL_MOPUB_ID;
		$mopub_under = <<<HTML
		<div class="mpb" style="text-align: center; margin: 0px auto; width: 100%">
			<div><a class="wpadvert-about" style="padding: 0 1px; display: block; font: 9px/1 sans-serif; text-decoration: underline;" href="http://en.wordpress.com/about-these-ads/" rel="nofollow">About these ads</a></div>
			<script type="text/javascript">
			var mopub_ad_unit="$mopub_id";
			var mopub_ad_width=300;
			var mopub_ad_height=250;
			var mopub_keywords="adsafe"; // TODO
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
	 * Generate synchronous adsense code
	 *
	 * @since 0.1
	 */
	public static function get_synchronous_adsense( $pub, $tag, $width, $height ) {
		return <<<HTML
		<script type="text/javascript"><!--
		google_ad_client = "ca-$pub";
		google_ad_slot = "$tag";
		google_ad_width = $width;
		google_ad_height = $height;
		//-->
		</script>
HTML;
	}

	/**
	 * Generate asynchronous adsense code
	 *
	 * @since 0.1
	 */
	public static function get_asynchronous_adsense( $pub, $tag, $width, $height ) {
		return <<<HTML
		<ins class="adsbygoogle"
			style="display:inline-block;width:{$width}px;height:{$height}px"
			data-ad-client="ca-$pub"
			data-ad-slot="$tag"></ins>
		<script>
		(adsbygoogle = window.adsbygoogle || []).push({});
		jQuery('.adsbygoogle').hide();
		</script>
HTML;
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
			if ( is_admin() )
				require_once( ADCONTROL_ROOT . '/php/no-jetpack.php' );

			return false;
		}

		return true;
	}
}

global $adcontrol;
$adcontrol = new AdControl();
