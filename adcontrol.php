<?php

/*
Plugin Name: AdControl
Plugin URI: http://wordads.co/
Description: Harness WordPress.com's advertising partners for your own website. Requires <a href="http://jetpack.me/" target="_blank">Jetpack</a> to be installed and connected.
Author: Automattic
Version: 1.0.5
Author URI: http://automattic.com
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

define( 'ADCONTROL_VERSION', '1.0.5' );
define( 'ADCONTROL_ROOT', dirname( __FILE__ ) );
define( 'ADCONTROL_BASENAME', plugin_basename( __FILE__ ) );
define( 'ADCONTROL_FILE_PATH', ADCONTROL_ROOT . '/' . basename( __FILE__ ) );
define( 'ADCONTROL_URL', plugins_url( '/', __FILE__ ) );
define( 'ADCONTROL_API_TEST_ID', '26942' );

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
		if ( ! isset( $this->params->options[$option] ) ) {
			return $default;
		}

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
		$this->insert_extras();
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
		add_filter( 'the_content', array( $this, 'insert_ad' ) );
		add_filter( 'the_excerpt', array( $this, 'insert_ad' ) );

		if ( $this->option( 'leaderboard' ) ) {
			add_action( 'wp_head', array( $this, 'insert_header_ad' ), 100 );
		}
	}

	/**
	 * Add the actions/filters to insert extra-network features.
	 *
	 * @since 0.1
	 */
	private function insert_extras() {
		require_once( ADCONTROL_ROOT . '/php/networks/amazon.php' );
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
	 * @return [type] [description]
	 */
	function insert_head_meta() {
		$domain = $this->params->targeting_tags['Domain'];
		$pageURL = $this->params->targeting_tags['PageURL'];
		$adsafe = $this->params->targeting_tags['AdSafe'];
		echo <<<HTML
		<script type="text/javascript">
			var _ipw_custom = {
				wordAds: '1',
				domain:  '$domain',
				pageURL: '$pageURL',
				adSafe:  '$adsafe'
			};
		</script>
HTML;
	}

	/**
	 * IPONWEB scripts in <head>
	 *
	 * @since 0.2
	 */
	function insert_head_iponweb() {
		echo <<<HTML
		<!-- IPONWEB header script -->
		<script type="text/javascript">
			window.__ATA = {
				scriptSrc: '//s.pubmine.com/showad.js',
				slotPrefix: 'automattic-id-',
				customParams: _ipw_custom,
				initAd: function(o) {
					var o = o || {},
						g = window,
						d = g.document,
						wr = d.write,
						id = g.__ATA.id();
					wr.call(d, '<div id="' + id + '" data-section="' + (o.sectionId || 0) + '"' + (o.type ? ('data-type="' + o.type + '"') : '') + ' ' + (o.forcedUrl ? ('data-forcedurl="' + o.forcedUrl + '"') : '') + ' style="width:' + (o.width || 0) + 'px; height:' + (o.height || 0) + 'px;">');
					g.__ATA.displayAd(id);
					wr.call(d, '</div>');
				},
				displayAd: function(id) {
					window.__ATA.ids = window.__ATA.ids || {};
					window.__ATA.ids[id] = 1;
				},
				id: function() {
					return window.__ATA.slotPrefix + (parseInt(Math.random() * 10000, 10) + 1 + (new Date()).getMilliseconds());
				}
			};
			(function(d, ata) {
				var pr = "https:" === d.location.protocol ? "https:" : "http:",
					src = pr + ata.scriptSrc,
					st = "text/javascript";
				d.write('<scr' + 'ipt type="' + st + '" src="' + src + '"><\/scr' + 'ipt>');
			})(window.document, window.__ATA);
		</script>
HTML;
	}

	/**
	 * Insert the ad onto the page
	 *
	 * @since 0.1
	 */
	function insert_ad( $content ) {
		if ( ! $this->params->should_show() ) {
			return $content;
		}

		return $content . $this->get_ad( 'belowpost' );
	}

	/**
	 * Inserts ad into header
	 *
	 * @since 0.1
	 */
	function insert_header_ad() {
		if ( ! $this->params->mobile_device || $this->option( 'leaderboard_mobile', true ) ) {
			echo $this->get_ad( 'top' );
		}
	}

	/**
	 * Get the ad for the spot and type.
	 * @param  string $spot top, side, or belowpost
	 * @param  string $type iponweb or adsense
	 */
	function get_ad( $spot, $type = 'iponweb' ) {
		$snippet = '';
		if ( 'iponweb' == $type ) {
			$section_id = ADCONTROL_API_TEST_ID;
			$width = 300;
			$height = 250;
			if ( 'top' == $spot ) {
				// mrec for mobile, leaderboard for desktop
				$section_id = 0 === $this->params->blog_id ? ADCONTROL_API_TEST_ID : $this->params->blog_id . '2';
				$width = $this->params->mobile_device ? 300 : 728;
				$height = $this->params->mobile_device ? 250 : 90;
			} else if ( 'belowpost' ) {
				$section_id = 0 === $this->params->blog_id ? ADCONTROL_API_TEST_ID : $this->params->blog_id . '1';
				$width = 300;
				$height = 250;
			}

			$snippet = <<<HTML
			<script type='text/javascript'>
				(function(g){g.__ATA.initAd({sectionId:$section_id, width:$width, height:$height});})(window);
			</script>
HTML;
		}

		$about = __( 'About these ads', 'adcontrol' );
		return <<<HTML
		<div class="wpcnt">
			<div class="wpa">
				<a class="wpa-about" href="http://en.wordpress.com/about-these-ads/" rel="nofollow">$about</a>
				<div id="ac-$spot" class="u $spot">
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
		if ( ! is_plugin_active( 'jetpack/jetpack.php' )
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

register_activation_hook( __FILE__, array( 'AdControl', 'activate' ) );
register_activation_hook( __FILE__, array( 'AdControl_Cron', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AdControl_Cron', 'deactivate' ) );

global $adcontrol;
$adcontrol = new AdControl();
