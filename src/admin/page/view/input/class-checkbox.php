<?php
/**
 * Checkbox Input UI Component for Admin Page
 *
 * This file defines the `Checkbox` class, which handles the rendering and management of checkbox input fields within the WordPress admin interface.
 * It includes methods for setting properties like the label, whether the checkbox is disabled, and handling the checkbox state. It also includes rendering functionality for the checkbox input field.
 *
 * @package YD\Core
 * @subpackage Admin\Page\View
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\Admin\Page\View;

defined( 'ABSPATH' ) || exit;

/**
 * Checkbox class for rendering and managing a checkbox input field.
 *
 * This class extends the Input class and is responsible for rendering a checkbox with optional label and disabled state.
 * It also handles the submission of the checkbox value via a hidden input field.
 */
final class Checkbox extends Input {
	/**
	 * The label for the checkbox.
	 *
	 * @var string|null
	 */
	private $label;

	/**
	 * The disabled state of the checkbox.
	 *
	 * @var bool|null
	 */
	private $is_disabled;

	/**
	 * Constructor for the Checkbox class.
	 *
	 * @param string      $data_name The data name for the checkbox.
	 * @param string|null $label The label for the checkbox.
	 */
	public function __construct( string $data_name, ?string $label = null ) {
		$this->set_data_name( $data_name );
		$this->label = $label;
	}

	/**
	 * Get the name for the input type.
	 *
	 * @return string The input type name (checkbox).
	 */
	protected function get_name(): string {
		return 'checkbox';
	}

	/**
	 * Set the disabled state of the checkbox.
	 *
	 * @param bool $is_disabled The disabled state of the checkbox.
	 * @return void
	 */
	public function set_disabled( bool $is_disabled ) {
		$this->is_disabled = $is_disabled;
	}

	/**
	 * Get the disabled state of the checkbox.
	 *
	 * @return bool The disabled state, defaulting to false if not set.
	 */
	private function get_disabled(): bool {
		return $this->is_disabled ?? false;
	}

	/**
	 * Render the checkbox input field with its label and hidden input for value submission.
	 *
	 * @return void
	 */
	protected function get_content() {
		?><label><input type="checkbox" 
		<?php
		$this->render_attribute_id_input();
		$this->render_attribute( 'checked', $this->get_value() );
		$this->render_attribute( 'disabled', $this->get_disabled() );
		?>
	/><?php echo( esc_html( $this->label ) ); ?></label>
	<input type="hidden" 
		<?php
		$this->render_attribute( 'name', $this->get_data_name() );
		$this->render_attribute( 'value', (int) $this->get_value() );
		?>
	/>
		<?php
	}
}

?>
