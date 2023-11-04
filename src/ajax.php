<?php
/**
 * Ajax
 *
 * @package Wpinc Sys
 * @author Takuto Yanagida
 * @version 2023-11-04
 */

declare(strict_types=1);

namespace wpinc\sys\ajax;

/** phpcs:ignore
 * Activate AJAX.
 *
 * phpcs:ignore
 * @param array{
 *     action  : string,
 *     response: callable,
 *     public? : bool,
 *     nonce?  : string,
 * } $args Arguments.
 * $args {
 *     Arguments.
 *
 *     @type string   'action'
 *     @type callable 'response'
 *     @type bool     'public'   (Optional) Default false.
 *     @type string   'nonce'    (Optional)
 * }
 */
function activate( array $args ): void {
	$args += array(
		'public' => false,
		'nonce'  => '',
	);
	if ( empty( $args['nonce'] ) ) {
		$args['nonce'] = $args['action'];
	}

	if ( ! preg_match( '/^[a-zA-Z0-9_\-]+$/', $args['action'] ) ) {
		wp_die( 'Invalid string for \$action.' );
	}

	add_action(
		"wp_ajax_{$args['action']}",
		function () use ( $args ) {
			_cb_wp_ajax__( $args );
		}
	);
	if ( $args['public'] ) {
		add_action(
			"wp_ajax_nopriv_{$args['action']}",
			function () use ( $args ) {
				_cb_wp_ajax__( $args );
			}
		);
	}
}

/**
 * Gets AJAX URL.
 *
 * @param array{ action: string, nonce: string } $args  Arguments.
 * @param array<string, mixed>                   $query (Optional) Query arguments.
 * @return string AJAX URL.
 */
function get_url( array $args, array $query = array() ): string {
	$query['action'] = $args['action'];
	$query['nonce']  = wp_create_nonce( $args['nonce'] );

	$url = admin_url( 'admin-ajax.php' );
	foreach ( $query as $key => $val ) {
		$url = add_query_arg( $key, $val, $url );
	}
	return $url;
}

/**
 * Callback function for 'wp_ajax_{$action}' and 'wp_ajax_nopriv_{$action}' actions.
 *
 * @param array{ nonce: string, response: callable } $args Arguments.
 */
function _cb_wp_ajax__( array $args ): void {
	check_ajax_referer( $args['nonce'], 'nonce' );
	nocache_headers();

	$res = call_user_func( $args['response'] );
	if ( is_array( $res ) ) {
		wp_send_json( $res );
	} else {
		echo $res;  // phpcs:ignore
		die;
	}
}
