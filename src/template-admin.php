<?php
/**
 * Template Admin
 *
 * @package Wpinc Sys
 * @author Takuto Yanagida
 * @version 2024-03-14
 */

declare(strict_types=1);

namespace wpinc\sys\template_admin;

require_once __DIR__ . '/assets/admin-current-post.php';

/**
 * Activates template admin.
 *
 * @param string $function_name Function name for admin.
 */
function activate( string $function_name = 'setup_template_admin' ): void {
	if ( ! is_admin() ) {
		return;
	}
	$suffixes = array( '--admin.php', '_admin.php' );

	add_action(
		'admin_menu',
		function () use ( $suffixes, $function_name ) {
			_cb_admin_menu__template_admin( $suffixes, $function_name );
		}
	);
}

/**
 * Callback function for 'admin_menu' action.
 *
 * @param string[] $suffixes      Suffixes of file names of template admin files.
 * @param string   $function_name Function name.
 */
function _cb_admin_menu__template_admin( array $suffixes, string $function_name ): void {
	$post_id = \wpinc\get_admin_post_id();

	$tmp = get_post_meta( $post_id, '_wp_page_template', true );
	if (
		is_string( $tmp ) && '' !== $tmp  // Check for non-empty-string.
		&& 'default' !== $tmp
	) {
		foreach ( $suffixes as $sf ) {
			if ( _load_page_template_admin( $post_id, $tmp, $sf, $function_name ) ) {
				return;
			}
		}
	}
	if ( _is_page_on_front( $post_id ) ) {
		foreach ( $suffixes as $sf ) {
			if ( _load_page_template_admin( $post_id, 'front-page.php', $sf, $function_name ) ) {
				return;
			}
		}
	}
	$pt = \wpinc\get_admin_post_type();
	if ( is_string( $pt ) ) {
		foreach ( $suffixes as $sf ) {
			if ( _load_page_template_admin( $post_id, $pt . '.php', $sf, $function_name ) ) {
				return;
			}
		}
	}
}

/**
 * Loads page template admin.
 *
 * @access private
 *
 * @param int    $post_id       The post ID.
 * @param string $path          The path to the page template admin.
 * @param string $suffix        Suffix of the file name.
 * @param string $function_name Function name for admin.
 * @return bool True if successful.
 */
function _load_page_template_admin( int $post_id, string $path, string $suffix, string $function_name ): bool {
	$path = str_replace( '.php', $suffix, $path );
	$path = get_parent_theme_file_path( $path );

	if ( file_exists( $path ) ) {
		require_once $path;

		if ( function_exists( $function_name ) ) {
			$function_name( $post_id );
			return true;
		}
	}
	return false;
}


// -----------------------------------------------------------------------------


/**
 * Check whether the post ID is the front page.
 *
 * @access private
 *
 * @param int $post_id Post ID.
 * @return bool True if the post ID is the front page.
 */
function _is_page_on_front( int $post_id ): bool {
	if ( 'page' === get_option( 'show_on_front' ) ) {
		$pof = get_option( 'page_on_front' );
		if ( is_numeric( $pof ) && (int) $pof === $post_id ) {
			return true;
		}
	}
	return false;
}
