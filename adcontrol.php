<?php

/*
Plugin Name: AdControl for WordPress
Plugin URI: http://automattic.com
Description: Harness the power of WordPress.com's advertising partners for your own blog.
Author: Automattic
Version: 0.1.1-beta
Author URI: http://automattic.com
Text Domain: adcontrol

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

define( 'ADCONTROL_VERSION', '0.1-beta' );
define( 'ADCONTROL_ROOT' , dirname( __FILE__ ) );
define( 'ADCONTROL_FILE_PATH' , ADCONTROL_ROOT . '/' . basename( __FILE__ ) );
define( 'ADCONTROL_URL' , plugins_url( '/', __FILE__ ) );
define( 'ADCONTROL_DFP_ID',  '3443918307802676' );
// TODO: Store MOPUB_ID with each ad unit. Each ad unit in MOPUB has its own ID.
define( 'ADCONTROL_MOPUB_ID', '9ba30f9603ef4828aa35dd8199a961f5' );

require_once( ADCONTROL_ROOT . '/php/widgets.php' );

class AdControl {

	public $params = null;

	/**
	 * The different supported ad types.
	 * v0.1 - mrec only for now
	 * @var array
	 */
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
	 * Convenience function for grabbing options from params->options
	 * @param  string $option the option to grab
	 * @param  mixed  $default (optional)
	 * @return option or $default if not set
	 *
	 * @since 0.1
	 */
	function option( $option, $default = false ) {
		if ( ! isset( $this->params->options[$option] ) )
			return $default;

		return $this->params->options[$option];
	}

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
		// requires Jetpack for now (probably always)
		if ( ! self::check_jetpack() )
			return;

		load_plugin_textdomain(
			'adcontrol',
			false,
			plugin_basename( dirname( __FILE__ ) ) . '/languages/'
		);

		if ( is_admin() ) {
			require_once( ADCONTROL_ROOT . '/php/admin.php' );
			require_once( ADCONTROL_ROOT . '/php/ajax.php' );
			return;
		}

		// bail on infinite scroll
		if ( self::is_infinite_scroll() )
			return;

		require_once( ADCONTROL_ROOT . '/php/user-agent.php' );
		require_once( ADCONTROL_ROOT . '/php/params.php' );

		$this->params = new AdControl_Params();
		// check reasons to bail
		if ( 'signed' != $this->option( 'tos' ) )
			return; // only show ads for folks that have signed the TOS
		if ( 'pause' == $this->option( 'show_to_logged_in' ) )
			return; // don't show if paused
		if ( ! current_user_can( 'manage_options' ) && 'no' == $this->option( 'show_to_logged_in' ) && is_user_logged_in() )
			return; // don't show to logged in users (if that option is selected)
		if ( $this->params->is_mobile() && is_ssl() )
			return; // Not support mobile ads over SSL at the moment

		$this->insert_adcode();
		// $this->insert_extras(); // TODO configure extras to show always if desired
	}

	/**
	 * Check for Jetpack's The_Neverending_Home_Page and use got_infinity
	 * @return boolean true if load came from infinite scroll
	 *
	 * @since 0.1
	 */
	public static function is_infinite_scroll() {
		return current_theme_supports( 'infinite-scroll' ) &&
				class_exists( 'The_Neverending_Home_Page' ) &&
				The_Neverending_Home_Page::got_infinity();
	}

	/**
	 * Add the actions/filters to insert the ads. Checks for mobile or desktop.
	 *
	 * @since 0.1
	 */
	private function insert_adcode() {
		// check for mobile, then insert ads
		add_action( 'wp_head', array( $this, 'insert_targeting_details' ) );
		if ( $this->params->is_mobile() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_mobile_scripts' ) );
			add_filter( 'the_content', array( $this, 'insert_mobile_ad' ) );
			add_filter( 'the_excerpt', array( $this, 'insert_mobile_ad' ) );
		} else {
			// TODO check adsafe
			$this->params->add_slot( 'belowpost', 'Adcontrol_4_org_300', 400, 267, '3443918307802676' );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_filter( 'the_content', array( $this, 'insert_ad' ) );
			add_filter( 'the_excerpt', array( $this, 'insert_ad' ) );

			if ( ! empty( $this->params->options['adsense_leader_set'] )
					&& ! empty( $this->params->options['enable_advanced_settings'] ) ) {
				add_action( 'wp_head', array( $this, 'insert_header_ad' ), 100 );
			}
		}
	}

	/**
	 * Add the actions/filters to insert extra-network features (e.g. Taboola, Promoted Posts).
	 *
	 * @since 0.1
	 */
	private function insert_extras() {
		require_once( ADCONTROL_ROOT . '/php/networks/taboola.php' );
		new AdControl_Taboola( $this->params );

		require_once( ADCONTROL_ROOT . '/php/networks/coull.php' );
		new AdControl_Coull( $this->params );

		require_once( ADCONTROL_ROOT . '/php/networks/skimlinks.php' );
		new AdControl_Skimlinks( $this->params );
	}

	/**
	 * Inserts ad targeting details for use by external scripts
	 */
	function insert_targeting_details() {
		echo $this->params->get_ad_targeting_details();
	}

	/**
	 * Register desktop scripts and styles
	 *
	 * @since 0.1
	 */
	function enqueue_scripts() {
		// JS
		wp_enqueue_script(
			'ac-adclk',
			ADCONTROL_URL . 'js/adclk.js',
			array( 'jquery' ),
			'2013-06-21',
			true
		);

		$data = array(
			'slot'  => 'belowpost', // TODO add other slots?
		);
		wp_localize_script( 'ac-adclk', 'ac_adclk', $data );

		add_action( 'wp_head', array( $this, 'insert_head_adcontrol' ) );
		add_action( 'wp_head', array( $this, 'insert_head_gam' ) ); // TODO still GAM?

		// CSS
		wp_enqueue_style(
			'noticon-font',
			'//s0.wordpress.com/i/noticons/noticons.css',
			false,
			'2013-08-28'
		);

		wp_enqueue_style(
			'adcontrol',
			ADCONTROL_URL . 'css/ac-style.css',
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
			'ac-adclk',
			ADCONTROL_URL . 'js/adclk.js',
			array( 'jquery' ),
			'2013-06-21',
			true
		);

		$data = array(
			'slot'  => 'belowpost', // TODO add other slots?
		);
		wp_localize_script( 'ac-adclk', 'ac_adclk', $data );
	}

	/**
	 * Insert any extra stuff that goes into the pages <head>
	 *
	 * @since 0.1
	 */
	function insert_head_adcontrol() {
		$part = ( is_home() || is_archive() ) ? 'index' : 'permalink';
		$domain = esc_js( $_SERVER['HTTP_HOST'] );
		$current_page_url = esc_url( $this->params->url );

		echo <<<HTML
		<script type="text/javascript">
		var wpcom_ads = { bid: '{$this->params->blog_id}', pt: '$part', ac: 1, domain: '$domain', url: '$current_page_url', };
		</script>
HTML;
	}

	/**
	 * DFP/GAM scripts in the <head>
	 *
	 * @since 0.1
	 */
	function insert_head_gam() {
		$about = __( 'About these ads', 'adcontrol' );
		echo '
		<script type="text/javascript" src="http://partner.googleadservices.com/gampad/google_service.js"></script>
		<script type="text/javascript">
			GS_googleAddAdSenseService("ca-pub-', ADCONTROL_DFP_ID ,'");
			GS_googleEnableAllServices();
		</script>';
		if ( ! empty( $this->params->options['amazon_match_buy'] ) ) {
			require_once( ADCONTROL_ROOT . '/php/networks/amazon.php' );
		}
		echo '
		<script type="text/javascript">
			',$this->params->get_dfp_targetting(),'
		</script>
		<script type="text/javascript">
			',$this->get_googleaddslots(),'
		</script>
		<script type="text/javascript">
			GA_googleAddAdSensePageAttr("google_page_url", "', esc_js( $this->params->url ) ,'");
			GA_googleFetchAds();
		</script>
		<script type="text/javascript">
		jQuery( window ).load( function() {
			jQuery( "a.wpadvert-about" ).text( "', esc_js( $about ) ,'" );
		} );
		</script>
';
	}

	/**
	 * Tell DFP about the slites we intend to use.
	 *
	 * @since 0.1
	 */
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

		return $content . $this->get_ad( 'belowpost' );
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
	 * Inserts ad into header
	 *
	 * @since 0.1
	 */
	function insert_header_ad() {
		echo $this->get_ad( 'top', 'adsense' );
	}

	/**
	 * [get_ad description]
	 * @param  string $spot top, side, or belowpost
	 * @param  string $type dfp or adsense
	 */
	function get_ad( $spot, $type = 'dfp' ) {
		if ( 'dfp' == $type ) {
			$snippet = '<script type="text/javascript">GA_googleFillSlot("' . $this->params->dfp_slots[$spot . '.name'] . '");</script>';
		} elseif ( 'adsense' == $type ) {
			require_once( ADCONTROL_ROOT . '/php/networks/adsense.php' );
			if ( 'top' == $spot )
				$spot = 'leader';

			$pub = $this->params->options['adsense_publisher_id'];
			$tag = $this->params->options['adsense_' . $spot . '_tag_id'];
			$width = AdControl::$ad_tag_ids[$this->params->options['adsense_' . $spot . '_tag_unit']]['width'];
			$height = AdControl::$ad_tag_ids[$this->params->options['adsense_' . $spot . '_tag_unit']]['height'];
			$snippet = AdControl_Adsense::get_asynchronous_adsense( $pub, $tag, $width, $height );
		}

		return <<<HTML
		<div class="wpcnt">
			<div class="wpa">
				<a class="wpa-about" href="http://en.wordpress.com/about-these-ads/" rel="nofollow">About these ads</a>
				<div class="u $spot">
					$snippet
				</div>
			</div>
		</div>
HTML;
	}

	/**
	 * Enforce Jetpack activated. Otherwise, load special no-jetpack admin.
	 *
	 * @return true if Jetpack is active and activated
	 *
	 * @since 0.1
	 */
	public static function check_jetpack() {
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
