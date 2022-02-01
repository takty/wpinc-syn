<?php
/**
 * Custom Option Page
 *
 * @package Wpinc Sys
 * @author Takuto Yanagida
 * @version 2022-02-01
 */

namespace wpinc\sys\option_page;

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
	$args += array(
		'page_title'   => '',
		'menu_title'   => '',
		'slug'         => '',
		'option_key'   => '',
		'as_menu_page' => false,
		'sections'     => array(),
	);
	if ( empty( $args['page_title'] ) && ! empty( $args['menu_title'] ) ) {
		$args['page_title'] = $args['menu_title'];
	}
	if ( ! empty( $args['page_title'] ) && empty( $args['menu_title'] ) ) {
		$args['menu_title'] = $args['page_title'];
	}
	foreach ( $args['sections'] as $sid => &$cont ) {
		$cont += array(
			'label'  => '',
			'fields' => array(),
		);
		foreach ( $cont['fields'] as $key => &$params ) {
			$params += array(
				'type'        => '',
				'label'       => '',
				'description' => null,
				'filter'      => null,
			);
		}
	}

	$inst = _get_instance();

	$inst->page_title   = $args['page_title'];
	$inst->menu_title   = $args['menu_title'];
	$inst->slug         = $args['slug'];
	$inst->option_key   = $args['option_key'];
	$inst->as_menu_page = $args['as_menu_page'];
	$inst->sections     = $args['sections'];

	add_action( 'admin_menu', '\wpinc\sys\option_page\_cb_admin_menu' );
	add_action( 'admin_init', '\wpinc\sys\option_page\_cb_admin_init' );
}

/**
 * Callback function for 'admin_menu' action.
 *
 * @access private
 */
function _cb_admin_menu(): void {
	$inst = _get_instance();
	if ( $inst->as_menu_page ) {
		add_menu_page(
			$inst->page_title,
			$inst->menu_title,
			'edit_pages',
			$inst->slug,
			'\wpinc\sys\option_page\_cb_output_html'
		);
	} else {
		add_submenu_page(
			'options-general.php',
			$inst->page_title,
			$inst->menu_title,
			'manage_options',
			$inst->slug,
			'\wpinc\sys\option_page\_cb_output_html'
		);
	}
}

/**
 * Callback function for outputting HTML.
 *
 * @access private
 */
function _cb_output_html(): void {
	$inst = _get_instance();
	?>
	<div class="wrap">
		<h2><?php echo esc_html( $inst->page_title ); ?></h2>
		<form method="post" action="options.php">
	<?php
			settings_fields( $inst->slug );
			do_settings_sections( $inst->slug );
			submit_button();
	?>
		</form>
	</div>
	<?php
}

/**
 * Callback function for 'admin_init' action.
 *
 * @access private
 */
function _cb_admin_init(): void {
	$inst = _get_instance();
	$vals = get_option( $inst->option_key );

	register_setting(
		$inst->slug,
		$inst->option_key,
		array(
			'sanitize_callback' => '\wpinc\sys\option_page\_cb_sanitize',
		)
	);
	foreach ( $inst->sections as $sid => $cont ) {
		add_settings_section( $sid, $cont['label'], null, $inst->slug );

		foreach ( $cont['fields'] as $key => $params ) {
			add_settings_field(
				$key,
				$params['label'],
				'\wpinc\sys\option_page\_cb_output_html_field',
				$inst->slug,
				$sid,
				array( $key, $params, $vals )
			);
		}
	}
}

/**
 * Callback function for sanitizing input data.
 *
 * @access private
 *
 * @param array $input Input data.
 * @return array Sanitized data.
 */
function _cb_sanitize( array $input ): array {
	$inst = _get_instance();
	$new  = array();

	foreach ( $inst->sections as $sid => $cont ) {
		foreach ( $cont['fields'] as $key => $params ) {
			if ( ! isset( $input[ $key ] ) ) {
				continue;
			}
			$filter = $params['filter'];
			if ( $filter ) {
				$new[ $key ] = call_user_func( $filter, $input[ $key ] );
			} else {
				$new[ $key ] = $input[ $key ];
			}
		}
	}
	return $new;
}

/**
 * Callback function for outputting HTML fields.
 *
 * @access private
 *
 * @param array $args {
 *     Arguments.
 *     @type string 'key'    Sub key of the option.
 *     @type array  'params' Parameters of the field.
 *     @type array  'vals'   Array of values.
 * }
 */
function _cb_output_html_field( array $args ): void {
	list( $key, $params, $vals ) = $args;

	$inst = _get_instance();
	$name = "{$inst->option_key}[{$key}]";
	$desc = $params['description'] ?? '';
	$val  = $vals[ $key ] ?? null;

	switch ( $params['type'] ) {
		case 'checkbox':
			_echo_checkbox( $val, $key, $name, $desc );
			break;
		case 'textarea':
			_echo_textarea( $val, $key, $name, $desc );
			break;
		default:
			_echo_input( $val, $key, $name, $params['type'], $desc );
			break;
	}
}

/**
 * Displays input fields.
 *
 * @access private
 *
 * @param string|null $val  Current value.
 * @param string      $key  Sub key of the option.
 * @param string      $name Name attribute.
 * @param string      $type Type attribute.
 * @param string      $desc (Optional) Description. Default ''.
 */
function _echo_input( ?string $val, string $key, string $name, string $type, string $desc = '' ): void {
	printf(
		'<input type="%s" id="%s" name="%s" value="%s" class="regular-text" aria-describedby="%s-description">',
		esc_attr( $type ),
		esc_attr( $key ),
		esc_attr( $name ),
		esc_attr( $val ?? '' ),
		esc_attr( $key )
	);
	if ( ! empty( $desc ) ) {
		printf(
			'<p class="description" id="%s-description">%s</p>',
			esc_attr( $key ),
			esc_html( $desc )
		);
	}
}

/**
 * Displays textarea fields.
 *
 * @access private
 *
 * @param string|null $val  Current value.
 * @param string      $key  Sub key of the option.
 * @param string      $name Name attribute.
 * @param string      $desc (Optional) Description. Default ''.
 */
function _echo_textarea( ?string $val, string $key, string $name, string $desc = '' ): void {
	if ( ! empty( $desc ) ) {
		printf(
			'<label for="%s">%s</label>',
			esc_attr( $key ),
			esc_html( $desc )
		);
	}
	printf(
		'<p><textarea id="%s" name="%s" rows="10" class="large-text" aria-describedby="%s-description">%s</textarea></p>',
		esc_attr( $key ),
		esc_attr( $name ),
		esc_attr( $key ),
		esc_attr( $val ?? '' )
	);
}

/**
 * Displays checkbox fields.
 *
 * @access private
 *
 * @param string|null $val  Current value.
 * @param string      $key  Sub key of the option.
 * @param string      $name Name attribute.
 * @param string      $desc (Optional) Description. Default ''.
 */
function _echo_checkbox( ?string $val, string $key, string $name, string $desc = '' ): void {
	printf(
		'<label><input type="checkbox" name="%s" value="1"%s> %s</label>',
		esc_attr( $name ),
		'1' === ( $val ?? '' ) ? ' checked' : '',
		esc_html( $desc )
	);
}


// -----------------------------------------------------------------------------


/**
 * Gets instance.
 *
 * @access private
 *
 * @return object Instance.
 */
function _get_instance(): object {
	static $values = null;
	if ( $values ) {
		return $values;
	}
	$values = new class() {
		/**
		 * The text to be displayed in the title tags of the page.
		 *
		 * @var string
		 */
		public $page_title;

		/**
		 * The text to be used for the menu.
		 *
		 * @var string
		 */
		public $menu_title;

		/**
		 * The slug name to refer to this menu by.
		 *
		 * @var string
		 */
		public $slug;

		/**
		 * Name of the option to retrieve.
		 *
		 * @var string
		 */
		public $option_key;

		/**
		 * Whether to add the option as menu page.
		 *
		 * @var bool
		 */
		public $as_menu_page;

		/**
		 * Sections of the option page.
		 *
		 * @var array
		 */
		public $sections;
	};
	return $values;
}
