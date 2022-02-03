<?php
/**
 * Miscellaneous
 *
 * @package Wpinc Sys
 * @author Takuto Yanagida
 * @version 2022-02-03
 */

namespace wpinc\sys;

/**
 * Checks current post type.
 *
 * @param string $post_type Post type.
 * @return bool True if the current post type is $post_type.
 */
function is_post_type( string $post_type ): bool {
	$id_g = $_GET['post'] ?? null;  // phpcs:ignore
	$id_p = $_POST['post_ID'] ?? null;  // phpcs:ignore
	if ( ! $id_g && ! $id_p ) {
		return false;
	}
	$p = get_post( intval( $id_g ? $id_g : $id_p ) );
	if ( $p ) {
		$pt = $p->post_type;
	} else {
		$pt = $_GET['post_type'] ?? '';  // phpcs:ignore
	}
	return $post_type === $pt;
}

/**
 * Retrieves post type title.
 */
function get_post_type_title() {
	$post_type = get_query_var( 'post_type' );
	if ( is_array( $post_type ) ) {
		$post_type = reset( $post_type );
	}
	$post_type_obj = get_post_type_object( $post_type );
	return apply_filters( 'post_type_archive_title', $post_type_obj->labels->name, $post_type );
}


// -----------------------------------------------------------------------------


/**
 * Gets current URL.
 *
 * @return string Current URL.
 */
function get_current_url(): string {
	// phpcs:disable
	$host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'];
	return ( is_ssl() ? 'https://' : 'http://' ) . $host . $_SERVER['REQUEST_URI'];
	// phpcs:enable
}

/**
 * Serializes URL components.
 *
 * @param array $cs URL components.
 * @return string URL.
 */
function serialize_url( array $cs ): string {
	// phpcs:disable
	$scheme = isset( $cs['scheme'] )   ? "{$cs['scheme']}://" : '';
	$host   = isset( $cs['host'] )     ? $cs['host']          : '';
	$port   = isset( $cs['port'] )     ? ":{$cs['port']}"     : '';
	$user   = isset( $cs['user'] )     ? $cs['user']          : '';
	$pass   = isset( $cs['pass'] )     ? ":{$cs['pass']}"     : '';
	$pass   = ( $user || $pass )       ? "$pass@"             : '';
	$path   = isset( $cs['path'] )     ? $cs['path']          : '';
	$query  = isset( $cs['query'] )    ? "?{$cs['query']}"    : '';
	$frag   = isset( $cs['fragment'] ) ? "#{$cs['fragment']}" : '';
	return "$scheme$user$pass$host$port$path$query$frag";
	// phpcs:enable
}
