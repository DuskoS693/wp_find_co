<?php
/**
 * Plugin Name: Voting plugin
 * Description: Voting Plugin .
 * Plugin URI: http://example.com
 * Version: 0.1
 * Author: Dusko Stanisic
 * Author URI: http://example.com/
 * Text Domain: simple-voting
 */

class Voting {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_plugins_scripts' ) );
		add_action( 'the_content', array( $this, 'add_vote_buttons' ) );
		add_action('init', array($this, 'fa_custom_setup_kit', ));
	}

	public function enqueue_plugins_scripts() {

		/** css ***/

		wp_enqueue_style( 'voting-css', plugin_dir_url( __FILE__ ) .'assets/css/plugin.css' );

		/** js */
		wp_enqueue_script( 'simple-vote-js', plugin_dir_url( __FILE__ ) . 'assets/js/simple_vote.js', array( 'jquery' ), '1.0', true );
		wp_enqueue_script( 'font-awesome-kit', 'https://kit.fontawesome.com/0fc19f2398.js', [], null );
		wp_localize_script( 'simple-vote-js', 'votingData', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'voting-nonce' )
		) );
	}

	public function add_vote_buttons( $content ) {
		if ( is_single() ) {
			global $post;
			$voting_html = '<div class="voting-wrap">
								<div class="button-head">
									Was this article Helpful?
								</div> 
								<div class="voting-action">
									<button class="voting-btn vote-up"><i class="fas fa-smile"></i> Yes</button>
									<button class="voting-btn vote-down"><i class="fas fa-frown"></i> No</button>
								</div>
								</div>';

			$content .= $voting_html;
		}

		return $content;
	}


	function fa_custom_setup_kit($kit_url = '') {
		foreach ( [ 'wp_enqueue_scripts', 'admin_enqueue_scripts', 'login_enqueue_scripts' ] as $action ) {
			add_action(
				$action,
				function () use ( $kit_url ) {
					wp_enqueue_script( 'font-awesome-kit', $kit_url, [], null );
				}
			);
		}
	}



}

new Voting();