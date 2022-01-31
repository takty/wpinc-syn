<?php
/**
 * Ajax
 *
 * @package Wpinc Sys
 * @author Takuto Yanagida
 * @version 2022-01-30
 */

namespace wpinc\sys\ajax;

/**
 * Activate AJAX.
 *
 * @param array $args {
 *     Arguments.
 *
 *     @type string 'action'
 *     @type string 'response'
 *     @type bool   'public'   (Optional) Default false.
 *     @type string 'nonce'    (Optional)
 * }
 */
function activate( array $args ): void {
	$args += array(
		'action'   => '',
		'response' => '',
		'public'   => false,
		'nonce'    => '',
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
 * @param array $args  Arguments.
 * @param array $query (Optional) Query arguments.
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
 * @param array $args Arguments.
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
