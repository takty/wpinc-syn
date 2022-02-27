<?php
/**
 * IP Restriction (IPv4)
 *
 * @package Wpinc Sys
 * @author Takuto Yanagida
 * @version 2022-02-27
 */

namespace wpinc\sys\ip_restriction;

const PMK_IP_RESTRICTION = '_ip_restriction';

/**
 * Adds CIDR.
 *
 * @param string $cidr Allowed CIDR.
 * @param string $cls  CSS class.
 */
function add_allowed_cidr( string $cidr, string $cls = '' ): void {
	$inst = _get_instance();

	$inst->whites[] = array(
		'cidr' => $cidr,
		'cls'  => $cls,
	);
}

/**
 * Adds post type as IP restricted.
 *
 * @param string|string[] $post_type_s Post types.
 */
function add_post_type( $post_type_s ): void {
	$inst = _get_instance();
	$pts  = is_array( $post_type_s ) ? $post_type_s : array( $post_type_s );

	foreach ( $pts as $pt ) {
		register_post_meta(
			$pt,
			PMK_IP_RESTRICTION,
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
	if ( empty( $inst->post_types ) ) {
		_initialize_hooks();
	}
	array_push( $inst->post_types, ...$pts );
}

/**
 * Check whether the current IP is allowed.
 *
 * @return bool True if the IP is OK.
 */
function is_allowed(): bool {
	$inst = _get_instance();

	static $checked = 0;
	if ( $checked++ ) {
		return $inst->is_allowed;
	}
	$inst = _get_instance();
	$ip   = $_SERVER['REMOTE_ADDR'];  // phpcs:ignore

	$inst->is_allowed = false;
	foreach ( $inst->whites as $w ) {
		if ( _in_cidr( $ip, $w['cidr'] ) ) {
			$inst->is_allowed = true;
			if ( ! empty( $w['cls'] ) ) {
				$inst->current_body_classes[] = $w['cls'];
			}
		}
	}
	return $inst->is_allowed;
}

/**
 * Check whether the current query is restricted and the current IP is not allowed.
 *
 * @return bool True if the current query is restricted and the current IP is not allowed.
 */
function is_restricted(): bool {
	$inst = _get_instance();
	return $inst->is_restricted;
}

/**
 * Initializes hooks.
 *
 * @access private
 */
function _initialize_hooks(): void {
	if ( is_admin() ) {
		// For indication in post lists.
		add_filter( 'display_post_states', '\wpinc\sys\ip_restriction\_cb_display_post_states', 10, 2 );
	} else {
		add_action( 'pre_get_posts', '\wpinc\sys\ip_restriction\_cb_pre_get_posts' );
		add_filter( 'body_class', '\wpinc\sys\ip_restriction\_cb_body_class' );
	}

	if ( is_admin() ) {
		add_action(
			'current_screen',  // For using is_block_editor().
			function () {
				global $pagenow;
				if ( 'post-new.php' === $pagenow || 'post.php' === $pagenow ) {
					if ( get_current_screen()->is_block_editor() ) {
						add_action( 'enqueue_block_editor_assets', '\wpinc\sys\ip_restriction\_cb_enqueue_block_editor_assets' );
					} else {
						add_action( 'post_submitbox_misc_actions', '\wpinc\sys\ip_restriction\_cb_post_submitbox_misc_actions' );
						add_action( 'save_post', '\wpinc\sys\ip_restriction\_cb_save_post', 10, 2 );
					}
				}
			}
		);
	}
}


// -----------------------------------------------------------------------------


/**
 * Check IP.
 *
 * @access private
 *
 * @param string $ip   Current IP.
 * @param string $cidr CIDR.
 * @return bool True if the IP matches.
 */
function _in_cidr( string $ip, string $cidr ): bool {
	list( $network, $mask_bit_len ) = explode( '/', $cidr );

	$host   = 32 - $mask_bit_len;
	$net    = ip2long( $network ) >> $host << $host;
	$ip_net = ip2long( $ip ) >> $host << $host;
	return $net === $ip_net;
}


// -----------------------------------------------------------------------------


/**
 * Callback function for 'pre_get_posts' action.
 *
 * @access private
 *
 * @param \WP_Query $query The WP_Query instance (passed by reference).
 */
function _cb_pre_get_posts( \WP_Query $query ): void {
	static $bypass = false;
	if ( $bypass ) {
		return;
	}
	if ( is_user_logged_in() || is_allowed() ) {
		return;
	}
	$inst        = _get_instance();
	$post_type_s = $query->get( 'post_type', array() );
	$pts         = is_array( $post_type_s ) ? $post_type_s : array( $post_type_s );

	if ( ! empty( $pts ) && empty( array_intersect( $pts, $inst->post_types ) ) ) {
		return;
	}
	$bypass = true;
	$ex_ps  = get_posts(
		array(
			'post_type'      => empty( $pts ) ? $inst->post_types : $pts,
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'meta_query'     => array(  // phpcs:ignore
				array(
					'key'     => PMK_IP_RESTRICTION,
					'compare' => '=',
					'value'   => '1',
				),
			),
		)
	);
	$bypass = false;

	if ( ! empty( $ex_ps ) ) {
		$query->set( 'post__not_in', $ex_ps );
		$inst->is_restricted = true;

		$p = $query->get( 'p' );
		if ( in_array( $p, $ex_ps, true ) ) {
			$query->set_404();
		}
	}
}

/**
 * Callback function for 'body_class' filter.
 *
 * @access private
 *
 * @param string[] $classes Classes.
 */
function _cb_body_class( array $classes ) {
	$inst = _get_instance();
	if ( is_allowed() ) {
		array_push( $classes, ...$inst->current_body_classes );
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
	$is_sticky = get_post_meta( $post->ID, PMK_IP_RESTRICTION, true );
	if ( $is_sticky ) {
		$post_states['ip_restriction'] = _x( 'IP Restriction', 'ip restriction', 'wpinc_sys' );
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
	if ( in_array( get_current_screen()->id, $inst->post_types, true ) ) {
		$url_to = untrailingslashit( \wpinc\get_file_uri( __DIR__ ) );
		wp_enqueue_script(
			'wpinc-ip-restriction',
			\wpinc\abs_url( $url_to, './assets/js/ip-restriction.min.js' ),
			array( 'wp-element', 'wp-i18n', 'wp-data', 'wp-components', 'wp-edit-post', 'wp-plugins' ),
			filemtime( __DIR__ . '/assets/js/ip-restriction.min.js' ),
			true
		);
		wp_localize_script(
			'wpinc-ip-restriction',
			'wpinc_ip_restriction',
			array(
				'meta_keys' => array( PMK_IP_RESTRICTION ),
				'labels'    => array( _x( 'IP Restriction', 'ip restriction', 'wpinc_sys' ) ),
			)
		);
	}
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
	if ( ! in_array( $post->post_type, $inst->post_types, true ) ) {
		return;
	}
	wp_nonce_field( '_wpinc_ip_restriction', '_wpinc_ip_restriction_nonce' );
	$is_restricted = get_post_meta( get_the_ID(), PMK_IP_RESTRICTION, true );
	?>
	<div class="misc-pub-section">
		<label style="margin-left:18px;">
			<input type="checkbox" name="_wpinc_ip_restriction" value="1" <?php echo esc_attr( $is_restricted ? ' checked' : '' ); ?>>
			<span class="checkbox-title"><?php echo esc_html_x( 'IP Restriction', 'ip restriction', 'wpinc_sys' ); ?></span>
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
	if (
		! in_array( $post->post_type, $inst->post_types, true ) ||
		! isset( $_POST['_wpinc_ip_restriction_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['_wpinc_ip_restriction_nonce'] ), '_wpinc_ip_restriction' ) ||
		defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE
	) {
		return;
	}
	if ( isset( $_POST['_wpinc_ip_restriction'] ) ) {
		update_post_meta( $post_id, PMK_IP_RESTRICTION, '1' );
	} else {
		delete_post_meta( $post_id, PMK_IP_RESTRICTION );
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
		 * White list of allowed IPs.
		 *
		 * @var array
		 */
		public $whites = array();

		/**
		 * The target post types.
		 *
		 * @var array
		 */
		public $post_types = array();

		/**
		 * CSS classes to be added.
		 *
		 * @var array
		 */
		public $current_body_classes = array();

		/**
		 * Whether the current post is allowed to be shown.
		 *
		 * @var bool|null
		 */
		public $is_allowed = false;

		/**
		 * Whether the current post is IP restricted.
		 *
		 * @var bool
		 */
		public $is_restricted = false;
	};
	return $values;
}
