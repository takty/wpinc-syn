<?php
/**
 * Sticky for Custom Post Types
 *
 * @package Wpinc Sys
 * @author Takuto Yanagida
 * @version 2023-08-31
 */

namespace wpinc\sys\sticky;

require_once __DIR__ . '/post-status.php';

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
 * @param array<string, mixed> $deprecated Deprecated.
 */
function initialize( array $deprecated = array() ): void {
	if ( ! empty( $deprecated ) && WP_DEBUG ) {
		trigger_error( 'Use function \'\\wpinc\\sys\\post_status\\initialize\' instead.', E_USER_DEPRECATED );  // phpcs:ignore
	}
	$args = array(
		'meta_key'   => PMK_STICKY,  // phpcs:ignore
		'label'      => _x( 'Stick this post at the top', 'sticky', 'wpinc_sys' ),
		'post_state' => _x( 'Sticky', 'post status' ),
		'post_class' => 'sticky',
	);
	\wpinc\sys\post_status\initialize( $args + $deprecated );
}

/**
 * Makes custom post type sticky.
 *
 * @param string|string[] $post_type_s Post types.
 * @param string          $meta_key    Post meta key used for sticky.
 */
function add_post_type( $post_type_s, string $meta_key = PMK_STICKY ): void {
	if ( PMK_STICKY !== $meta_key && WP_DEBUG ) {
		trigger_error( 'Use function \'\\wpinc\\sys\\post_status\\add_post_type\' instead.', E_USER_DEPRECATED );  // phpcs:ignore
	}
	if ( PMK_STICKY === $meta_key && ! \wpinc\sys\post_status\is_initialized( PMK_STICKY ) ) {
		initialize();
	}
	\wpinc\sys\post_status\add_post_type( $post_type_s, $meta_key );
}
