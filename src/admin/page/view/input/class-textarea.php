<?php
/**
 * Textarea Input UI Component for Admin Page
 *
 * This file defines the `Textarea` class, which handles the rendering and management of a textarea input field within the WordPress admin interface.
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
 * Textarea class represents a textarea input field with the ability to set custom attributes and placeholders.
 * It extends the base Input class and overrides specific methods for rendering the textarea input.
 */
final class Textarea extends Input {
	/**
	 * Placeholder text for the textarea.
	 *
	 * @var string|null
	 */
	private $placeholder;

	/**
	 * Custom attributes for the textarea input.
	 *
	 * @var array
	 */
	private $custom_attributes = array();

	/**
	 * Initializes the data name for the textarea and optionally sets a placeholder.
	 *
	 * @param string      $data_name The name of the data.
	 * @param string|null $placeholder The placeholder text for the textarea (optional).
	 */
	public function __construct( string $data_name, ?string $placeholder = null ) {
		$this->set_data_name( $data_name );
		$this->placeholder = $placeholder;
	}

	/**
	 * Returns the name of the input field.
	 *
	 * @return string The name of the input field ('textarea').
	 */
	protected function get_name(): string {
		return 'textarea';
	}

	/**
	 * Renders the content of the textarea input.
	 * Includes attributes like id, required, name, placeholder, and any custom attributes.
	 *
	 * @return void
	 */
	protected function get_content() {
		?><textarea class="regular-text" 
		<?php
			$this->render_attribute_id_input();
			$this->render_attribute( 'required', $this->is_required() );
			$this->render_attribute( 'name', $this->get_data_name() );
			$this->render_attribute( 'placeholder', $this->placeholder );

		foreach ( $this->custom_attributes as $key => $value ) {
			$this->render_attribute( $key, $value );
		}
		?>
		><?php echo( esc_html( $this->get_value() ) ); ?></textarea><?php
	}

	/**
	 * Sets custom attributes for the textarea input.
	 *
	 * @param array $attributes The custom attributes to set.
	 * @return void
	 */
	public function set_custom_attributes( array $attributes ) {
		$this->custom_attributes = $attributes;
	}
}

?>
