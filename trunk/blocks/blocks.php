<?php
/**
 * Simple integration of Gutenberg Editor Block(s)
 *
 * @see https://www.ibenic.com/integrating-gutenberg-blocks-in-plugins/
 *
 **/

/**
 * Class Online_Status_InSL_Blocks
 */
class Online_Status_InSL_Blocks {

	/**
	 * Online_Status_InSL_Blocks constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'run' ) );
	}

	/**
	 * Run it and load Gutenberg blocks.
	 */
	public function run() {
		if ( ! function_exists( 'is_gutenberg_page' ) ) {
			return;
		}

		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register the Blocks.
	 */
	public function register_blocks() {

		wp_register_script(
			'online-status-insl-gutenberg',
			plugins_url( 'assets/dist/js/gutenberg.js', ONLINE_STATUS_INSL_MAIN_FILE ),
			array( 'wp-blocks', 'wp-element' )
		);

		register_block_type( 'online-status-insl/online-status-insl-block', array(
			'editor_script'   => 'online-status-insl-gutenberg'
		) );
	}
}

new Online_Status_InSL_Blocks();