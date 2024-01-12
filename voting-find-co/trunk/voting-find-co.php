<?php
/**
 * Plugin Name: Was this article helpful?
 * Description: Simple voting plugin
 * Plugin URI: http://example.com
 * Version: 1.0
 * Author: Dusko Stanisic
 * Author URI: http://example.com/
 * Text Domain: simple-voting
 */

class Voting {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_plugins_scripts' ) );
		add_action( 'the_content', array( $this, 'add_vote_buttons' ) );
		add_action( 'wp_ajax_save_vote', array( $this, 'save_vote' ) );
		add_action( 'wp_ajax_nopriv_save_vote', array( $this, 'save_vote' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_voting_meta_box' ) );
	}


	/**
     * Enqueue plugin's scripts and styles
	 * @return void
	 */
	public function enqueue_plugins_scripts() {
		/** css ***/
		wp_enqueue_style( 'voting-css', plugin_dir_url( __FILE__ ) . 'assets/css/plugin.css' );

		/** js */
		wp_enqueue_script( 'simple-vote-js', plugin_dir_url( __FILE__ ) . 'assets/js/simple_vote.js', array( 'jquery' ), '1.0', true );
		wp_enqueue_script( 'font-awesome-kit', 'https://kit.fontawesome.com/0fc19f2398.js', [], null );
		wp_localize_script( 'simple-vote-js', 'votingData', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'voting-nonce' )
		) );
	}

	/**
	 * Get current user's IP address
	 * @return mixed
	 */
	public function get_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			//check ip from share internet
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			//to check ip is pass from proxy
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return $ip;
	}


	/**
     * Render vote buttons HTML markup
	 * @param $content
	 *
	 * @return mixed|string
	 */
	public function add_vote_buttons( $content ) {
		if ( is_single() ) {
			global $post;

			$user_ip           = $this->get_ip();
			$user_id           = $this->morph_ip_addr_to_int( $user_ip );
			$has_already_voted = get_user_meta( $user_id, 'has_voted_' . $post->ID, true );

			$voting_html = '<div class="voting-wrap">
								<div class="button-head">
									Was this article Helpful?
								</div>' .
			               '<div class="voting-action">
									<button class="voting-btn vote-up" data-post-id="' . esc_attr( $post->ID ) . '"><i class="fas fa-smile"></i> <span>Yes</span></button>
									<button class="voting-btn vote-down" data-post-id="' . esc_attr( $post->ID ) . '"><i class="fas fa-frown"></i> <span>No</span></button>
								</div>
								
								</div>';

			if ( $has_already_voted ) {
				$vote_type      = get_user_meta( $user_id, 'vote_type_' . $post->ID, true );
				$positive_class = $vote_type == 'positive' ? 'active' : "";
				$negative_class = $vote_type == 'negative' ? 'active' : "";
				$votes          = $this->get_calculated_votes( $post->ID );
				$voting_html    = '<div class="voting-wrap">
								<div class="button-head">
									Thank you for your feedback
								</div>' .
				                  '<div class="voting-action">
									<button class="voting-btn vote-up ' . esc_attr( $positive_class ) . '" data-post-id="' . esc_attr( $post->ID ) . '" disabled><i class="fas fa-smile"></i> <span>' . esc_html( $votes['positive_percentage'] ) . ' %</span></button>
									<button class="voting-btn vote-down ' . esc_attr( $negative_class ) . '" data-post-id="' . esc_attr( $post->ID ) . '" disabled><i class="fas fa-frown"></i> <span>' . esc_html( $votes['negative_percentage'] ) . ' %</span></button>
								</div>
								
								</div>';
			}

			$content .= $voting_html;
		}

		return $content;
	}


	/**
     * Check if user has already voted
	 * @param $user_id
	 * @param $post_id
	 *
	 * @return bool
	 */
	public function has_already_voted( $user_id, $post_id ) {

		return get_user_meta( $user_id, 'has_voted_' . $post_id, true );
	}


	/**
     * Save vote in db
	 * @return void
	 */
	public function save_vote() {

		check_ajax_referer( 'voting-nonce', 'security' );
		$user_ip   = $this->get_ip();
		$post_id   = absint( $_POST['post_id'] );
		$vote_type = sanitize_text_field( $_POST['vote_type'] );

		// Morph user ip address so we can use it as id
		$user_id = $this->morph_ip_addr_to_int( $user_ip );
        //check if user has already voted
		$has_voted = $this->has_already_voted( $user_id, $post_id );

		if ( ! $has_voted ) {
			$this->update_article_meta( $vote_type, $post_id );
			update_user_meta( $user_id, 'has_voted_' . $post_id, true );
			update_user_meta( $user_id, 'vote_type_' . $post_id, $vote_type );
			$votes = $this->get_calculated_votes( $post_id );

			echo json_encode( $votes );
		}

		wp_die();
	}


	/**
     * Render voting metabox
	 * @return void
	 */
	public function add_voting_meta_box() {
		add_meta_box( 'voting_results', 'Voting Results', array(
			$this,
			'voting_results_meta_box'
		), 'post', 'side', 'high' );
	}


	/**
     * Metabox callback
	 * @param $post
	 *
	 * @return void
	 */
	public function voting_results_meta_box( $post ) {
		$votes = $this->get_calculated_votes( $post->ID );

		?>
        <p><b>Positive:</b> <?php echo esc_html( $votes['positive_votes'] ); ?>
            (<?php echo esc_html( $votes['positive_percentage'] ); ?>%)</p>
        <p><b>Negative:</b> <?php echo esc_html( $votes['negative_votes'] ); ?>
            (<?php echo esc_html( $votes['negative_percentage'] ); ?>%)</p>
		<?php
	}


	/**
     * Update voting data
	 * @param $vote_type
	 * @param $post_id
	 *
	 * @return void
	 */
	public function update_article_meta( $vote_type, $post_id ) {
		$positive_count = get_post_meta( $post_id, 'positive_count', true );
		$negative_count = get_post_meta( $post_id, 'negative_count', true );

		if ( $vote_type === 'positive' ) {
			$positive_count ++;
			update_post_meta( $post_id, 'positive_count', $positive_count );
		} elseif ( $vote_type === 'negative' ) {
			$negative_count ++;
			update_post_meta( $post_id, 'negative_count', $negative_count );
		}
	}


	/**
     * Calculate voting results
	 * @param $post_id
	 *
	 * @return array
	 */
	public function get_calculated_votes( $post_id ) {
		$positive_votes = get_post_meta( $post_id, 'positive_count', true );
		$negative_votes = get_post_meta( $post_id, 'negative_count', true );

		$vote_sum            = (int) $positive_votes + (int) $negative_votes;
		$positive_percentage = $vote_sum > 0 ? round( ( $positive_votes / $vote_sum ) * 100, 0 ) : 0;
		$negative_percentage = 100 - $positive_percentage;

		return [
			'positive_votes'      => $positive_votes,
			'negative_votes'      => $negative_votes,
			'positive_percentage' => $positive_percentage,
			'negative_percentage' => $negative_percentage,
		];
	}


	/**
     * Get users IP address
	 * @param $ip_address
	 *
	 * @return array|string|string[]
	 */
	public function morph_ip_addr_to_int( $ip_address ) {

		return str_replace( '.', '', $ip_address );
	}
}

new Voting();