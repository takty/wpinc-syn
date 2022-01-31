<?php
/**
 * Miscellaneous for Admin
 *
 * @author Takuto Yanagida
 * @version 2021-03-23
 */

namespace st;

function url_to_attachment_id( $url ) {
	$ud = wp_upload_dir();

	$upload_url = $ud['baseurl'];
	if ( strpos( $url, $upload_url ) !== 0 ) {
		return false;
	}
	$id_url = _search_attachment_id( $url, $upload_url );
	if ( $id_url !== false ) {
		return $id_url;
	}
	$full_url = preg_replace( '/(-[0-9]+x[0-9]+)(\.[^.]+){0,1}$/i', '${2}', $url );
	if ( $url === $full_url ) {
		return false;
	}
	return _search_attachment_id( $full_url, $upload_url );
}

function _search_attachment_id( $url, $upload_url ) {
	$attached_file = str_replace( $upload_url . '/', '', $url );

	global $wpdb;
	$id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_wp_attached_file' AND meta_value='%s' LIMIT 1;",
			$attached_file
		)
	);
	if ( $id === 0 ) {
		return false;
	}
	return array( 'id' => $id, 'url' => $url );
}
