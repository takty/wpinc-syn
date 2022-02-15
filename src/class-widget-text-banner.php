<?php
/**
 * Text Banner Widget
 *
 * @package Wpinc Sys
 * @author Takuto Yanagida
 * @version 2022-02-15
 */

namespace wpinc\sys;

/**
 * Text banner widget.
 */
class Widget_Text_Banner extends \WP_Widget {

	/**
	 * Separates a line.
	 *
	 * @param string $str String.
	 * @return string String.
	 */
	private static function separate_line( string $str ): string {
		$ls  = preg_split( '/　　|<\s*br\s*\/?>/ui', $str );
		$_ls = array_map( 'esc_html', $ls );
		return implode( '<br>', $_ls );
	}

	/**
	 * Checks whether the string show color code.
	 *
	 * @param string $color String to be checked.
	 * @return bool True if the string is color code.
	 */
	private static function is_color_code( string $color ): bool {
		if ( preg_match( '/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $color ) ) {
			return true;
		}
		return false;
	}


	// -------------------------------------------------------------------------


	/**
	 * Template HTML of the widget.
	 *
	 * @var string
	 */
	private static $template;

	/**
	 * Whether to use color.
	 *
	 * @var bool
	 */
	private static $do_use_color;

	/**
	 * Whether to use background color.
	 *
	 * @var bool
	 */
	private static $do_use_bg_color;

	/**
	 * Whether to use optional color.
	 *
	 * @var bool
	 */
	private static $do_use_opt_color;

	/**
	 * Registers widgets.
	 *
	 * @param array $args {
	 *     Arguments.
	 *
	 *     @type string 'template'              Template HTML of the widget.
	 *     @type bool   'do_use_color'          Whether to use color.
	 *     @type bool   'do_use_bg_color'       Whether to use background color.
	 *     @type bool   'do_use_optional_color' Whether to use optional color.
	 * }
	 */
	public static function register( array $args ) {
		$args += array(
			'template'              => '',
			'do_use_color'          => true,
			'do_use_bg_color'       => true,
			'do_use_optional_color' => false,
		);

		self::$template         = $args['template'];
		self::$do_use_color     = $args['do_use_color'];
		self::$do_use_bg_color  = $args['do_use_bg_color'];
		self::$do_use_opt_color = $args['do_use_optional_color'];

		add_action(
			'admin_print_scripts-widgets.php',
			function () {
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker' );
			}
		);
		register_widget( '\wpinc\sys\Widget_Text_Banner' );
	}


	// -------------------------------------------------------------------------


	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'widget_text_banner',
			_x( 'Text Banner', 'text banner', 'wpinc_sys' ),
			array(
				'classname'   => 'widget_text_banner',
				'description' => _x( 'Text Banner', 'text banner', 'wpinc_sys' ),
			)
		);
	}

	/**
	 * Echoes the widget content.
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {
		global $post;

		$title    = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$title    = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		$link_url = ! empty( $instance['link_url'] ) ? $instance['link_url'] : '';
		$color    = ! empty( $instance['color'] ) ? $instance['color'] : '';
		$color_bg = ! empty( $instance['color_bg'] ) ? $instance['color_bg'] : '';

		$_title    = self::separate_line( $title );
		$_link_url = esc_attr( $link_url );
		$_color    = esc_attr( $color );
		$_color_bg = esc_attr( $color_bg );

		$output = str_replace(
			array( '%title%', '%link_url%', '%color%', '%color_bg%' ),
			array(
				$args['before_title'] . $_title . $args['after_title'],
				$_link_url,
				$_color,
				$_color_bg,
			),
			self::$template
		);

		echo $args['before_widget'];  // phpcs:ignore
		echo $output;  // phpcs:ignore
		echo $args['after_widget'];  // phpcs:ignore
	}

	/**
	 * Updates a particular instance of a widget.
	 *
	 * @param array $new_instance New settings for this instance as input by the user via
	 *                            WP_Widget::form().
	 * @param array $old_instance Old settings for this instance.
	 * @return array Settings to save or bool false to cancel saving.
	 */
	public function update( $new_instance, $old_instance ) {
		$new_instance = wp_parse_args(
			$new_instance,
			array(
				'title'    => '',
				'link_url' => '',
				'color'    => '',
				'color_bg' => '',
			)
		);

		$instance = $old_instance;

		$instance['title']    = sanitize_text_field( $new_instance['title'] );
		$instance['link_url'] = sanitize_text_field( $new_instance['link_url'] );
		$instance['color']    = sanitize_text_field( $new_instance['color'] );
		$instance['color_bg'] = sanitize_text_field( $new_instance['color_bg'] );

		return $instance;
	}

	/**
	 * Outputs the settings update form.
	 *
	 * @param array $instance Current settings.
	 */
	public function form( $instance ) {
		$instance = wp_parse_args(
			(array) $instance,
			array(
				'title'     => '',
				'link_url'  => '',
				'color'     => '',
				'color_bg'  => '',
				'color_opt' => '',
			)
		);

		$id_title       = $this->get_field_id( 'title' );
		$id_link_url    = $this->get_field_id( 'link_url' );
		$id_color       = $this->get_field_id( 'color' );
		$id_color_bg    = $this->get_field_id( 'color_bg' );
		$id_color_opt   = $this->get_field_id( 'color_opt' );
		$name_title     = $this->get_field_name( 'title' );
		$name_link_url  = $this->get_field_name( 'link_url' );
		$name_color     = $this->get_field_name( 'color' );
		$name_color_bg  = $this->get_field_name( 'color_bg' );
		$name_color_opt = $this->get_field_name( 'color_opt' );

		$title     = $instance['title'];
		$link_url  = $instance['link_url'];
		$color     = empty( $instance['color'] ) ? '#ffffff' : $instance['color'];
		$color_bg  = empty( $instance['color_bg'] ) ? '#ffffff' : $instance['color_bg'];
		$color_opt = empty( $instance['color_opt'] ) ? '#ffffff' : $instance['color_opt'];

		echo '<table>';
		?>
		<tr>
			<td><label for="<?php echo esc_attr( $id_title ); ?>"><?php echo esc_html_x( 'Title', 'text banner', 'wpinc_sys' ); ?>:</label></td>
			<td><input id="<?php echo esc_attr( $id_title ); ?>" name="<?php echo esc_attr( $name_title ); ?>" class="widefat title sync-input" type="text" value="<?php echo esc_attr( $title ); ?>"></td>
		</tr>
		<tr>
			<td><label for="<?php echo esc_attr( $id_link_url ); ?>"><?php echo esc_html_x( 'Link To', 'text banner', 'wpinc_sys' ); ?>:</label></td>
			<td><input id="<?php echo esc_attr( $id_link_url ); ?>" name="<?php echo esc_attr( $name_link_url ); ?>" class="widefat link sync-input" type="text" value="<?php echo esc_attr( $link_url ); ?>" placeholder="http://" pattern="((\w+:)?\/\/\w.*|\w+:(?!\/\/$)|\/|\?|#).*"></td>
		</tr>
		<?php
		if ( self::$do_use_color ) {
			?>
			<tr>
				<td><label for="<?php echo esc_attr( $id_color ); ?>"><?php echo esc_html_x( 'Color', 'text banner', 'wpinc_sys' ); ?>:</label></td>
				<td><input id="<?php echo esc_attr( $id_color ); ?>" name="<?php echo esc_attr( $name_color ); ?>" class="widefat color-picker" type="text" value="<?php echo esc_attr( $color ); ?>"></td>
			</tr>
			<?php
		}
		if ( self::$do_use_bg_color ) {
			?>
			<tr>
				<td><label for="<?php echo esc_attr( $id_color_bg ); ?>"><?php echo esc_html_x( 'Background Color', 'text banner', 'wpinc_sys' ); ?>:</label></td>
				<td><input id="<?php echo esc_attr( $id_color_bg ); ?>" name="<?php echo esc_attr( $name_color_bg ); ?>" class="widefat color-picker" type="text" value="<?php echo esc_attr( $color_bg ); ?>"></td>
			</tr>
			<?php
		}
		if ( self::$do_use_opt_color ) {
			?>
			<tr>
				<td><label for="<?php echo esc_attr( $id_color_opt ); ?>"><?php echo esc_html_x( 'Optional Color', 'text banner', 'wpinc_sys' ); ?>:</label></td>
				<td><input id="<?php echo esc_attr( $id_color_opt ); ?>" name="<?php echo esc_attr( $name_color_opt ); ?>" class="widefat color-picker" type="text" value="<?php echo esc_attr( $color_opt ); ?>"></td>
			</tr>
			<?php
		}
		echo '</table>';

		$code = "(function ($) {
			$(function () {
				function initColorPicker(widget) {
					const opts = {
						mode        : 'hsl',
						defaultColor: false,
						clear       : function (e) { $(e.target).trigger('change'); },
						change      : function (e, ui) {
							$(e.target).val(ui.color.toString());
							$(e.target).trigger('change');
						},
					};
					widget.find('.color-picker').wpColorPicker(opts);
				}
				function onFormUpdate(event, widget) { initColorPicker(widget); }
				$(document).on('widget-added widget-updated', onFormUpdate);
				$(document).ready(function () {
					$('#widgets-right .widget:has(.color-picker)').each(function () { initColorPicker($(this)); });
				});
			});
		})(jQuery);";
		wp_add_inline_script( 'wp-color-picker', $code, 'after' );
	}

}
