<?php

/*
Plugin Name: WordAds for WordPress
Plugin URI: http://automattic.com
Description: Harness the power of WordPress.com's advertising partners for your own blog.
Author: Automattic, Derek Springer
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

define( 'WORDADS_VERSION', '0.1-alpha' );
define( 'WORDADS_ROOT' , dirname( __FILE__ ) );
define( 'WORDADS_FILE_PATH' , WORDADS_ROOT . '/' . basename( __FILE__ ) );
define( 'WORDADS_URL' , plugins_url( '/', __FILE__ ) );

class WordAds {

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
		// bail on infinite scroll
		if ( current_theme_supports( 'infinite-scroll' ) &&
				class_exists( 'The_Neverending_Home_Page' ) &&
				The_Neverending_Home_Page::got_infinity() ) {

			return;
		}

		load_plugin_textdomain(
			'wordads',
			false,
			plugin_basename( dirname( __FILE__ ) ) . '/languages/'
		);

		require_once( WORDADS_ROOT . '/php/admin.php' );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_filter( 'the_content', array( $this, 'insert_ad' ) );
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
			WORDADS_URL . 'js/adclk.js',
			array( 'jquery' ),
			'2013-06-21',
			true
		);

		$params = array(
			'theme' => wp_get_theme()->Name,
			'slot'  => 'belowpost' // TODO add other slots?
		);
		wp_localize_script( 'wa-adclk', 'wa_adclk', $params );

		// CSS
		wp_enqueue_style(
			'genericon-font',
			WORDADS_URL . 'css/genericons/genericons.css',
			false,
			'2.0'
		);

		wp_enqueue_style(
			'wordads',
			WORDADS_URL . 'css/wordads.css',
			array( 'genericon-font' ),
			'2013-06-24'
		);
	}

	function insert_ad( $content ) {
		$ad = <<<HTML
<div class="wpcnt">
		<div class="wpa">
			<a class="wpa-about" href="http://en.wordpress.com/about-these-ads/" rel="nofollow">About these ads</a>
			<div class="u">
				 Accumsan rutrum toss the mousie tail flick, vel bat lay down in your way enim ut eat lick I don’t like that food chase the red dot
			</div>
		</div>
</div>
HTML;

	return $content . $ad;
	}
}

global $wordads;
$wordads = new WordAds();
