<?php
/**
 * Data Manager Utility for Input Sanitization and Validation
 *
 * This file defines the `Data_Manager` class, which handles input sanitization, validation,
 * and rule-based data processing. It supports complex rule definitions for various data types,
 * including objects, arrays, enums, and primitive types. It also includes utility methods for
 * encoding, decoding, and file operations used across the application.
 *
 * @package YD\Core
 * @subpackage Data
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD;

defined( 'ABSPATH' ) || exit;

/**
 * Data_Manager Class
 *
 * This class provides functionality for sanitizing and validating input data based on rules.
 * It supports complex data types and provides utility methods for encoding, decoding,
 * and handling file operations.
 */
final class Data_Manager {
	/**
	 * The rules for sanitizing and validating data.
	 *
	 * @var array
	 */
	private $rules;

	/**
	 * The input data to be sanitized and validated.
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Data_Manager constructor.
	 *
	 * Initializes the class with provided rules and data.
	 *
	 * @param array $rules The rules for sanitization and validation.
	 * @param array $data The data to be sanitized and validated.
	 */
	public function __construct( array $rules, array $data ) {
		$this->rules = $rules;
		$this->data  = $data;
	}

	/**
	 * Sanitizes the input data based on the defined rules.
	 *
	 * Loops through the data and applies the appropriate sanitization and validation
	 * logic based on the defined rules.
	 *
	 * @return array The sanitized data.
	 * @throws \Exception If a rule is invalid or required data is missing.
	 */
	public function sanitize(): array {
		if ( empty( $this->data ) ) {
			return array();
		}

		$sanitized_data = empty( $this->rules ) ? $this->data : array();

		foreach ( $this->rules as $rule_key => $rule_value ) {
			$type       = $rule_value['type'] ?? 'string';
			$required   = $rule_value['required'] ?? false;
			$data_value = $this->data[ $rule_key ] ?? $rule_value['default'] ?? null;

			if ( ! empty( $rule_value['pass'] ) ) {
				$sanitized_data[ $rule_key ] = $data_value;
				continue;
			}

			if ( '*' === $rule_key ) {
				foreach ( array_keys( $this->data ) as $key ) {
					if ( '*' !== $key && ! array_key_exists( $key, $this->rules ) ) {
						if ( ! empty( $rule_value['key_rule'] ) ) {
							$old_key = $key;

							$key = self::sanitize_single( $rule_value['key_rule'], $key );

							if ( $key !== $old_key ) {
								$old_key_data = $this->data[ $old_key ];
								unset( $this->data[ $old_key ] );
								$this->data[ $key ] = $old_key_data;
							}
						}
						$sanitized_data += ( new self( array( $key => $rule_value ), $this->data ) )->sanitize();
					}
				}
				continue;
			}

			if ( ! in_array( $type, $this->get_types(), true ) ) {
				throw new \Exception(
					sprintf(
						'[type] is not none of %s. Current type is: %s',
						esc_html( join( ', ', $this->get_types() ) ),
						esc_html( $type )
					)
				);
			}

			if ( ! array_key_exists( $rule_key, $this->data ) || ( ! is_bool( $data_value ) && ! is_numeric( $data_value ) && empty( $data_value ) ) ) {
				if ( $required ) {
					if ( null === $rule_value['default'] ) {
						throw new \Exception(
							sprintf(
								'[%s] is required and should define [default] rule.',
								esc_html( $rule_key )
							)
						);
					} else {
						$data_value = $rule_value['default'];
					}
				} elseif ( ! empty( $rule_value['enable_empty_value'] ) ) {
					$data_value = '';
				} else {
					continue; }
			}

			$sanitized_value;

			if ( ! empty( $rule_value['sanitize_raw_callback'] ) ) {
				$data_value = $rule_value['sanitize_raw_callback']( $data_value );
			}

			if ( ! empty( $rule_value['sanitize_callback'] ) ) {
				$sanitized_value = $rule_value['sanitize_callback']( $data_value );
			} else {
				switch ( $type ) {
					case 'string':
						if ( ! empty( $rule_value['is_textarea'] ) ) {
							$sanitized_value = sanitize_textarea_field( $data_value );
						} else {
							$sanitized_value = sanitize_text_field( $data_value );
						}
						$sanitized_value = stripslashes( $sanitized_value );

						if ( ! empty( $rule_value['pattern_replace'] ) ) {
							foreach ( $rule_value['pattern_replace'] as $item ) {
								$sanitized_value = preg_replace( $item['pattern'], $item['replacement'], $sanitized_value );
							}
						}

						if ( ! empty( $rule_value['sanitize_length'] ) ) {
							$max_length = $rule_value['sanitize_length'];

							$sanitized_value = substr( $sanitized_value, 0, $max_length );
						}

						$pattern_match = $rule_value['pattern_match'] ?? false;

						if ( $pattern_match && ! preg_match( $pattern_match, $sanitized_value ) ) {
							$sanitized_value = $rule_value['default'];
						}

						break;
					case 'enum':
						$values = $rule_value['values'];
						if ( in_array( $data_value, $values, true ) ) {
							$sanitized_value = $data_value;
						} else {
							$sanitized_value = null;
						}
						break;
					case 'integer':
					case 'double':
						$sanitized_value = 'integer' === $type ? (int) $data_value : (float) $data_value;

						$min = $rule_value['min'] ?? false;
						$max = $rule_value['max'] ?? false;

						if ( ( false !== $min && $sanitized_value < $min ) || ( false !== $max && $sanitized_value > $max ) ) {
							$sanitized_value = $rule_value['default'];
						}
						break;
					case 'boolean':
						$sanitized_value = filter_var( $data_value, FILTER_VALIDATE_BOOLEAN );
						break;
					case 'array':
					case 'object':
						$value_length = count( (array) $data_value ?? array() );

						if ( ! $value_length ) {
							$sanitized_value = array();
							break;
						}

						$rules = $rule_value['rules'] ?? array();

						if ( 'object' === $type ) {
							$rules_from_keys = $rule_value['rules_from_keys'] ?? array();

							if ( ! empty( $rules_from_keys ) ) {
								$current_key = $this->data[ $rules_from_keys['target_key'] ];
								$rules       = $rules_from_keys['rules'][ $current_key ];
							}

							$sanitized_value = ( new self( $rules, (array) $data_value ) )->sanitize();
							break;
						}

						if ( ! empty( $rule_value['item_rules'] ) ) {
							$rules = array_combine(
								range( 0, $value_length - 1 ),
								array_map(
									function () use ( $rule_value ) {
										return $rule_value['item_rules'];
									},
									$data_value
								)
							);

							$sanitized_value = ( new self( $rules, (array) $data_value ) )->sanitize();
						} else {
							$sanitized_value = array_map(
								function ( $item ) use ( $rules ) {
									return ( new self( $rules, (array) $item ) )->sanitize();
								},
								$data_value
							);
						}

						break;
				}
			}

			if ( ! empty( $rule_value['after_sanitize_callback'] ) ) {
				$sanitized_value = $rule_value['after_sanitize_callback']( $data_value );
			}

			if ( ! $required && ! isset( $sanitized_value ) ) {
				continue; }

			if ( ! empty( $rule_value['hide_by_condition'] ) ) {
				$is_match_all = true;
				foreach ( $rule_value['hide_by_condition'] as $condition_key => $condition_value ) {
					$sanitized_target_value = self::sanitize_single( $this->rules[ $condition_key ], $this->data[ $condition_key ] );

					if ( $sanitized_target_value !== $condition_value ) {
						$is_match_all = false;
					}
				}
				if ( $is_match_all ) {
					continue;
				}
			}

			$sanitized_data[ $rule_key ] = $sanitized_value;
		}

		return $sanitized_data;
	}

	/**
	 * Returns the rules used for sanitization and validation.
	 *
	 * @return array The rules for sanitization.
	 */
	public function get_rules(): array {
		return $this->rules;
	}

	/**
	 * Returns an array of supported data types.
	 *
	 * @return array List of supported data types.
	 */
	private function get_types(): array {
		return array(
			'string',
			'integer',
			'double',
			'boolean',
			'array',
			'object',
			'enum',
		);
	}

	/**
	 * Sanitizes a single value based on the provided rule.
	 *
	 * @param array $rule_value The sanitization rule.
	 * @param mixed $value The value to be sanitized.
	 *
	 * @return mixed The sanitized value.
	 */
	public static function sanitize_single( array $rule_value, mixed $value ): mixed {
		return ( new self( array( $rule_value ), array( $value ) ) )->sanitize()[0];
	}

	/**
	 * Extracts a specific key from an array and returns it as an array.
	 *
	 * @param string $key The key to extract.
	 * @param array  $data The array to extract from.
	 *
	 * @return array The extracted key-value pair, or an empty array if not found.
	 */
	public static function array_property( string $key, array $data ): array {
		$value = $data[ $key ] ?? null;
		return null === $value ? array() : array( $key => $value );
	}

	/**
	 * Encodes data into a base64 string after JSON encoding it.
	 *
	 * @param array $data The data to be encoded.
	 *
	 * @return string The base64 encoded string.
	 */
	public static function encode( array $data ): string {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		return base64_encode( wp_json_encode( $data, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE ) );
	}

	/**
	 * Decodes a base64 encoded string back into an array.
	 *
	 * @param string|null $data The base64 encoded string to decode.
	 *
	 * @return array The decoded data as an array.
	 */
	public static function decode( ?string $data ): array {
		if ( empty( $data ) ) {
			return array();
		}
		// phpcs:ignore Universal.Operators.DisallowShortTernary, WordPress.PHP.DiscouragedPHPFunctions
		return json_decode( base64_decode( $data ) ?: '{}', true ) ?? array();
	}

	/**
	 * Reads a file from the request based on the provided file key.
	 *
	 * @param string $file_key The key for the file in the $_FILES array.
	 *
	 * @return string|null The file contents, or null if the file could not be read.
	 */
	public static function read_file_from_request( string $file_key ): ?string {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification
		$file = $_FILES[ $file_key ] ?? null;
		if ( $file ) {
			if ( $file['error'] ) {
				return null;
			}
			return self::read_file( $file['tmp_name'] );
		}
		return null;
	}

	/**
	 * Reads the contents of a file from a given file path.
	 *
	 * @param string $file_path The path to the file to read.
	 *
	 * @return string|null The file contents, or null if the file could not be read.
	 */
	public static function read_file( string $file_path ): ?string {
		// phpcs:disable WordPress.WP.AlternativeFunctions
		// phpcs:ignore WordPress.PHP.NoSilencedErrors
		$fp = @fopen( $file_path, 'r' );
		if ( $fp ) {
			$data = fread( $fp, filesize( $file_path ) );
			if ( $data ) {
				fclose( $fp );
				return $data;
			}
			fclose( $fp );
		}
		// phpcs:enable WordPress.WP.AlternativeFunctions
		return null;
	}

	/**
	 * Writes data to a file at a specified path.
	 *
	 * @param string $file_path The path to the file where data will be written.
	 * @param string $data The data to write to the file.
	 * @param bool   $is_append Whether to append to the file (true) or overwrite (false).
	 * @return void
	 */
	public static function write_file( string $file_path, string $data, bool $is_append = false ) {
		// phpcs:disable WordPress.WP.AlternativeFunctions
		// phpcs:ignore WordPress.PHP.NoSilencedErrors
		$fp = @fopen( $file_path, $is_append ? 'a' : 'w' );
		if ( $fp ) {
			fwrite( $fp, $data );
			fclose( $fp );
		}
		// phpcs:enable WordPress.WP.AlternativeFunctions
	}
}
