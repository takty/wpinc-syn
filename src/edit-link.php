<?php
/**
 * Custom Template Tags for Edit Links
 *
 * @package Wpinc Sys
 * @author Takuto Yanagida
 * @version 2022-02-21
 */

namespace wpinc\sys;

/**
 * Echo edit post link of posts when available.
 *
 * @param string $cls CSS class.
 */
function the_admin_edit_post( string $cls = '' ): void {
	if ( can_edit_post() ) {
		?>
		<div class="admin-edit<?php echo esc_attr( ' ' . $cls ); ?>">
			<a href="<?php the_edit_link_post(); ?>">EDIT</a>
		</div>
		<?php
	}
}

/**
 * Echo edit post link of menus when available.
 *
 * @param int|null $menu_id Menu ID to edit.
 * @param string   $cls     CSS Class.
 */
function the_admin_edit_menu( ?int $menu_id, string $cls = '' ): void {
	if ( null !== $menu_id && can_edit_theme_options() ) {
		?>
		<div class="admin-edit<?php echo esc_attr( ' ' . $cls ); ?>">
			<a href="<?php the_edit_link_menu( $menu_id ); ?>">EDIT</a>
		</div>
		<?php
	}
}


// -----------------------------------------------------------------------------


/**
 * Determines whether the current user can edit the post.
 *
 * @return bool True if user can edit post.
 */
function can_edit_post(): bool {
	global $post;
	return $post && is_user_logged_in() && current_user_can( 'edit_post', $post->ID );
}

/**
 * Determines whether the current user can edit theme options.
 *
 * @return bool True if user can edit theme options.
 */
function can_edit_theme_options(): bool {
	return is_user_logged_in() && current_user_can( 'edit_theme_options' );
}


// -----------------------------------------------------------------------------


/**
 * Echos edit link for post.
 */
function the_edit_link_post(): void {
	global $post;
	echo esc_attr( admin_url( "post.php?post={$post->ID}&action=edit" ) );
}

/**
 * Echos edit link for menu.
 *
 * @param int $menu_id Menu ID to edit.
 */
function the_edit_link_menu( int $menu_id ): void {
	echo esc_attr( admin_url( "nav-menus.php?action=edit&menu=$menu_id" ) );
}

/**
 * Echos edit link for widgets.
 */
function the_edit_link_widget(): void {
	echo esc_attr( admin_url( 'widgets.php' ) );
}

/**
 * Echos edit link for options.
 *
 * @param string $page_slug Page slug of option to edit.
 */
function the_edit_link_option_page( string $page_slug ): void {
	echo esc_attr( admin_url( "options-general.php?page=$page_slug" ) );
}
