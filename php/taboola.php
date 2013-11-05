<?php

/**
 * Inserts the special Taboola code after the end of the content on a page.
 *
 * @since 0.1
 */
class AdControl_Taboola {

	/**
	 * Where Taboola should be inserted, either 'belowpost' or 'footer'
	 * @var string
	 */
	private $location = '';

	/**
	 * Params from main AdControl
	 */
	private $params;

	/**
	 * Call to init once wp object has loaded
	 */
	public function __construct( $params, $location = 'belowpost' ) {
		$this->params = $params;
		$this->location = $location;
		$this->init();
	}

	/**
	 * Enqueue special scripts and add action hooks
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		if ( 'belowpost' == $this->location ) {
			add_filter( 'the_content', array( &$this, 'insert_taboola_belowpost' ) );
			add_filter( 'the_excerpt', array( &$this, 'insert_taboola_belowpost' ) );
		} else {
			add_action( 'loop_end', array( &$this, 'insert_taboola_footer' ) );
		}
	}

	/**
	 * Enqueue the Taboola script in the footer
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			'taboola-loader',
			'http://cdn.taboolasyndication.com/libtrc/wordpress-showcase/loader.js',
			array(),
			false,
			true
		);
	}

	/**
	 * Insert the Taboola loader snippet after the content (loop_end)
	 */
	public function insert_taboola_footer() {
		// bail if already loaded or coming from infinite scroll
		if ( AdControl::is_infinite_scroll() )
			return;

		echo self::get_taboola_insert();
	}

	/**
	 * Insert the Taboola loader snippet in the content
	 */
	public function insert_taboola_belowpost( $content ) {
		// bail if already loaded or coming from infinite scroll
		if ( ! is_single() && ( ! $this->params->should_show() || AdControl::is_infinite_scroll() ) )
			return $content;

		$content .= self::get_taboola_insert();
		return $content;
	}

	/**
	 * Generates the code to insert based on the page type
	 * @return string Inline Taboola .js for insertion
	 */
	public static function get_taboola_insert() {
		$page_type = is_single() ? 'article' : 'home';
		return '
		<div id="taboola-div"></div>
		<script type="text/javascript">
		//<![CDATA[
			window._taboola = window._taboola || [];
			_taboola.push({' . $page_type . ':"auto"});
			_taboola.push({mode:"grid-3x2", container:"taboola-div"});
		//]]>
		</script>
		';
	}
}
