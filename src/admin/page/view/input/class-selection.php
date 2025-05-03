<?php
/**
 * Selection Input UI Component for Admin Page
 *
 * This file defines the `Selection` class, which handles the rendering and management of a selection input field within the WordPress admin interface.
 * It includes methods for handling multiple selections, setting AJAX actions, and defining input properties for the selection input.
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
 * Selection class defines the "Selection" input field, a custom UI component for selecting action targets
 * within the WordPress admin interface. It extends the base `Input` class and includes functionality for
 * managing properties, display names, AJAX actions, and the ability to handle multiple selections.
 */
final class Selection extends Input {
	/**
	 * The properties of the selection input.
	 *
	 * @var array|null
	 */
	private $properties;

	/**
	 * The display name of the selection input.
	 *
	 * @var string|null
	 */
	private $display_name;

	/**
	 * The name of the AJAX action associated with the selection input.
	 *
	 * @var string|null
	 */
	private $ajax_action_name;

	/**
	 * Whether the selection allows multiple values.
	 *
	 * @var bool
	 */
	private $is_multiple = true;

	/**
	 * Constructor for the Selection class.
	 *
	 * @param string      $data_name The data name for the selection input.
	 * @param string|null $display_name Optional display name for the selection input.
	 */
	public function __construct( string $data_name, ?string $display_name = null ) {
		$this->set_data_name( $data_name );
		$this->display_name = $display_name;
	}

	/**
	 * Set the AJAX action name.
	 *
	 * @param string $ajax_action_name The AJAX action name.
	 * @return void
	 */
	public function set_ajax_action_name( $ajax_action_name ) {
		$this->ajax_action_name = $ajax_action_name;
	}

	/**
	 * Set the properties for the selection input.
	 *
	 * @param array $properties The properties to set.
	 * @return void
	 */
	public function set_properties( $properties ) {
		$this->properties = $properties;
	}

	/**
	 * Set whether the selection allows multiple values.
	 *
	 * @param bool $is_multiple Whether the selection allows multiple values.
	 * @return void
	 */
	public function set_multiple( $is_multiple ) {
		$this->is_multiple = $is_multiple;
	}

	/**
	 * Check if the selection allows multiple values.
	 *
	 * @return bool True if multiple selections are allowed, false otherwise.
	 */
	public function is_multiple(): bool {
		return $this->is_multiple;
	}

	/**
	 * Get the name of the selection input.
	 *
	 * @return string The name of the selection input.
	 */
	protected function get_name(): string {
		return 'selection';
	}

	/**
	 * Get the data attributes for the selection input.
	 *
	 * @return array The data attributes for the selection input, including configuration,
	 *               properties, and value.
	 */
	protected function get_data_attributes(): array {
		return array(
			'config'     => array(
				'is_multiple'      => $this->is_multiple(),
				'is_required'      => $this->is_required(),
				'data_name'        => $this->get_data_name(),
				'display_name'     => $this->display_name ?? '',
				'ajax_action_name' => $this->ajax_action_name,
			),
			'properties' => $this->properties ?? array(),
			'value'      => $this->get_value(),
		);
	}
}
