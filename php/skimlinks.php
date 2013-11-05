<?php

/**
 * Inserts Skimlinks snippet to add affiliate links to relevant links
 *
 * @since 0.1
 */
class AdControl_Skimlinks {

	/**
	 * Params from main AdControl
	 */
	private $params;

	public function __construct( $params ) {
		$this->params = $params;
		add_action( 'wp_footer', array( &$this, 'skimlinks_footer_js' ) );
	}

	function skimlinks_footer_js() {
		$pub_id   = '725X584219'; // WordPress.com Default Skimlinks Publisher ID
		$sitename = '58pfl9955.wordpress.com'; // default sitename
		$tracking = $this->params->blog_id;

		echo <<<HTML
		<script type="text/javascript">
			var skimlinks_pub_id = "$pub_id"
			var skimlinks_sitename = "$sitename";
			var skimlinks_tracking = "$tracking";
		</script>
		<script type="text/javascript" src="http://s.skimresources.com/js/725X1342.skimlinks.js"></script>
HTML;

	}
}
