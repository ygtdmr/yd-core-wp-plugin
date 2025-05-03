<?php
/**
 * Text Input UI Component for Admin Page
 *
 * This file defines the `Text` class, which handles the rendering and management of a text input field within the WordPress admin interface.
 * It includes methods for setting placeholder text, custom attributes, and handling the input value and required fields.
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
 * Text class represents an input field of type "text" in the admin panel view.
 * It extends the Input class and provides functionality for rendering a text input
 * field with customizable attributes such as placeholder text and custom attributes.
 */
final class Text extends Input {
	/**
	 * The placeholder text for the input field.
	 *
	 * @var string|null
	 */
	private $placeholder;

	/**
	 * An array of custom attributes to be added to the input element.
	 *
	 * @var array
	 */
	private $custom_attributes = array();

	/**
	 * Constructor for the Text class.
	 *
	 * @param string      $data_name The name of the data for the input field.
	 * @param string|null $placeholder Optional placeholder text for the input field.
	 */
	public function __construct( string $data_name, ?string $placeholder = null ) {
		$this->set_data_name( $data_name );
		$this->placeholder = $placeholder;
	}

	/**
	 * Returns the name of the input field type.
	 *
	 * @return string The name of the input field type.
	 */
	protected function get_name(): string {
		return 'text';
	}

	/**
	 * Renders the HTML content for the text input field.
	 *
	 * @return void
	 */
	protected function get_content() {
		?><input class="regular-text" type="text" 
		<?php
			$this->render_attribute_id_input();
			$this->render_attribute( 'required', $this->is_required() );
			$this->render_attribute( 'name', $this->get_data_name() );
			$this->render_attribute( 'value', $this->get_value() );
			$this->render_attribute( 'placeholder', $this->placeholder );

		foreach ( $this->custom_attributes as $key => $value ) {
			$this->render_attribute( $key, $value );
		}
		?>
		><?php
	}

	/**
	 * Sets custom attributes for the input field.
	 *
	 * @param array $attributes An associative array of custom attributes.
	 * @return void
	 */
	public function set_custom_attributes( array $attributes ) {
		$this->custom_attributes = $attributes;
	}
}

?>
