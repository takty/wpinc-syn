<?php
/**
 * Custom Settings.
 *
 * @package Wpinc Sys
 * @author Takuto Yanagida
 * @version 2022-02-09
 */

namespace wpinc\sys;

/**
 * Activates simple default slugs.
 *
 * @param string|string[] $post_type_s Post types. Default array() (all post types).
 */
function activate_simple_default_slug( $post_type_s = array() ) {
	if ( ! is_admin() ) {
		return;
	}
	$pts = is_array( $post_type_s ) ? $post_type_s : array( $post_type_s );
	add_filter(
		'wp_unique_post_slug',
		function ( $slug, $post_ID, $post_status, $post_type ) use ( $pts ) {
			$post = get_post( $post_ID );
			if ( '0000-00-00 00:00:00' === $post->post_date_gmt ) {
				if ( empty( $pts ) || in_array( $post_type, $pts, true ) ) {
					$slug = $post_ID;
				}
			}
			return $slug;
		},
		10,
		4
	);
}

/**
 * Activates 'enter title here' label.
 */
function activate_enter_title_here_label() {
	if ( ! is_admin() ) {
		return;
	}
	add_filter(
		'enter_title_here',
		function ( $enter_title_here, $post ) {
			$pto = get_post_type_object( $post->post_type );
			$lab = $pto->labels->enter_title_here ?? '';
			if ( ! empty( $lab ) && is_string( $lab ) ) {
				$enter_title_here = esc_html( $lab );
			}
			return $enter_title_here;
		},
		10,
		2
	);
}


// -----------------------------------------------------------------------------


/**
 * Activates password from template.
 */
function activate_password_form_template(): void {
	if ( ! is_admin() ) {
		add_filter( 'the_password_form', '\wpinc\sys\_cb_the_password_form', 10 );
	}
}

/**
 * Callback function for 'the_password_form' hook.
 *
 * @access private
 *
 * @param string $output The password form HTML output.
 */
function _cb_the_password_form( $output ) {
	$password_form_template = locate_template( 'passwordform.php' );

	if ( '' !== $password_form_template ) {
		ob_start();
		require $password_form_template;
		$output = str_replace( "\n", '', ob_get_clean() );
	}
	return $output;
}


// -----------------------------------------------------------------------------


/**
 * Removes indications from post titles.
 *
 * @param bool $protected Whether to remove 'Protected'.
 * @param bool $private   Whether to remove 'Private'.
 */
function remove_post_title_indication( bool $protected, bool $private ): void {
	if ( ! is_admin() ) {
		if ( $protected ) {
			add_filter( 'protected_title_format', '\wpinc\sys\_cb_title_format' );
		}
		if ( $private ) {
			add_filter( 'private_title_format', '\wpinc\sys\_cb_title_format' );
		}
	}
}

/**
 * Callback function for 'protected_title_format' and 'private_title_format' filter.
 *
 * @return string Format.
 */
function _cb_title_format(): string {
	return '%s';
}

/**
 * Removes prefixes from archive titles.
 */
function remove_archive_title_prefix(): void {
	if ( ! is_admin() ) {
		add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
	}
}

/**
 * Removes separators from document title.
 */
function remove_document_title_separator(): void {
	add_filter(
		'document_title_parts',
		function ( $title ) {
			if ( is_front_page() ) {
				$title['title'] = _strip_custom_tags( $title['title'] );
			} else {
				$title['site'] = _strip_custom_tags( $title['site'] );
			}
			return $title;
		}
	);
}

/**
 * Strips all tags and custom 'br'.
 *
 * @access private
 *
 * @param string $text The text.
 * @return string The stripped text.
 */
function _strip_custom_tags( string $text ): string {
	// Replace double full-width spaces and br tags to single space.
	$text = preg_replace( '/　　|<\s*br\s*\/?>/ui', ' ', $text );
	$text = wp_strip_all_tags( $text, true );
	return $text;
}
