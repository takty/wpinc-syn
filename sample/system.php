<?php
/**
 * System
 *
 * @package Sample
 * @author Takuto Yanagida
 * @version 2022-01-31
 */

namespace sample {
	require_once __DIR__ . '/sys/custom.php';
	require_once __DIR__ . '/sys/edit-link.php';

	/**
	 * Activates 'enter title here' label.
	 */
	function activate_enter_title_here_label() {
		\wpinc\sys\activate_enter_title_here_label();
	}

	/**
	 * Activates password from template.
	 */
	function activate_password_form_template(): void {
		\wpinc\sys\activate_password_form_template();
	}

	/**
	 * Removes indications from post titles.
	 *
	 * @param bool $protected Whether to remove 'Protected'.
	 * @param bool $private   Whether to remove 'Private'.
	 */
	function remove_post_title_indication( bool $protected, bool $private ): void {
		\wpinc\sys\remove_post_title_indication( $protected, $private );
	}

	/**
	 * Removes prefixes from archive titles.
	 */
	function remove_archive_title_prefix(): void {
		\wpinc\sys\remove_archive_title_prefix();
	}


	// -------------------------------------------------------------------------


	/**
	 * Echo edit post link of posts when available.
	 *
	 * @param string $cls CSS class.
	 */
	function the_admin_edit_post( string $cls = '' ): void {
		\wpinc\sys\the_admin_edit_post( $cls );
	}

	/**
	 * Echo edit post link of menus when available.
	 *
	 * @param \wpinc\navi\Nav_Menu $nav_menu Nav_Menu to be edited.
	 * @param string               $cls      CSS Class.
	 */
	function the_admin_edit_menu( \wpinc\navi\Nav_Menu $nav_menu, string $cls = '' ): void {
		\wpinc\sys\the_admin_edit_menu( $nav_menu, $cls );
	}

	/**
	 * Determines whether the current user can edit the post.
	 *
	 * @return bool True if user can edit post.
	 */
	function can_edit_post(): bool {
		return \wpinc\sys\can_edit_post();
	}

	/**
	 * Determines whether the current user can edit theme options.
	 *
	 * @return bool True if user can edit theme options.
	 */
	function can_edit_theme_options(): bool {
		return \wpinc\sys\can_edit_theme_options();
	}

	/**
	 * Echos edit link for post.
	 */
	function the_edit_link_post(): void {
		\wpinc\sys\the_edit_link_post();
	}

	/**
	 * Echos edit link for menu.
	 *
	 * @param int $menu_id Menu ID to edit.
	 */
	function the_edit_link_menu( int $menu_id ): void {
		\wpinc\sys\the_edit_link_menu( $menu_id );
	}

	/**
	 * Echos edit link for widgets.
	 */
	function the_edit_link_widget(): void {
		\wpinc\sys\the_edit_link_widget();
	}

	/**
	 * Echos edit link for options.
	 *
	 * @param string $page_slug Page slug of option to edit.
	 */
	function the_edit_link_option_page( string $page_slug ): void {
		\wpinc\sys\the_edit_link_option_page( $page_slug );
	}
}

namespace sample\ajax {
	require_once __DIR__ . '/sys/ajax.php';

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
		\wpinc\sys\ajax\activate( $args );
	}

	/**
	 * Gets AJAX URL.
	 *
	 * @param array $args  Arguments.
	 * @param array $query (Optional) Query arguments.
	 * @return string AJAX URL.
	 */
	function get_url( array $args, array $query = array() ): string {
		return \wpinc\sys\ajax\get_url( $args, $query );
	}
}

namespace sample\classic_editor {
	require_once __DIR__ . '/sys/classic-editor.php';

	/**
	 * Add buttons to the tool bar.
	 *
	 * @param string|null $url_to    URL to this script.
	 * @param int         $row_index Tool bar row.
	 */
	function add_buttons( ?string $url_to = null, int $row_index = 2 ): void {
		\wpinc\sys\classic_editor\add_buttons( $url_to, $row_index );
	}

	/**
	 * Adds style formats.
	 *
	 * @param array $args {
	 *     Arguments.
	 *
	 *     @type array 'labels'  Labels of styles.
	 *     @type array 'formats' Additional formats.
	 * }
	 */
	function add_style_formats( array $args = array() ): void {
		\wpinc\sys\classic_editor\add_style_formats( $args );
	}

	/**
	 * Sets used heading tags.
	 *
	 * @param int $first_level First level of heading tag.
	 * @param int $count       Count of headings.
	 */
	function set_used_heading( int $first_level = 2, int $count = 3 ): void {
		\wpinc\sys\classic_editor\set_used_heading( $first_level, $count );
	}

	/**
	 * Disables table resizing function.
	 */
	function disable_table_resizing(): void {
		\wpinc\sys\classic_editor\disable_table_resizing();
	}

	/**
	 * Adds quick tags.
	 */
	function add_quick_tags(): void {
		\wpinc\sys\classic_editor\add_quick_tags();
	}
}

namespace sample\ip_restriction {
	require_once __DIR__ . '/sys/ip-restriction.php';

	/**
	 * Activates IP restriction.
	 */
	function activate(): void {
		\wpinc\sys\ip_restriction\activate();
	}

	/**
	 * Adds CIDR.
	 *
	 * @param string $cidr Allowed CIDR.
	 * @param string $cls  CSS class.
	 */
	function add_allowed_cidr( string $cidr, string $cls = '' ): void {
		\wpinc\sys\ip_restriction\add_allowed_cidr( $cidr, $cls );
	}

	/**
	 * Adds post type as IP restricted.
	 *
	 * @param string|string[] $post_type_s Post types.
	 */
	function add_post_type( $post_type_s ): void {
		\wpinc\sys\ip_restriction\add_post_type( $post_type_s );
	}

	/**
	 * Check whether the current IP is allowed.
	 *
	 * @return bool True if the IP is OK.
	 */
	function is_allowed(): bool {
		return \wpinc\sys\ip_restriction\is_allowed();
	}

	/**
	 * Check whether the current query is restricted and the current IP is not allowed.
	 *
	 * @return bool True if the current query is restricted and the current IP is not allowed.
	 */
	function is_restricted(): bool {
		return \wpinc\sys\ip_restriction\is_restricted();
	}
}

namespace sample\option_page {
	require_once __DIR__ . '/sys/option-page.php';

	/**
	 * Activates custom option page.
	 *
	 * @param array $args {
	 *     Arguments.
	 *
	 *     @type string 'page_title'   The text to be displayed in the title tags of the page when the menu is selected.
	 *     @type string 'menu_title'   The text to be used for the menu.
	 *     @type string 'slug'         Slug used as the menu name and the option name.
	 *     @type string 'option_key'   Option key.
	 *     @type bool   'as_menu_page' Whether to add the menu item to side menu. Default false.
	 *     @type array  'sections'     Section parameters.
	 * }
	 */
	function activate( array $args ): void {
		\wpinc\sys\option_page\activate( $args );
	}
}

namespace sample\sticky {
	require_once __DIR__ . '/sys/sticky.php';

	/**
	 * Disables embedded sticky post function.
	 * The embedded sticky post function is only for default 'post' type.
	 */
	function disable_embedded_sticky(): void {
		\wpinc\sys\sticky\disable_embedded_sticky();
	}

	/**
	 * Makes custom post type sticky.
	 *
	 * @param string|string[] $post_type_s Post types.
	 */
	function add_post_type( $post_type_s ): void {
		\wpinc\sys\sticky\add_post_type( $post_type_s );
	}
}

namespace sample\template_admin {
	require_once __DIR__ . '/sys/template-admin.php';

	/**
	 * Activates template admin.
	 *
	 * @param string $function_name Function name for admin.
	 */
	function activate( string $function_name = 'setup_template_admin' ): void {
		\wpinc\sys\template_admin\activate( $function_name );
	}
}
