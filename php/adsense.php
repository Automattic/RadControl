<?php

class AdControl_Adsense {

	/**
	 * Generate synchronous adsense code
	 *
	 * @since 0.1
	 */
	public static function get_synchronous_adsense( $pub, $tag, $width, $height, $url = false ) {
		if ( $url )
			$url = 'google_page_url = "' . $url . '";';

		return <<<HTML
		<script type="text/javascript">
		<!--
		google_ad_client = "ca-$pub";
		google_ad_slot = "$tag";
		google_ad_width = $width;
		google_ad_height = $height;
		$url
		//-->
		</script>
		<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
HTML;
	}

	/**
	 * Generate asynchronous adsense code
	 *
	 * @since 0.1
	 */
	public static function get_asynchronous_adsense( $pub, $tag, $width, $height, $url = false ) {
		// TODO URL
		return <<<HTML
		<script async src="http://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
		<ins class="adsbygoogle"
			 style="display:inline-block;width:{$width}px;height:{$height}px"
			 data-ad-client="ca-$pub"
			 data-ad-slot="$tag"></ins>
		<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
HTML;
	}
}
