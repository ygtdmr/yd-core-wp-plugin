<?php
/**
 * Dropdown Input UI Component for Admin Page
 *
 * This file defines the `Dropdown` class, which handles the rendering and management of dropdown select input fields within the WordPress admin interface.
 * It includes methods for setting options, retrieving the selected value, and handling data attributes for the dropdown. It also renders the dropdown field with the provided options.
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
 * Dropdown class represents a dropdown input field with a set of options.
 * It extends the base `Input` class and provides functionality specific to dropdowns,
 * such as handling options and generating the appropriate data attributes.
 */
final class Dropdown extends Input {
	/**
	 * List of options for the dropdown.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Constructor for the Dropdown class.
	 *
	 * @param string $data_name The name for the data attribute.
	 * @param array  $options   The list of options for the dropdown.
	 */
	public function __construct( string $data_name, array $options ) {
		$this->set_data_name( $data_name );
		$this->options = $options;
	}

	/**
	 * Get the name of the input field.
	 *
	 * @return string The name of the input field, which is 'dropdown'.
	 */
	protected function get_name(): string {
		return 'dropdown';
	}

	/**
	 * Get the data attributes for the dropdown input field.
	 *
	 * @return array The array of data attributes, including 'config' and 'value'.
	 */
	protected function get_data_attributes(): array {
		return array(
			'config' => array(
				'options'   => $this->options,
				'data_name' => $this->get_data_name(),
			),
			'value'  => $this->get_value(),
		);
	}

	/**
	 * Get the value of the dropdown field.
	 *
	 * If a value is set, it will return it. Otherwise, it will return the first option.
	 *
	 * @return string The selected value, or the first option if none is set.
	 */
	public function get_value(): string {
		return parent::get_value() ?? array_key_first( $this->options );
	}
}
