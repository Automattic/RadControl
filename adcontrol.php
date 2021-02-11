<?php

/*
Plugin Name: AdControl
Plugin URI: https://wordads.co/
Description: Harness WordPress.com's advertising partners for your own website. Requires <a href="https://jetpack.com/" target="_blank">Jetpack</a> to be installed and connected.
Author: Automattic
Version: 1.5
Author URI: https://automattic.com
Text Domain: adcontrol
Domain Path: /languages

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

define( 'ADCONTROL_VERSION', '1.5' );
define( 'ADCONTROL_ROOT', dirname( __FILE__ ) );
define( 'ADCONTROL_BASENAME', plugin_basename( __FILE__ ) );
define( 'ADCONTROL_FILE_PATH', ADCONTROL_ROOT . '/' . basename( __FILE__ ) );
define( 'ADCONTROL_URL', plugins_url( '/', __FILE__ ) );
define( 'ADCONTROL_API_TEST_ID', '26942' );
define( 'ADCONTROL_API_TEST_ID2', '114160' );

add_action( 'plugins_loaded', array( 'AdControl', 'plugin_textdomain'), 99 );

require_once( ADCONTROL_ROOT . '/php/widgets.php' );
require_once( ADCONTROL_ROOT . '/php/api.php' );
require_once( ADCONTROL_ROOT . '/php/cron.php' );

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
		if ( ! isset( $this->params->options[ $option ] ) ) {
			return $default;
		}

		return $this->params->options[ $option ];
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
		if ( ! self::check_jetpack() ) {
			return;
		}

		// bail on infinite scroll
		if ( self::is_infinite_scroll() ) {
			return;
		}

		if ( is_admin() ) {
			require_once( ADCONTROL_ROOT . '/php/admin.php' );
			return;
		}

		require_once( ADCONTROL_ROOT . '/php/params.php' );
		$this->params = new AdControl_Params();
		if ( $this->should_bail() ) {
			return;
		}

		$this->insert_adcode();
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
		add_action( 'wp_head', array( $this, 'insert_head_meta' ), 20 );
		add_action( 'wp_head', array( $this, 'insert_head_iponweb' ), 30 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		if ( ! apply_filters( 'adcontrol_content_disable', false ) ) {
			add_filter( 'the_content', array( $this, 'insert_ad' ) );
		}
		if ( ! apply_filters( 'adcontrol_excerpt_disable', false ) ) {
			add_filter( 'the_excerpt', array( $this, 'insert_ad' ) );
		}

		if ( $this->option( 'leaderboard', true ) ) {
			switch ( get_stylesheet() ) {
				case 'twentyseventeen':
				case 'twentyfifteen':
				case 'twentyfourteen':
					add_action( 'wp_footer', array( $this, 'insert_header_ad_special' ) );
					break;
				default:
					add_action( 'wp_head', array( $this, 'insert_header_ad' ), 100 );
					break;
			}
		}
	}

	/**
	 * Register desktop scripts and styles
	 *
	 * @since 0.1
	 */
	function enqueue_scripts() {
		wp_enqueue_style(
			'adcontrol',
			ADCONTROL_URL . 'css/ac-style.css',
			array(),
			'2015-12-18'
		);
	}

	/**
	 * IPONWEB metadata used by the various scripts
	 * @return void
	 */
	function insert_head_meta() {
		$themename = esc_js( get_stylesheet() );
		$pagetype = intval( $this->params->get_page_type_ipw() );
		$data_tags = ( $this->params->cloudflare ) ? ' data-cfasync="false"' : '';
		$site_id = $this->params->blog_id;
		echo <<<HTML
		<script$data_tags type="text/javascript">
			var __ATA_PP = { pt: $pagetype, ht: 3, tn: '$themename', amp: false, siteid: $site_id };
			var __ATA = __ATA || {};
			__ATA.cmd = __ATA.cmd || [];
			__ATA.criteo = __ATA.criteo || {};
			__ATA.criteo.cmd = __ATA.criteo.cmd || [];
		</script>
HTML;
	}

	/**
	 * IPONWEB scripts in <head>
	 *
	 * @since 0.2
	 */
	function insert_head_iponweb() {
		$data_tags = ( $this->params->cloudflare ) ? ' data-cfasync="false"' : '';
		echo <<<HTML
		<link rel='dns-prefetch' href='//s.pubmine.com' />
		<link rel='dns-prefetch' href='//x.bidswitch.net' />
		<link rel='dns-prefetch' href='//static.criteo.net' />
		<link rel='dns-prefetch' href='//ib.adnxs.com' />
		<link rel='dns-prefetch' href='//aax.amazon-adsystem.com' />
		<link rel='dns-prefetch' href='//bidder.criteo.com' />
		<link rel='dns-prefetch' href='//cas.criteo.com' />
		<link rel='dns-prefetch' href='//gum.criteo.com' />
		<link rel='dns-prefetch' href='//ads.pubmatic.com' />
		<link rel='dns-prefetch' href='//gads.pubmatic.com' />
		<link rel='dns-prefetch' href='//tpc.googlesyndication.com' />
		<link rel='dns-prefetch' href='//ad.doubleclick.net' />
		<link rel='dns-prefetch' href='//googleads.g.doubleclick.net' />
		<link rel='dns-prefetch' href='//www.googletagservices.com' />
		<link rel='dns-prefetch' href='//cdn.switchadhub.com' />
		<link rel='dns-prefetch' href='//delivery.g.switchadhub.com' />
		<link rel='dns-prefetch' href='//delivery.swid.switchadhub.com' />
		<script$data_tags async type="text/javascript" src="//s.pubmine.com/head.js"></script>
HTML;
	}

	/**
	 * Insert the ad onto the page
	 *
	 * @since 0.1
	 */
	function insert_ad( $content ) {
		/**
		 * Allow third-party tools to disable the display of in post ads.
		 *
		 * @since 1.1
		 *
		 * @param bool true Should the in post unit be disabled. Default to false.
		 */
		$disable = apply_filters( 'adcontrol_inpost_disable', false );
		if ( ! $this->params->should_show() || $disable ) {
			return $content;
		}

		$ad_type = $this->option( 'wordads_house', true ) ? 'house' : 'iponweb';
		return $content . $this->get_ad( 'belowpost', $ad_type );
	}

	/**
	 * Inserts ad into header
	 *
	 * @since 0.1
	 */
	function insert_header_ad() {
		/**
		 * Allow third-party tools to disable the display of header ads.
		 *
		 * @since 1.1
		 *
		 * @param bool true Should the header unit be disabled. Default to false.
		 */
		$disable = apply_filters( 'adcontrol_header_disable', false );
		if ( $disable ) {
			return;
		}

		if ( ! $this->params->mobile_device || $this->option( 'leaderboard_mobile', true ) ) {
			$ad_type = $this->option( 'wordads_house', true ) ? 'house' : 'iponweb';
			echo $this->get_ad( 'top', $ad_type );
		}
	}

	/**
	 * Special cases for inserting header unit via jQuery
	 *
	 * @since 1.3
	 */
	function insert_header_ad_special() {
		/**
		 * Allow third-party tools to disable the display of header ads.
		 *
		 * @module wordads
		 *
		 * @since 1.1
		 *
		 * @param bool true Should the header unit be disabled. Default to false.
		 */
		if ( apply_filters( 'adcontrol_header_disable', false ) ) {
			return;
		}

		$selector = '#content';
		switch ( get_stylesheet() ) {
			case 'twentyseventeen':
				$selector = '#content';
				break;
			case 'twentyfifteen':
				$selector = '#main';
				break;
			case 'twentyfourteen':
				$selector = 'article:first';
				break;
		}

		if ( ! $this->params->mobile_device || $this->option( 'leaderboard_mobile', true ) ) {
			$ad_type = $this->option( 'wordads_house' ) ? 'house' : 'iponweb';
			echo $this->get_ad( 'top', $ad_type );
			echo <<<HTML
			<script type="text/javascript">
				jQuery('.wpcnt-header').insertBefore('$selector');
			</script>
HTML;
		}
	}

	/**
	 * Get the ad for the spot and type.
	 * @param  string $spot top, side, or belowpost
	 * @param  string $type iponweb or adsense
	 */
	function get_ad( $spot, $type = 'iponweb' ) {
		$snippet = '';
		$blocker_unit = 'mrec';
		if ( 'iponweb' == $type ) {
			$section_id = ADCONTROL_API_TEST_ID;
			$width = 300;
			$height = 250;
			$second_belowpost = '';
			$snippet = '';
			if ( 'top' == $spot ) {
				// mrec for mobile, leaderboard for desktop
				$section_id = 0 === $this->params->blog_id ? ADCONTROL_API_TEST_ID : $this->params->blog_id . '2';
				$width = $this->params->mobile_device ? 300 : 728;
				$height = $this->params->mobile_device ? 250 : 90;
				$blocker_unit = $this->params->mobile_device ? 'top_mrec' : 'top';
				$snippet = $this->get_ad_snippet( $section_id, $height, $width, $blocker_unit );
			} else if ( 'belowpost' == $spot ) {
				$section_id = 0 === $this->params->blog_id ? ADCONTROL_API_TEST_ID : $this->params->blog_id . '1';
				$width = 300;
				$height = 250;
				$snippet = $this->get_ad_snippet( $section_id, $height, $width, 'mrec', 'float:left;margin-right:5px;margin-top:0px;' );
				if ( $this->option( 'second_belowpost', true ) ) {
					$section_id2 = 0 === $this->params->blog_id ? ADCONTROL_API_TEST_ID2 : $this->params->blog_id . '4';
					$snippet .= $this->get_ad_snippet( $section_id2, $height, $width, 'mrec2', 'float:left;margin-top:0px;' );
				}
			}
		} else if ( 'house' == $type ) {
			$leaderboard = 'top' == $spot && ! $this->params->mobile_device;
			$snippet = $this->get_house_ad( $leaderboard ? 'leaderboard' : 'mrec' );
			if ( 'belowpost' == $spot && $this->option( 'second_belowpost', true ) ) {
				$snippet .= $this->get_house_ad( $leaderboard ? 'leaderboard' : 'mrec' );
			}
		}

		$header = 'top' == $spot ? 'wpcnt-header' : '';
		$about = __( 'Advertisements', 'adcontrol' );
		return <<<HTML
		<div class="wpcnt $header">
			<div class="wpa">
				<span class="wpa-about">$about</span>
				<div id="ac-$spot" class="u $spot">
					$snippet
				</div>
			</div>
		</div>
HTML;
	}

	/**
	 * Returns the snippet to be inserted into the ad unit
	 * @param  int $section_id
	 * @param  int $height
	 * @param  int $width
	 * @param  string $css
	 * @return string
	 *
	 * @since 1.4
	 */
	function get_ad_snippet( $section_id, $height, $width, $adblock_unit = 'mrec', $css = '' ) {
		$this->ads[] = array( 'id' => $section_id, 'width' => $width, 'height' => $height );
		$data_tags = $this->params->cloudflare ? ' data-cfasync="false"' : '';
		$adblock_ad = $this->get_adblocker_ad( $adblock_unit );

		return <<<HTML
		<div style="padding-bottom:15px;width:{$width}px;height:{$height}px;$css">
			<div id="atatags-{$section_id}">
				<script$data_tags type="text/javascript">
				__ATA.cmd.push(function() {
					__ATA.initSlot('atatags-{$section_id}',  {
						collapseEmpty: 'before',
						sectionId: '{$section_id}',
						width: {$width},
						height: {$height}
					});
				});
				</script>
				$adblock_ad
			</div>
		</div>
HTML;
	}

	/**
	 * Get Criteo Acceptable Ad unit
	 * @param  string $unit mrec, mrec2, widesky, top, top_mrec
	 *
	 * @since 1.3
	 */
	public function get_adblocker_ad( $unit = 'mrec' ) {
		$data_tags = $this->params->cloudflare ? ' data-cfasync="false"' : '';
		$criteo_id = mt_rand();
		$height = 250;
		$width = 300;
		$zone_id = 388248;
		if ( 'mrec2' == $unit ) { // 2nd belowpost
			$zone_id = 837497;
		} else if ( 'widesky' == $unit ) { // sidebar
			$zone_id = 563902;
			$width = 160;
			$height= 600;
		} else if ( 'top' == $unit ) { // top leaderboard
			$zone_id = 563903;
			$width = 728;
			$height = 90;
		} else if ( 'top_mrec' == $unit ) { // top mrec
			$zone_id = 563903;
		}

		return <<<HTML
		<div id="crt-$criteo_id" style="width:{$width}px;height:{$height}px;display:none !important;"></div>
		<script$data_tags type="text/javascript">
		(function(){var c=function(){var a=document.getElementById("crt-{$criteo_id}");window.Criteo?(a.parentNode.style.setProperty("display","inline-block","important"),a.style.setProperty("display","block","important"),window.Criteo.DisplayAcceptableAdIfAdblocked({zoneid:{$zone_id},containerid:"crt-{$criteo_id}",collapseContainerIfNotAdblocked:!0,callifnotadblocked:function(){a.style.setProperty("display","none","important");a.style.setProperty("visbility","hidden","important")}})):(a.style.setProperty("display","none","important"),a.style.setProperty("visibility","hidden","important"))};if(window.Criteo)c();else{if(!__ATA.criteo.script){var b=document.createElement("script");b.src="//static.criteo.net/js/ld/publishertag.js";b.onload=function(){for(var a=0;a<__ATA.criteo.cmd.length;a++){var b=__ATA.criteo.cmd[a];"function"===typeof b&&b()}};(document.head||document.getElementsByTagName("head")[0]).appendChild(b);__ATA.criteo.script=b}__ATA.criteo.cmd.push(c)}})();
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
	public static function check_jetpack() {
		if ( ! class_exists( 'Jetpack' )
				|| ! method_exists( 'Jetpack_Client', 'wpcom_json_api_request_as_blog' )
				|| ! ( Jetpack::is_active() || Jetpack::is_development_mode() ) ) {

			if ( is_admin() ) {
				require_once( ADCONTROL_ROOT . '/php/no-jetpack.php' );
			}

			return false;
		}

		return true;
	}

	/**
	 * Check the reasons to bail before we attempt to insert ads.
	 * @return true if we should bail (don't insert ads)
	 *
	 * @since 0.1
	 */
	public function should_bail() {
		if ( ! $this->option( 'wordads_approved' ) ) {
			return true; // user isn't approved for AdControl
		}

		if ( ! $this->option( 'tos' ) ) {
			return true; // only show ads for folks that have signed the TOS
		}

		if ( 'pause' == $this->option( 'show_to_logged_in' ) ) {
			return true; // don't show if paused
		}

		if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) && 'no' == $this->option( 'show_to_logged_in' ) ) {
			return true; // don't show to logged in users (if that option is selected)
		}

		return false;
	}

	/**
	 * Returns markup for HTML5 house ad base on unit
	 * @param  string $unit mrec, widesky, or leaderboard
	 * @return string       markup for HTML5 house ad
	 */
	public function get_house_ad( $unit = 'mrec' ) {
		if ( ! in_array( $unit, array( 'mrec', 'widesky', 'leaderboard' ) ) ) {
			$unit = 'mrec';
		}

		$width  = 300;
		$height = 250;
		if ( 'widesky' == $unit ) {
			$width  = 160;
			$height = 600;
		} else if ( 'leaderboard' == $unit ) {
			$width  = 728;
			$height = 90;
		}

		return <<<HTML
		<iframe
			src="https://s0.wp.com/wp-content/blog-plugins/wordads/house/html5/$unit/index.html"
			width="$width"
			height="$height"
			frameborder="0"
			scrolling="no"
			marginheight="0"
			marginwidth="0">
		</iframe>
HTML;
	}

	/**
	 * Activation hook actions
	 *
	 * @since 0.2
	 */
	public static function activate() {
		if ( self::check_jetpack() ) {
			// Grab status from API on activation if JP is active
			AdControl_API::update_tos_status_from_api();
			AdControl_API::update_wordads_status_from_api();
		}
	}

	/**
	 * Load language files
	 *
	 * @since 1.0.3
	 */
	public static function plugin_textdomain() {
		load_plugin_textdomain(
			'adcontrol',
			false,
			plugin_basename( dirname( __FILE__ ) ) . '/languages/'
		);
	}
}

add_action( 'admin_notices', 'adcontrol_deprecation_notice', 100 );
function adcontrol_deprecation_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$warning = sprintf(
		'<strong>Please note:</strong> The AdControl plugin is officially deprecated and will cease to function with future Jetpack releases. If you wish to continue using this service please utilize <a href="%s">the offical Ads module included in Jetpack.</a>',
		'https://jetpack.com/features/traffic/ads/'
	);
    ?>
    <div class="notice notice-warning">
        <p><?php echo $warning; ?></p>
    </div>
    <?php
}

register_activation_hook( __FILE__, array( 'AdControl', 'activate' ) );
register_activation_hook( __FILE__, array( 'AdControl_Cron', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AdControl_Cron', 'deactivate' ) );

global $adcontrol;
$adcontrol = new AdControl();
