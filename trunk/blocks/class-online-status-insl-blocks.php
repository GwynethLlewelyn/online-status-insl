<?php
/**
 * Simple integration of Gutenberg Editor Block(s)
 *
 * @see https://www.ibenic.com/integrating-gutenberg-blocks-in-plugins/
 * @category OnlineStatusInSL
 * @package  OnlineStatusInSL
 * @author   Gwyneth Llewelyn <gwyneth.llewelyn@gwynethllewelyn.net>
 * @license  https://directory.fsf.org/wiki/License:BSD-3-Clause BSD 3-Clause "New" or "Revised" License
 * @version  1.6.2
 * @link     https://gwynethllewelyn.net/online-status-insl/
 **/

// Do not allow direct access to this file.
if ( ! defined( 'WPINC' ) ) {
	die( 'Direct script access denied.' ); // no translation, because `__()` may not be available...
}

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
	 *
	 * @return void
	 */
	public function run() {
		if ( ! function_exists( 'is_gutenberg_page' ) ) {
			return;
		}

		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register the Blocks.
	 *
	 * @return void
	 */
	public function register_blocks() {
		wp_register_script(
			'online-status-insl-gutenberg',
			plugins_url( 'assets/dist/js/gutenberg.js', ONLINE_STATUS_INSL_MAIN_FILE ),
			array( 'wp-blocks', 'wp-element' ),
			'1.6.0',
			true
		);

		register_block_type(
			'online-status-insl/online-status-insl-block',
			array(
				'editor_script' => 'online-status-insl-gutenberg',
			)
		);
	}
}
new Online_Status_InSL_Blocks();
