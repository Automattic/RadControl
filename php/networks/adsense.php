<?php

class AdControl_Adsense {

	/**
	 * Generate synchronous adsense code
	 *
	 * @since 0.1
	 */
	public static function get_synchronous_adsense( $pub, $tag, $width, $height, $url = '' ) {
		if ( ! empty( $url ) )
			$url = 'google_page_url = "' . esc_url( $url ) . '";';

		$data_tags = ( AdControl_Params::is_cloudflare() ) ? ' data-cfasync="false"' : '';
		$output = '
		<script' . $data_tags . ' type="text/javascript">
		<!--
		google_ad_client = "ca-' . esc_attr( $pub ) . '";
		google_ad_slot = "' . esc_attr( $tag ) . '";
		google_ad_width = ' . absint( $width ) . ';
		google_ad_height = ' . absint( $height ) . ';
		$url
		//-->
		</script>
		<script' . $data_tags . ' src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>';
		return $output;
	}

	/**
	 * Generate asynchronous adsense code
	 *
	 * @since 0.1
	 */
	public static function get_asynchronous_adsense( $pub, $tag, $width, $height, $url = '' ) {
		$data_tags = ( AdControl_Params::is_cloudflare() ) ? ' data-cfasync="false"' : '';
		// TODO URL
		return <<<HTML
		<script$data_tags async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
		<ins class="adsbygoogle"
			 style="display:inline-block;width:{$width}px;height:{$height}px"
			 data-ad-client="ca-$pub"
			 data-ad-slot="$tag"></ins>
		<script$data_tags type="text/javascript">(adsbygoogle = window.adsbygoogle || []).push({});</script>
HTML;
	}
}
