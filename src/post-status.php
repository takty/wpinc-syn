<?php
/**
 * Custom Post Status
 *
 * @package Wpinc Sys
 * @author Takuto Yanagida
 * @version 2023-09-01
 */

namespace wpinc\sys\post_status;

/**
 * Initialize custom post status.
 *
 * @param array<string, mixed> $args Arguments.
 * @return true|\WP_Error Error if an error occurred.
 */
function initialize( array $args = array() ) {
	$args += array(
		'meta_key'   => '',  // phpcs:ignore
		'label'      => '',
		'post_class' => null,
		'post_state' => null,
		'post_type'  => array(),
	);
	if ( empty( $args['meta_key'] ) ) {
		return new \WP_Error( 'empty_meta_key', __( 'The meta key is empty.' ) );
	}
	if ( empty( $args['label'] ) ) {
		return new \WP_Error( 'empty_label', __( 'The label is empty.' ) );
	}

	$inst = _get_instance();
	if ( isset( $inst->settings[ $args['meta_key'] ] ) ) {
		return new \WP_Error( 'registered_meta_key', __( 'The meta key has already been registered.' ) );
	}

	$inst->settings[ $args['meta_key'] ] = $args;  // phpcs:ignore
	if ( ! empty( $args['post_type'] ) ) {
		add_post_type( $args['post_type'], $args['meta_key'] );
	}
	return true;
}

/**
 * Returns whether the meta key is initialized.
 *
 * @param string $meta_key Post meta key.
 * @return bool True when initialized.
 */
function is_initialized( string $meta_key ): bool {
	$inst = _get_instance();
	return isset( $inst->settings[ $meta_key ] );
}

/**
 * Add a post type for custom post status.
 *
 * @param string|string[] $post_type_s Post types.
 * @param string          $meta_key    Post meta key.
 * @return true|\WP_Error Error if an error occurred.
 */
function add_post_type( $post_type_s, string $meta_key ) {
	$inst = _get_instance();
	$pts  = (array) $post_type_s;

	$s = $inst->settings[ $meta_key ] ?? null;
	if ( null === $s ) {
		return new \WP_Error( 'unknown_meta_key', __( 'The meta key is not initialized.' ) );
	}

	foreach ( $pts as $pt ) {
		_register( $pt, $meta_key );
	}
	static $initialized = false;
	if ( ! $initialized ) {
		_initialize_hooks();
		$initialized = true;
	}
	if ( ! empty( $pts ) ) {
		array_push( $s['post_type'], ...$pts );
	}
	$s['post_type'] = array_values( array_unique( $s['post_type'] ) );

	$inst->settings[ $meta_key ] = $s;
	return true;
}

/**
 * Register a pair of post type and post meta key.
 *
 * @access private
 *
 * @param string $post_type Post type.
 * @param string $meta_key  Post meta key.
 */
function _register( string $post_type, string $meta_key ): void {
	$inst = _get_instance();
	$pair = "$post_type:$meta_key";
	if ( isset( $inst->pt_pmk[ $pair ] ) ) {
		return;
	}
	$inst->pt_pmk[ $pair ] = true;

	register_post_meta(
		$post_type,
		$meta_key,
		array(
			'type'          => 'boolean',
			'default'       => false,
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => function() {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}

/**
 * Initializes hooks.
 *
 * @access private
 */
function _initialize_hooks(): void {
	if ( is_admin() ) {
		// For indication in post lists.
		add_filter( 'display_post_states', '\wpinc\sys\post_status\_cb_display_post_states', 10, 2 );
	} else {
		// For adding post classes.
		add_filter( 'post_class', '\wpinc\sys\post_status\_cb_post_class', 10, 3 );
	}

	if ( is_admin() ) {
		add_action(
			'current_screen',  // For using is_block_editor().
			function () {
				global $pagenow;
				if ( 'post-new.php' === $pagenow || 'post.php' === $pagenow ) {
					$cs = get_current_screen();
					if ( $cs && $cs->is_block_editor() ) {
						add_action( 'enqueue_block_editor_assets', '\wpinc\sys\post_status\_cb_enqueue_block_editor_assets' );
					} else {
						add_action( 'post_submitbox_misc_actions', '\wpinc\sys\post_status\_cb_post_submitbox_misc_actions' );
						add_action( 'save_post', '\wpinc\sys\post_status\_cb_save_post', 10, 2 );
					}
				}
			}
		);
	}
}

/**
 * Extracts post type specific setting.
 *
 * @access private
 *
 * @param string $post_type Post type.
 * @return array<string, mixed> Setting.
 */
function _extract_post_type_specific_setting( string $post_type ): array {
	$inst = _get_instance();
	return array_filter(
		$inst->settings,
		function ( $s ) use ( $post_type ) {
			return in_array( $post_type, $s['post_type'], true );
		}
	);
}


// -----------------------------------------------------------------------------


/**
 * Callback function for 'post_class' filter.
 *
 * @access private
 *
 * @param string[] $classes An array of post class names.
 * @param string[] $class   An array of additional class names added to the post.
 * @param int      $post_id The post ID.
 * @return string[] Classes.
 */
function _cb_post_class( array $classes, array $class, int $post_id ): array {
	$inst = _get_instance();
	foreach ( $inst->settings as $meta_key => $s ) {
		if ( empty( $s['post_class'] ) ) {
			continue;
		}
		if ( in_array( get_post_type( $post_id ), $s['post_type'], true ) ) {
			$val = get_post_meta( $post_id, $meta_key, true );
			if ( $val ) {
				$classes[] = $s['post_class'];
			}
		}
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
	foreach ( $inst->settings as $meta_key => $s ) {
		if ( empty( $s['post_state'] ) ) {
			continue;
		}
		if ( in_array( get_post_type( $post->ID ), $s['post_type'], true ) ) {
			$val = get_post_meta( $post->ID, $meta_key, true );
			if ( $val ) {
				$post_states[ $meta_key ] = $s['post_state'];  // phpcs:ignore
			}
		}
	}
	return $post_states;
}


// ---------------------------------------- Callback Functions for Block Editor.


/**
 * Callback function for 'enqueue_block_editor_assets' action.
 *
 * @access private
 */
function _cb_enqueue_block_editor_assets(): void {
	$inst = _get_instance();
	$cs   = get_current_screen();
	$ss   = $cs ? _extract_post_type_specific_setting( $cs->id ) : array();
	if ( empty( $ss ) ) {
		return;
	}
	$url_to = untrailingslashit( \wpinc\get_file_uri( __DIR__ ) );
	wp_enqueue_script(
		'wpinc-post-status',
		\wpinc\abs_url( $url_to, './assets/js/post-status.min.js' ),
		array( 'wp-element', 'wp-i18n', 'wp-data', 'wp-components', 'wp-edit-post', 'wp-plugins' ),
		(string) filemtime( __DIR__ . '/assets/js/post-status.min.js' ),
		true
	);
	wp_localize_script(
		'wpinc-post-status',
		'wpinc_post_status',
		array(
			'meta_keys' => array_column( $ss, 'meta_key' ),
			'labels'    => array_column( $ss, 'label' ),
		)
	);
}


// -------------------------------------- Callback Functions for Classic Editor.


/**
 * Callback function for 'post_submitbox_misc_actions' action.
 *
 * @access private
 *
 * @param \WP_Post $post WP_Post object for the current post.
 */
function _cb_post_submitbox_misc_actions( \WP_Post $post ): void {
	$inst = _get_instance();
	$ss   = _extract_post_type_specific_setting( $post->post_type );
	if ( empty( $ss ) ) {
		return;
	}
	wp_nonce_field( '_wpinc_post_status', '_wpinc_post_status_nonce' );
	foreach ( $ss as $s ) {
		$val = get_post_meta( $post->ID, $s['meta_key'], true );
		?>
		<div class="misc-pub-section">
			<label style="margin-left:18px;">
				<input type="checkbox" name="_wpinc<?php echo esc_attr( $s['meta_key'] ); ?>" value="1" <?php echo esc_attr( $val ? ' checked' : '' ); ?>>
				<span class="checkbox-title"><?php echo esc_html( $s['label'] ); ?></span>
			</label>
		</div>
		<?php
	}
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
	$ss   = _extract_post_type_specific_setting( $post->post_type );
	if ( empty( $ss ) ) {
		return;
	}
	if (
		! isset( $_POST['_wpinc_post_status_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['_wpinc_post_status_nonce'] ), '_wpinc_post_status' ) ||
		defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE
	) {
		return;
	}
	foreach ( $ss as $s ) {
		if ( isset( $_POST[ "_wpinc{$s['meta_key']}" ] ) ) {
			update_post_meta( $post_id, $s['meta_key'], '1' );
		} else {
			delete_post_meta( $post_id, $s['meta_key'] );
		}
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
		 * Settings.
		 *
		 * @var array<string, array<string, mixed>>
		 */
		public $settings = array();

		/**
		 * Pairs of post type and post meta key.
		 *
		 * @var array<string, string>
		 */
		public $pt_pmk = array();
	};
	return $values;
}
