<?php
/**
 * Sticky for Custom Post Types
 *
 * @package Wpinc Sys
 * @author Takuto Yanagida
 * @version 2022-02-14
 */

namespace wpinc\sys\sticky;

const PMK_STICKY = '_sticky';

/**
 * Disables embedded sticky post function.
 * The embedded sticky post function is only for default 'post' type.
 */
function disable_embedded_sticky(): void {
	if ( is_admin() ) {
		return;
	}
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
		_initialize_hooks();
	}
	array_push( $inst->post_types, ...$pts );
}

/**
 * Initializes hooks.
 */
function _initialize_hooks(): void {
	if ( is_admin() ) {
		add_filter( 'display_post_states', '\wpinc\sys\sticky\_cb_display_post_states', 10, 2 );
		add_action( 'save_post', '\wpinc\sys\sticky\_cb_save_post', 10, 2 );
		add_action(
			'current_screen',  // For using is_block_editor().
			function () {
				global $pagenow;
				if ( 'post-new.php' === $pagenow || 'post.php' === $pagenow ) {
					if ( get_current_screen()->is_block_editor() ) {
						add_action( 'add_meta_boxes', '\wpinc\sys\sticky\_cb_add_meta_boxes', 10, 2 );
					} else {
						add_action( 'post_submitbox_misc_actions', '\wpinc\sys\sticky\_cb_post_submitbox_misc_actions' );
					}
				}
			}
		);
	} else {
		add_filter( 'post_class', '\wpinc\sys\sticky\_cb_post_class', 10, 3 );
	}
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
 * Callback function for 'display_post_states' filter.
 *
 * @access private
 *
 * @param string[] $post_states An array of post display states.
 * @param \WP_Post $post        The current post object.
 * @return string[] The filtered states.
 */
function _cb_display_post_states( array $post_states, \WP_Post $post ): array {
	$inst = _get_instance();
	if ( ! in_array( get_post_type( $post ), $inst->post_types, true ) ) {
		return $post_states;
	}
	$is_sticky = get_post_meta( $post->ID, PMK_STICKY, true );
	if ( $is_sticky ) {
		$post_states['sticky'] = _x( 'Sticky', 'post status' );
	}
	return $post_states;
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

	if ( get_current_screen()->is_block_editor() ) {
		?>
		<label>
			<input type="checkbox" name="_wpinc_sticky" value="1" <?php echo esc_attr( $sticky ? ' checked' : '' ); ?>>
			<span class="checkbox-title"><?php echo esc_html_x( 'Stick this post at the top', 'sticky', 'wpinc_sys' ); ?></span>
		</label>
		<?php
	} else {
		?>
		<div class="misc-pub-section">
			<label style="margin-left:18px;">
				<input type="checkbox" name="_wpinc_sticky" value="1" <?php echo esc_attr( $sticky ? ' checked' : '' ); ?>>
				<span class="checkbox-title"><?php echo esc_html_x( 'Stick this post at the top', 'sticky', 'wpinc_sys' ); ?></span>
			</label>
		</div>
		<?php
	}
}

/**
 * Callback function for 'add_meta_boxes' action.
 * For adding metabox to block editor.
 *
 * @param string   $post_type Post type.
 * @param \WP_Post $post      Post object.
 */
function _cb_add_meta_boxes( string $post_type, \WP_Post $post ): void {
	$inst = _get_instance();
	\add_meta_box(
		'_wpinc_sticky_mb',
		__( 'Sticky' ),
		function ( \WP_Post $post ) {
			_cb_post_submitbox_misc_actions( $post );
		},
		$post_type,
		'side',
		'high'
	);
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
	if (
		! in_array( $post->post_type, $inst->post_types, true ) ||
		! isset( $_POST['_wpinc_sticky_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['_wpinc_sticky_nonce'] ), '_wpinc_sticky' ) ||
		defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE
	) {
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
