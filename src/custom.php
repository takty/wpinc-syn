<?php
/**
 * Custom Settings.
 *
 * @package Wpinc Syn
 * @author Takuto Yanagida
 * @version 2022-01-31
 */

namespace wpinc\syn;

/**
 * Enables 'enter title here' label.
 */
function enable_enter_title_here_label() {
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
