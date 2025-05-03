<?php
/**
 * Color Picker Input UI Component for Admin Pages
 *
 * This file defines the `Color_Picker` class, responsible for rendering and managing
 * a colour‑picker control within the WordPress admin interface.  The control wraps
 * the WordPress <input type="text"> colour picker (with alpha channel support)
 * and stores the selected value (e.g. `rgb(255, 255, 255)` or
 * `rgba(255, 255, 255, 0.4)`).
 *
 * @package    YD\Core
 * @subpackage Admin\Page\View
 * @author     Yigit Demir
 * @since      1.0.0
 * @version    1.0.0
 */

namespace YD\Admin\Page\View;

defined( 'ABSPATH' ) || exit;

/**
 * UI component that renders a colour‑picker input in the admin area.
 *
 * Extends the generic `Input` class and overrides the minimal set of
 * methods required to output the control and its configuration.
 */
final class Color_Picker extends Input {

	/**
	 * Regular‑expression pattern used to validate colour strings accepted by
	 * the control (e.g. `rgb(255,255,255)` or `rgba(255,255,255,0.4)`).
	 */
	const RULE_PATTERN_MATCH = '/rgb\(\d{1,3}\,\d{1,3}\,\d{1,3}\)|rgba\(\d{1,3}\,\d{1,3}\,\d{1,3}\,0(?:\.\d{1,2})?\)/';

	/**
	 * Constructor for the Color_Picker class.
	 *
	 * @param string $data_name The name of the data for the input field.
	 */
	public function __construct( string $data_name ) {
		$this->set_data_name( $data_name );
	}

	/**
	 * Returns the name of the input field type.
	 *
	 * @return string The name of the input field type.
	 */
	protected function get_name(): string {
		return 'color-picker';
	}

	/**
	 * Outputs the HTML markup for the colour‑picker control.
	 *
	 * @return void
	 */
	protected function get_content() {
		?><input type="text" data-alpha-enabled="true"
		<?php
			$this->render_attribute_id_input();
			$this->render_attribute( 'name', $this->get_data_name() );
			$this->render_attribute( 'value', $this->get_value() );
		?>
		><?php
	}
}

?>
