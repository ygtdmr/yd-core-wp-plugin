<?php
/**
 * Input Field UI Component for Admin Page
 *
 * This file defines the `Input` class, which handles the rendering and management of input fields within the WordPress admin interface.
 * It includes methods for setting properties like the ID, value, description, and help text, as well as rendering the input field and its associated attributes.
 *
 * @package YD\Core
 * @subpackage Admin\Page\View
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\Admin\Page\View;

use YD\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Input class for Input elements in the Admin UI.
 *
 * This abstract class provides basic functionality for handling form input elements
 * in the admin interface, including setting and getting attributes, rendering the input,
 * and adding help text and descriptions.
 */
abstract class Input {
	/**
	 * The ID of the input element.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * The data attribute name for the input element.
	 *
	 * @var string
	 */
	private $data_name;

	/**
	 * The value of the input element.
	 *
	 * @var mixed
	 */
	private $value;

	/**
	 * The help text displayed alongside the input.
	 *
	 * @var string
	 */
	private $help_text;

	/**
	 * The description displayed below the input.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Whether the input is required.
	 *
	 * @var bool
	 */
	private $is_required = true;

	/**
	 * Whether the input is ignored.
	 *
	 * @var bool
	 */
	private $is_ignored = false;

	/**
	 * Abstract method to get the name of the input element.
	 *
	 * @return string The name of the input element.
	 */
	abstract protected function get_name(): string;

	/**
	 * Gets the data attributes for the input element.
	 *
	 * @return array The array of data attributes for the input element.
	 */
	protected function get_data_attributes(): array {
		return array();
	}

	/**
	 * Gets the content for the input element.
	 *
	 * @return void
	 */
	protected function get_content() {}

	/**
	 * Sets whether the input element is required.
	 *
	 * @param string $is_required Whether the input is required.
	 * @return void
	 */
	public function set_required( string $is_required ) {
		$this->is_required = $is_required;
	}

	/**
	 * Checks if the input element is required.
	 *
	 * @return bool True if required, false otherwise.
	 */
	public function is_required(): bool {
		return $this->is_required;
	}

	/**
	 * Sets whether the input element is ignored.
	 *
	 * @param string $is_ignored Whether the input is ignored.
	 * @return void
	 */
	public function set_ignored( string $is_ignored ) {
		$this->is_ignored = $is_ignored;
	}

	/**
	 * Checks if the input element is ignored.
	 *
	 * @return bool True if ignored, false otherwise.
	 */
	public function is_ignored(): bool {
		return $this->is_ignored;
	}

	/**
	 * Sets the ID of the input element.
	 *
	 * @param string $id The ID of the input element.
	 * @return void
	 */
	public function set_id( string $id ) {
		$this->id = $id;
	}

	/**
	 * Gets the ID of the input element.
	 *
	 * @return string|null The ID of the input element, or null if not set.
	 */
	public function get_id(): ?string {
		return $this->id;
	}

	/**
	 * Sets the help text for the input element.
	 *
	 * @param mixed $help_text The help text to display.
	 * @return void
	 */
	public function set_help_text( $help_text ) {
		$this->help_text = $help_text;
	}

	/**
	 * Gets the help text for the input element.
	 *
	 * @return string The help text for the input element.
	 */
	public function get_help_text(): string {
		return $this->help_text;
	}

	/**
	 * Sets the description for the input element.
	 *
	 * @param mixed $description The description to display.
	 * @return void
	 */
	public function set_description( $description ) {
		$this->description = $description;
	}

	/**
	 * Gets the description for the input element.
	 *
	 * @return string The description for the input element.
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Sets the data name for the input element.
	 *
	 * @param string $data_name The data name for the input element.
	 * @return void
	 */
	public function set_data_name( string $data_name ) {
		$this->data_name = $data_name;
	}

	/**
	 * Gets the data name for the input element.
	 *
	 * @return string The data name for the input element.
	 */
	public function get_data_name(): string {
		return $this->data_name;
	}

	/**
	 * Sets the value for the input element.
	 *
	 * @param mixed $value The value of the input element.
	 * @return void
	 */
	public function set_value( mixed $value ) {
		$this->value = $value;
	}

	/**
	 * Gets the value for the input element.
	 *
	 * @return mixed The value of the input element.
	 */
	public function get_value(): mixed {
		return $this->value;
	}

	/**
	 * Renders the help text for the input element.
	 *
	 * @return void
	 */
	private function render_help_text() {
		if ( ! $this->help_text ) {
			return;
		}
		?><div class="help-tip" <?php $this->render_attribute( 'data-input-id', $this->get_id() ); ?> tabindex="0"><div class="help-text"><?php echo( esc_html( $this->help_text ) ); ?></div></div>
		<?php
	}

	/**
	 * Renders the description for the input element.
	 *
	 * @return void
	 */
	private function render_description() {
		if ( ! $this->description ) {
			return;
		}
		?>
		<p class="description"><?php echo( esc_html( $this->description ) ); ?></p>
		<?php
	}

	/**
	 * Renders the ID attribute for the input element.
	 *
	 * @return void
	 */
	protected function render_attribute_id_input() {
		if ( $this->get_id() !== null && $this->get_id() !== '' ) {
			$this->render_attribute( 'id', $this->get_id() . '_input' );
		}
	}

	/**
	 * Renders a specified attribute for the input element.
	 *
	 * @param string $key The attribute key.
	 * @param mixed  $value The attribute value.
	 * @return void
	 */
	protected function render_attribute( string $key, mixed $value ) {
		$value = (string) $value;
		if ( '' !== $value ) {
			echo(
				sprintf(
					'%s="%s"',
					esc_attr( $key ),
					esc_attr( $value )
				)
			);
		}
	}

	/**
	 * Renders the input element with its content, attributes, help text, and description.
	 *
	 * @return void
	 */
	public function render() {
		echo '<div class="yd-admin-ui-input yd-admin-ui-input-';
		echo( esc_attr( $this->get_name() ) );
		if ( $this->is_ignored ) {
			echo ' ignored';
		}
		echo '"';
		$this->render_attribute( 'id', $this->get_id() );

		foreach ( $this->get_data_attributes() as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = wp_json_encode( $value, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE );
			}
			$this->render_attribute( 'data-' . $key, $value );
		}
		?>
		>
		<?php
			$this->get_content();
			$this->render_help_text();
			$this->render_description();
		?>
		</div>
		<?php
	}
}

?>
