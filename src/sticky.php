<?php
/**
 * Sticky for Custom Post Types
 *
 * @package Wpinc Sys
 * @author Takuto Yanagida
 * @version 2023-01-26
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
 * Initialize custom sticky.
 *
 * @param array $args Arguments.
 */
function initialize( array $args = array() ) {
	$args += array(
		'meta_key'   => '_sticky',  // phpcs:ignore
		'post_type'  => array(),
		'label'      => _x( 'Stick this post at the top', 'sticky', 'wpinc_sys' ),
		'post_class' => 'sticky',
		'post_state' => _x( 'Sticky', 'post status' ),
	);

	$inst = _get_instance();
	if ( isset( $inst->settings[ $args['meta_key'] ] ) ) {
		return new \WP_Error( 'registered_meta_key', __( 'The meta key has already been registered.' ) );
	}

	$inst->settings[ $args['meta_key'] ] = $args;  // phpcs:ignore
	if ( ! empty( $args['post_type'] ) ) {
		add_post_type( $args['post_type'], $args['meta_key'] );
	}
}

/**
 * Makes custom post type sticky.
 *
 * @param string|string[] $post_type_s Post types.
 * @param string          $meta_key    Post meta key used for sticky.
 */
function add_post_type( $post_type_s, string $meta_key = PMK_STICKY ) {
	$inst = _get_instance();
	$pts  = is_array( $post_type_s ) ? $post_type_s : array( $post_type_s );

	if ( empty( $inst->settings ) ) {
		initialize();
	}
	$setting = $inst->settings[ $meta_key ] ?? null;
	if ( null === $setting ) {
		return new \WP_Error( 'unknown_meta_key', __( 'The meta key is not registered.' ) );
	}

	foreach ( $pts as $pt ) {
		register( $pt, $meta_key );
	}
	static $initialized = false;
	if ( ! $initialized ) {
		_initialize_hooks();
		$initialized = true;
	}
	array_push( $setting['post_type'], ...$pts );
	$setting['post_type'] = array_values( array_unique( $setting['post_type'] ) );

	$inst->settings[ $meta_key ] = $setting;
}

/**
 * Register a pair of post type and post meta key.
 *
 * @param string $post_type Post type.
 * @param string $meta_key  Post meta key.
 */
function register( string $post_type, string $meta_key ): void {
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
		add_filter( 'display_post_states', '\wpinc\sys\sticky\_cb_display_post_states', 10, 2 );
	} else {
		// For adding 'sticky' to the article classes.
		add_filter( 'post_class', '\wpinc\sys\sticky\_cb_post_class', 10, 3 );
	}

	if ( is_admin() ) {
		add_action(
			'current_screen',  // For using is_block_editor().
			function () {
				global $pagenow;
				if ( 'post-new.php' === $pagenow || 'post.php' === $pagenow ) {
					if ( get_current_screen()->is_block_editor() ) {
						add_action( 'enqueue_block_editor_assets', '\wpinc\sys\sticky\_cb_enqueue_block_editor_assets' );
					} else {
						add_action( 'post_submitbox_misc_actions', '\wpinc\sys\sticky\_cb_post_submitbox_misc_actions' );
						add_action( 'save_post', '\wpinc\sys\sticky\_cb_save_post', 10, 2 );
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
 * @return array Setting.
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
 * @return array Classes.
 */
function _cb_post_class( array $classes, array $class, int $post_id ): array {
	$inst = _get_instance();
	foreach ( $inst->settings as $key => $setting ) {
		if ( in_array( get_post_type( $post_id ), $setting['post_type'], true ) ) {
			$is_sticky = get_post_meta( $post_id, $setting['meta_key'], true );
			if ( $is_sticky ) {
				$classes[] = $setting['post_class'];
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
	foreach ( $inst->settings as $key => $setting ) {
		if ( in_array( get_post_type( $post ), $setting['post_type'], true ) ) {
			$is_sticky = get_post_meta( $post->ID, $setting['meta_key'], true );
			if ( $is_sticky ) {
				$post_states[ $setting['post_class'] ] = $setting['post_state'];
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
	$ss   = _extract_post_type_specific_setting( get_current_screen()->id );
	if ( empty( $ss ) ) {
		return;
	}
	$url_to = untrailingslashit( \wpinc\get_file_uri( __DIR__ ) );
	wp_enqueue_script(
		'wpinc-sticky',
		\wpinc\abs_url( $url_to, './assets/js/sticky.min.js' ),
		array( 'wp-element', 'wp-i18n', 'wp-data', 'wp-components', 'wp-edit-post', 'wp-plugins' ),
		filemtime( __DIR__ . '/assets/js/sticky.min.js' ),
		true
	);
	wp_localize_script(
		'wpinc-sticky',
		'wpinc_sticky',
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
	wp_nonce_field( '_wpinc_sticky', '_wpinc_sticky_nonce' );
	foreach ( $ss as $s ) {
		$sticky = get_post_meta( get_the_ID(), $s['meta_key'], true );
		?>
		<div class="misc-pub-section">
			<label style="margin-left:18px;">
				<input type="checkbox" name="_wpinc<?php echo esc_attr( $s['meta_key'] ); ?>" value="1" <?php echo esc_attr( $sticky ? ' checked' : '' ); ?>>
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
		! isset( $_POST['_wpinc_sticky_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['_wpinc_sticky_nonce'] ), '_wpinc_sticky' ) ||
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
		 * @var array
		 */
		public $settings = array();

		/**
		 * Pairs of post type and post meta key.
		 *
		 * @var array
		 */
		public $pt_pmk = array();
	};
	return $values;
}
