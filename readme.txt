=== AdControl ===
Contributors: automattic, derekspringer, jeherve, rclations
Tags: advertising, ad codes, ads
Requires at least: 3.4
Tested up to: 5.6.1
Stable tag: 1.5

Harness WordPress.com's advertising partners for your own website.

== Description ==

Please note: this plugin is officially deprecated. If you are still interested in using this service please utilize [the offical Ads module included in Jetpack.](https://jetpack.com/features/traffic/ads/)

With traditional advertising relationships a publisher or blogger has two relationships and platforms--one for publishing and the other for advertising. AdControl combines the two partnerships into one.

AdControl is a new service from Automattic that extends our advertising scale and know-how to eligible sites using WordPress. We believe it is a special partnership between the largest Internet publishing platform and our users. The team at AdControl and WordPress.com are very excited about the potential to help our self-hosted publishers to earn income from their sites with high quality ads and strong rates from advertisers.

Make sure you [apply to WordAds](https://wordads.co/signup/) for each site you want to run AdControl on.

Requires [Jetpack](https://jetpack.com/) to be installed and connected. [Help getting started.](https://jetpack.com/support/getting-started-with-jetpack/)

== Installation ==

1. [Apply to WordAds](https://wordads.co/signup/) for each site you want to run AdControl on.
1. [Install and activate Jetpack.](https://wordpress.org/plugins/jetpack/installation/)
1. Upload `AdControl` to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Fill in other options in the Dashboard -> Settings -> AdControl page.

== Screenshots ==

1. Ads display on your site below the post
2. Set your AdControl settings in the Dashboard -> Settings -> AdControl page
3. Add an AdControl widget via Dashboard -> Appearance -> Widgets

== Changelog ==

= 1.5 =

* Deprication warning

= 1.4 =

* Updated to new `async` ad loads.
* Defaulted header unit to on upon activation.
* Updated widget section ids to auto-increment.

= 1.3.1 =

* Updated to new header tag.

= 1.3 =

* New options to control which page types below post ads appear on.
* Added option for 2nd below post ad unit.
* Some tweaks for 'twenty(fourteen|fifteen|seventeen)' themes.
* Some speed/prefetch optimizations.

= 1.2.1 =

* Removed link from "Advertisements" notice
* Updated house ads to HTML5

= 1.2 =

* Added support for house ads while we wait for site to get approved with our partner networks.
* Replaced "About these ads" notice with "Advertisements" so Google doesn't get cranky.
* Default values for Widgets should prevent some errors in cases where the widget can show up before values are set.

= 1.1.3 =

* Changed the bulk of the JavaScript in the header from being inline to an external JavaScript.

= 1.1.2 =

* Added CloudFlare detection for widget.

= 1.1.1 =

* Added automatic detection for sites using CloudFlare (Rocket Loader), excluding ad scripts from being included in the optimizations done by Rocket Loader.
* Added a constant to manually turn on CloudFlare support (Rocket Loader)
 1. 'define( 'ADCONTROL_CLOUDFLARE', true );' in wp-config will add a tag to the script tags to prevent Rocket Loader from including them in its optimization process, which can cause problems with ads loading properly.

= 1.1 =

* Updated Jetpack check for non-traditional installations.
* Updated debug output to use better color scheme. Green = good, red = problem.
* Added filters to disable units ad-hoc.
  1. `adcontrol_header_disable` set to true will prevent header unit from displaying.
  2. `adcontrol_inpost_disable` set to true will prevent the in post unit from displaying.

= 1.0.5 =

* Fixed some metadata URL escaping.

= 1.0.4 =

* Added some API debug options.

= 1.0.3 =

* More text domain fixes.
* Added option for turning off leaderboard on mobile.

= 1.0.2 =

* Text domain fixes.
* Added missing uninstall.php.

= 1.0.1 =

Cleanup and backend fixes.

= 1.0 =

Plugin streamlining and cleanup before release.
