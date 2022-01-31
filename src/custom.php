<?php
/**
 * Custom Settings.
 *
 * @package Wpinc Sys
 * @author Takuto Yanagida
 * @version 2022-01-31
 */

namespace wpinc\sys;

/**
 * Activates 'enter title here' label.
 */
function activate_enter_title_here_label() {
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

/**
 * Activates password from template.
 */
function activate_password_form_template(): void {
	add_filter( 'the_password_form', '\wpinc\alt\_cb_the_password_form', 10 );
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
	if ( $protected ) {
		add_filter( 'protected_title_format', '\wpinc\alt\_cb_title_format' );
	}
	if ( $private ) {
		add_filter( 'private_title_format', '\wpinc\alt\_cb_title_format' );
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
	add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
}
