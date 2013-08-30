<?php
class AdControl_Adsense {
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
}
