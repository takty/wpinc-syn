<?php
/**
 * Sticky for Custom Post Types
 *
 * @package Wpinc Sys
 * @author Takuto Yanagida
 * @version 2022-02-07
 */

namespace wpinc\sys\sticky;

const PMK_STICKY = '_sticky';

/**
 * Disables embedded sticky post function.
 * The embedded sticky post function is only for default 'post' type.
 */
function disable_embedded_sticky(): void {
	add_action(
		'pre_get_posts',
		function ( $query ) {
			if ( is_admin() || ! $query->is_main_query() ) {
				return;
			}
			$query->set( 'ignore_sticky_posts', '1' );
		}
	);
}


// -----------------------------------------------------------------------------


/**
 * Makes custom post type sticky.
 *
 * @param string|string[] $post_type_s Post types.
 */
function add_post_type( $post_type_s ): void {
	$inst = _get_instance();
	$pts  = is_array( $post_type_s ) ? $post_type_s : array( $post_type_s );

	if ( empty( $inst->post_types ) ) {
		add_filter( 'post_class', '\wpinc\sys\sticky\_cb_post_class', 10, 3 );
		add_action( 'post_submitbox_misc_actions', '\wpinc\sys\sticky\_cb_post_submitbox_misc_actions' );
		add_action( 'save_post', '\wpinc\sys\sticky\_cb_save_post', 10, 2 );
	}
	array_push( $inst->post_types, ...$pts );
}

/**
 * Callback function for 'post_class' filter.
 *
 * @access private
 *
 * @param string[] $classes An array of post class names.
 * @param string[] $class   An array of additional class names added to the post.
 * @param int      $post_id The post ID.
 * @return array Classes.
 */
function _cb_post_class( array $classes, array $class, int $post_id ): array {
	$inst = _get_instance();
	if ( is_admin() ) {
		return $classes;
	}
	if ( ! in_array( get_post_type( $post_id ), $inst->post_types, true ) ) {
		return $classes;
	}
	$is_sticky = get_post_meta( $post_id, PMK_STICKY, true );
	if ( $is_sticky ) {
		$classes[] = 'sticky';
	}
	return $classes;
}

/**
 * Callback function for 'post_submitbox_misc_actions' action.
 *
 * @access private
 *
 * @param \WP_Post $post WP_Post object for the current post.
 */
function _cb_post_submitbox_misc_actions( \WP_Post $post ): void {
	$inst = _get_instance();
	if ( ! in_array( $post->post_type, $inst->post_types, true ) ) {
		return;
	}
	wp_nonce_field( '_wpinc_sticky', '_wpinc_sticky_nonce' );
	$sticky = get_post_meta( get_the_ID(), PMK_STICKY, true );
	?>
	<div class="misc-pub-section">
		<label style="margin-left:18px;">
			<input type="checkbox" name="_wpinc_sticky" value="1" <?php echo esc_attr( $sticky ? ' checked' : '' ); ?>>
			<span class="checkbox-title"><?php echo esc_html_x( 'Stick this post at the top', 'sticky', 'wpinc_sys' ); ?></span>
		</label>
	</div>
	<?php
}

/**
 * Callback function for 'save_post' action.
 *
 * @access private
 *
 * @param int      $post_id Post ID.
 * @param \WP_Post $post    WP_Post object for the current post.
 */
function _cb_save_post( int $post_id, \WP_Post $post ): void {
	$inst = _get_instance();
	if ( ! in_array( $post->post_type, $inst->post_types, true ) ) {
		return;
	}
	if ( ! isset( $_POST['_wpinc_sticky_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( $_POST['_wpinc_sticky_nonce'] ), '_wpinc_sticky' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( isset( $_POST['_wpinc_sticky'] ) ) {
		update_post_meta( $post_id, PMK_STICKY, '1' );
	} else {
		delete_post_meta( $post_id, PMK_STICKY );
	}
}


// -----------------------------------------------------------------------------


/**
 * Gets instance.
 *
 * @access private
 *
 * @return object Instance.
 */
function _get_instance(): object {
	static $values = null;
	if ( $values ) {
		return $values;
	}
	$values = new class() {
		/**
		 * The target post types.
		 *
		 * @var array
		 */
		public $post_types = array();
	};
	return $values;
}
