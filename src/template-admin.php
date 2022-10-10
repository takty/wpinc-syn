<?php
/**
 * Template Admin
 *
 * @package Wpinc Sys
 * @author Takuto Yanagida
 * @version 2022-10-11
 */

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
	$post_fixes = array( '--admin.php', '_admin.php' );

	add_action(
		'admin_menu',
		function () use ( $post_fixes, $function_name ) {
			_cb_admin_menu__template_admin( $post_fixes, $function_name );
		}
	);
}

/**
 * Callback function for 'admin_menu' action.
 *
 * @param array  $post_fixes    Post-fixes of file names of template admin files.
 * @param string $function_name Function name.
 */
function _cb_admin_menu__template_admin( array $post_fixes, string $function_name ): void {
	$post_id = \wpinc\get_admin_post_id();

	$pt = get_post_meta( $post_id, '_wp_page_template', true );
	if ( ! empty( $pt ) && 'default' !== $pt ) {
		foreach ( $post_fixes as $post_fix ) {
			if ( _load_page_template_admin( $post_id, $pt, $post_fix, $function_name ) ) {
				return;
			}
		}
	}
	if ( _is_page_on_front( $post_id ) ) {
		foreach ( $post_fixes as $post_fix ) {
			if ( _load_page_template_admin( $post_id, 'front-page.php', $post_fix, $function_name ) ) {
				return;
			}
		}
	}
	$post_type = \wpinc\get_admin_post_type();
	if ( ! empty( $post_type ) ) {
		foreach ( $post_fixes as $post_fix ) {
			if ( _load_page_template_admin( $post_id, $post_type . '.php', $post_fix, $function_name ) ) {
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
 * @param string $post_fix      Postfix of the file name.
 * @param string $function_name Function name for admin.
 */
function _load_page_template_admin( int $post_id, string $path, string $post_fix, string $function_name ) {
	$path = str_replace( '.php', $post_fix, $path );
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
		if ( (int) get_option( 'page_on_front' ) === $post_id ) {
			return true;
		}
	}
	return false;
}
