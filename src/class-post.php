<?php
/**
 * Abstract Post Type Handler for Mobile App
 *
 * This file defines the abstract `Post` class, which provides a framework for creating
 * and managing custom post types in the WordPress admin. It supports dynamic labels,
 * custom meta boxes, column rendering, filtering, and post save hooks, enabling
 * extensive customization of post behavior and UI.
 *
 * @package YD\Core
 * @subpackage Post
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD;

use YD\Admin\Page;
use YD\Utils;
use YD\Data_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract Post Class
 *
 * This abstract class defines the structure for a custom post type in WordPress.
 * It includes methods for registering the post type, handling custom columns, managing meta boxes,
 * and saving data related to the post type.
 */
abstract class Post {

	/**
	 * Post constructor.
	 *
	 * Registers the post type and hooks various actions and filters related to the post type.
	 */
	public function __construct() {
		$text_domain = $this->get_text_domain();
		// phpcs:ignore WordPress.WP.I18n
		$label_singular = __( $this->get_label_singular(), $text_domain );
		// phpcs:ignore WordPress.WP.I18n
		$label_plural = __( $this->get_label_plural(), $text_domain );

		register_post_type(
			$this->get_type(),
			array_merge(
				$this->get_args(),
				array(
					'labels' => array(
						'name'                     => $label_plural,
						'singular_name'            => $label_singular,

						'add_new'                  => sprintf(
							// translators: %s is the singular name of the item (e.g., "block", "section").
							__( 'Add new %s', 'yd-core' ),
							$label_singular
						),
						'add_new_item'             => sprintf(
							// translators: %s is the singular name of the item being added.
							__( 'Add new %s', 'yd-core' ),
							$label_singular
						),
						'edit_item'                => sprintf(
							// translators: %s is the singular name of the item being edited.
							__( 'Edit %s', 'yd-core' ),
							$label_singular
						),
						'new_item'                 => sprintf(
							// translators: %s is the singular name of the new item.
							__( 'New %s', 'yd-core' ),
							$label_singular
						),
						'view_item'                => sprintf(
							// translators: %s is the singular name of the item being viewed.
							__( 'View %s', 'yd-core' ),
							$label_singular
						),
						'view_items'               => sprintf(
							// translators: %s is the plural name of the items being viewed.
							__( 'View %s', 'yd-core' ),
							$label_plural
						),
						'search_items'             => sprintf(
							// translators: %s is the plural name of the items being searched.
							__( 'Search %s', 'yd-core' ),
							$label_plural
						),
						'not_found'                => sprintf(
							// translators: %s is the plural name of the items not found.
							__( 'No %s found.', 'yd-core' ),
							$label_plural
						),
						'not_found_in_trash'       => sprintf(
							// translators: %s is the plural name of the items not found in trash.
							__( 'No %s found in Trash.', 'yd-core' ),
							$label_plural
						),
						'all_items'                => sprintf(
							// translators: %s is the plural name of all items.
							__( 'All %s', 'yd-core' ),
							$label_plural
						),
						'archives'                 => sprintf(
							// translators: %s is the singular name of the item for archives.
							__( '%s Archives', 'yd-core' ),
							$label_singular
						),
						'attributes'               => sprintf(
							// translators: %s is the singular name of the item for attributes.
							__( '%s Attributes', 'yd-core' ),
							$label_singular
						),
						'insert_into_item'         => sprintf(
							// translators: %s is the singular name of the item being inserted into.
							__( 'Insert into %s', 'yd-core' ),
							$label_singular
						),
						'uploaded_to_this_item'    => sprintf(
							// translators: %s is the singular name of the item to which files were uploaded.
							__( 'Uploaded to this %s', 'yd-core' ),
							$label_singular
						),
						'filter_items_list'        => sprintf(
							// translators: %s is the plural name of the items being filtered.
							__( 'Filter %s list', 'yd-core' ),
							$label_plural
						),
						'items_list_navigation'    => sprintf(
							// translators: %s is the plural name of the items in the navigation.
							__( '%s list navigation', 'yd-core' ),
							$label_plural
						),
						'items_list'               => sprintf(
							// translators: %s is the plural name of the items in the list.
							__( '%s list', 'yd-core' ),
							$label_plural
						),
						'item_published'           => sprintf(
							// translators: %s is the singular name of the item that was published.
							__( '%s published.', 'yd-core' ),
							$label_singular
						),
						'item_published_privately' => sprintf(
							// translators: %s is the singular name of the item that was published privately.
							__( '%s published privately.', 'yd-core' ),
							$label_singular
						),
						'item_reverted_to_draft'   => sprintf(
							// translators: %s is the singular name of the item reverted to draft.
							__( '%s reverted to draft.', 'yd-core' ),
							$label_singular
						),
						'item_trashed'             => sprintf(
							// translators: %s is the singular name of the item that was trashed.
							__( '%s trashed.', 'yd-core' ),
							$label_singular
						),
						'item_scheduled'           => sprintf(
							// translators: %s is the singular name of the item that was scheduled.
							__( '%s scheduled.', 'yd-core' ),
							$label_singular
						),
						'item_updated'             => sprintf(
							// translators: %s is the singular name of the item that was updated.
							__( '%s updated.', 'yd-core' ),
							$label_singular
						),
					),
				)
			)
		);

		add_action(
			'current_screen',
			function ( \WP_Screen $screen ) {
				if ( $screen->post_type !== $this->get_type() ) {
					return;
				}

				if ( $this->is_disable_view_type() ) {
					Utils\Main::add_filter(
						'view_mode_post_types',
						function ( $post_types ) {
							unset( $post_types[ $this->get_type() ] );
							return $post_types;
						}
					);
				}

				Utils\Main::add_filter(
					'pre_get_posts',
					function ( $query ) {
						if ( ( $query->query_vars['post_type'] ?? false ) === $this->get_type() ) {
							return $this->pre_get_posts( $query );
						}
						return $query;
					}
				);

				Utils\Main::add_filter(
					'manage_' . $this->get_type() . '_posts_columns',
					function ( $columns ) {
						return $this->get_column_title( $columns );
					}
				);

				Utils\Main::add_action(
					'manage_' . $this->get_type() . '_posts_custom_column',
					function ( $column_name, $post_id ) {
						$this->manage_column( $column_name, $post_id );
					},
					10,
					2
				);

				Utils\Main::add_filter(
					'post_updated_messages',
					function ( array $messages ) {
						$type        = $this->get_type();
						$text_domain = $this->get_text_domain();
						// phpcs:ignore WordPress.WP.I18n
						$label_singular = __( $this->get_label_singular(), $text_domain );
						// phpcs:ignore WordPress.WP.I18n
						$label_plural = __( $this->get_label_plural(), $text_domain );

						// translators: %s refers to the name of the item to be added.
						$msg_updated = sprintf( __( '%s updated.', 'yd-core' ), $label_singular );
						// translators: %s refers to the name of the item to be added.
						$msg_published = sprintf( __( '%s published.', 'yd-core' ), $label_singular );
						// translators: %s refers to the name of the item to be added.
						$msg_scheduled = sprintf( __( '%s scheduled.', 'yd-core' ), $label_singular );
						// translators: %s refers to the name of the item to be added.
						$view_posts_link_html = sprintf( ' <a href="/wp-admin/edit.php?post_type=%s">%s</a>', esc_attr( $type ), esc_html( sprintf( __( 'View %s', 'yd-core' ), $label_plural ) ) );

						$messages[ $type ][1]  = $msg_updated . $view_posts_link_html;
						$messages[ $type ][4]  = $msg_updated . $view_posts_link_html;
						$messages[ $type ][6]  = $msg_published . $view_posts_link_html;
						$messages[ $type ][9]  = $msg_scheduled . $view_posts_link_html;
						$messages[ $type ][10] = $msg_published . $view_posts_link_html;

						return $messages;
					}
				);

				Utils\Main::add_action(
					'edit_form_after_title',
					function ( \WP_Post $post ) {
						if ( $post->post_type !== $this->get_type() ) {
							return;
						}

						Page::load_assets();
						$this->callback_edit( $post );
					}
				);

				Utils\Main::add_action(
					'after_delete_post',
					function ( int $post_id ) {
						$this->after_delete( $post_id );
					}
				);

				Utils\Main::add_action(
					'add_meta_boxes',
					function ( string $post_type, \WP_Post $post ) {
						if ( $post_type !== $this->get_type() ) {
							return;
						}
						foreach ( $this->get_meta_boxes( $post ) as $meta_box_args ) {
							add_meta_box(
								$meta_box_args['id'],
								$meta_box_args['title'],
								$meta_box_args['callback'],
								$this->get_type(),
								$meta_box_args['context'] ?? 'advanced'
							);
						}
					},
					10,
					2
				);
			}
		);

		$action_save_post_name     = 'save_post_' . $this->get_type();
		$action_save_post_callback = function ( $_, \WP_Post $post ) use ( $action_save_post_name, &$action_save_post_callback ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			if ( empty( $_POST ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
				return;
			}

			Utils\Main::add_filter(
				'manage_' . $this->get_type() . '_posts_columns',
				function ( $columns ) {
					return $this->get_column_title( $columns );
				}
			);

			Utils\Main::add_action(
				'manage_' . $this->get_type() . '_posts_custom_column',
				function ( $column_name, $post_id ) {
					$this->manage_column( $column_name, $post_id );
				},
				10,
				2
			);

			$is_new = empty( $post->post_content );
			// phpcs:ignore WordPress.Security.NonceVerification
			$data = ( new Data_Manager( $this->get_rules(), $_POST ) )->sanitize();

			$post_data = $post->to_array();
			$post_data = $this->save_data( $data, $post_data, $is_new );

			remove_action( $action_save_post_name, $action_save_post_callback );

			wp_update_post( $post_data );
			$this->save_meta_data( $data, $post, $is_new );

			add_action( $action_save_post_name, $action_save_post_callback, 10, 2 );
		};

		add_action( $action_save_post_name, $action_save_post_callback, 10, 2 );
	}

	/**
	 * Returns the post type slug.
	 *
	 * @return string The post type slug.
	 */
	abstract protected function get_slug(): string;

	/**
	 * Returns the singular label for the post type.
	 *
	 * @return string The singular label.
	 */
	abstract protected function get_label_singular(): string;

	/**
	 * Returns the plural label for the post type.
	 *
	 * @return string The plural label.
	 */
	abstract protected function get_label_plural(): string;

	/**
	 * Returns the text domain for translation.
	 *
	 * @return string The text domain.
	 */
	abstract protected function get_text_domain(): string;

	/**
	 * Returns the arguments for registering the post type.
	 *
	 * @return array The arguments for the post type registration.
	 */
	abstract protected function get_args(): array;

	/**
	 * Callback function for editing a post.
	 *
	 * This method can be overridden in child classes to implement custom behavior
	 * for editing a post of this custom post type.
	 *
	 * @param \WP_Post $post The current post object being edited.
	 * @return void
	 */
	protected function callback_edit( \WP_Post $post ) {}

	/**
	 * Save the custom data for a post.
	 *
	 * This method is used to save any custom data to the post's metadata. It can be
	 * overridden in child classes to handle specific data saving behavior.
	 *
	 * @param array $data     The sanitized data to save.
	 * @param array $post_data The post data array.
	 * @param bool  $is_new   Whether the post is being created or updated.
	 *
	 * @return array The modified post data to save.
	 */
	protected function save_data( /* phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter */ array $data, array $post_data, bool $is_new ): array {
		return array(); }

	/**
	 * Save the metadata for a post.
	 *
	 * This method is used to save custom metadata for a post. It can be overridden
	 * in child classes to handle custom meta fields.
	 *
	 * @param array    $data     The sanitized data.
	 * @param \WP_Post $post    The current post object.
	 * @param bool     $is_new   Whether the post is new.
	 * @return void
	 */
	protected function save_meta_data( array $data, \WP_Post $post, bool $is_new ) {}

	/**
	 * Handle actions after a post is deleted.
	 *
	 * This method is used to perform custom actions when a post is deleted. It
	 * can be overridden in child classes to implement specific behavior.
	 *
	 * @param int $post_id The ID of the post that was deleted.
	 * @return void
	 */
	protected function after_delete( int $post_id ) {}

	/**
	 * Manage the custom columns for the post list table.
	 *
	 * This method allows for customization of the columns displayed in the WordPress
	 * post list table. It can be overridden in child classes to define custom columns.
	 *
	 * @param string $column_name The name of the column to manage.
	 * @param int    $post_id    The ID of the post being displayed.
	 * @return void
	 */
	protected function manage_column( string $column_name, int $post_id ) {}

	/**
	 * Get the titles for the custom columns in the post list table.
	 *
	 * This method defines the column titles for the custom post type. It can be overridden
	 * in child classes to define custom column titles.
	 *
	 * @param array $columns The current set of columns.
	 *
	 * @return array The modified array of column titles.
	 */
	protected function get_column_title( /* phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter */ array $columns ): array {
		return array(); }

	/**
	 * Get the validation rules for the post data.
	 *
	 * This method defines the validation rules for post data. It can be overridden
	 * in child classes to specify custom validation rules.
	 *
	 * @return array The array of validation rules.
	 */
	protected function get_rules(): array {
		return array(); }

	/**
	 * Get the meta boxes to display on the post edit screen.
	 *
	 * This method defines the meta boxes that should be displayed for the custom
	 * post type. It can be overridden in child classes to define custom meta boxes.
	 *
	 * @param \WP_Post $post The current post object.
	 *
	 * @return array The array of meta box arguments.
	 */
	protected function get_meta_boxes( /* phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter */ \WP_Post $post ): array {
		return array(); }

	/**
	 * Modify the query before it retrieves posts.
	 *
	 * This method is used to modify the WP_Query before it retrieves posts for the custom
	 * post type. It can be overridden in child classes to adjust the query.
	 *
	 * @param \WP_Query $query The WP_Query object.
	 *
	 * @return \WP_Query The modified query.
	 */
	protected function pre_get_posts( \WP_Query $query ): \WP_Query {
		return $query; }

	/**
	 * Determine whether the view type for this post should be disabled.
	 *
	 * This method checks whether the view type for the custom post type should be
	 * disabled in the WordPress admin interface. It can be overridden in child classes.
	 *
	 * @return bool True if the view type should be disabled, false otherwise.
	 */
	protected function is_disable_view_type(): bool {
		return true; }

	/**
	 * Get the post type string.
	 *
	 * This method returns the post type string, prefixed with 'yd_' and the custom
	 * slug defined for the post type.
	 *
	 * @return string The custom post type string.
	 */
	public function get_type(): string {
		return 'yd_' . $this->get_slug();
	}
}
