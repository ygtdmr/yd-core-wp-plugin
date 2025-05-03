<?php
/**
 * Post and Comment Utility Functions for Content Management
 *
 * This file defines the `Post` class, which provides a set of static methods for retrieving,
 * validating, and formatting post and comment data. It supports fetching post details, comment
 * trees with metadata, and rendering post content for mobile compatibility.
 *
 * @package YD\Core
 * @subpackage Utils
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Post Class for Managing Posts and Comments
 *
 * This class provides a set of functions for retrieving and managing posts and comments,
 * including fetching post details, handling comment information, and rendering post content.
 * It also supports metadata processing for posts and comments.
 */
final class Post {

	/**
	 * Get a post by its ID or slug.
	 *
	 * @param int|string $id The post ID or slug.
	 * @param string     $post_type The type of post (default is 'post').
	 * @return \WP_Post|\WP_Error The post object or WP_Error if not found.
	 */
	public static function get( int|string $id, string $post_type = 'post' ): \WP_Post|\WP_Error {
		$post;
		if ( is_int( $id ) ) {
			$post = get_post( $id );
		} elseif ( is_string( $id ) ) {
			$post = get_page_by_path( $id );
		}
		if ( empty( $post ) || $post->post_type !== $post_type || post_password_required( $post ) || 'publish' !== $post->post_status ) {
			return new \WP_Error( 'invalid_id', sprintf( '%s %s', __( 'Invalid' ), __( 'Id' ) ), array( 'status' => 404 ) );
		}
		return $post;
	}

	/**
	 * Get detailed information about a post.
	 *
	 * @param int|\WP_Post $post The post ID or WP_Post object.
	 * @param bool         $is_summary Whether to return a summary or full content (default is true).
	 * @return array|\WP_Error An array of post details or WP_Error if invalid.
	 */
	public static function get_info( int|\WP_Post $post, bool $is_summary = true ): array|\WP_Error {
		if ( is_int( $post ) ) {
			$post = get_post( $post );
		}

		if ( empty( $post ) || 'post' !== $post->post_type ) {
			return new \WP_Error( 'post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );
		}

		$info = array(
			'id'            => $post->ID,
			'title'         => $post->post_title,
			'categories'    => array_map(
				function ( \WP_Term $term ) {
					return array(
						'id'   => $term->term_id,
						'name' => $term->name,
					);
				},
				// phpcs:ignore Universal.Operators.DisallowShortTernary
				get_the_terms( $post, 'category' ) ?: array()
			),
			'tags'          => array_map(
				function ( \WP_Term $term ) {
					return array(
						'id'   => $term->term_id,
						'name' => $term->name,
					);
				},
				// phpcs:ignore Universal.Operators.DisallowShortTernary
				get_the_terms( $post, 'post_tag' ) ?: array()
			),
			'comment_count' => (int) $post->comment_count,
			'author'        => get_user_by( 'ID', $post->post_author )->display_name,
		);

		if ( ! $is_summary ) {
			$info += array(
				'content' => self::render_content( $post->post_content ?? '', true ),
			);
		}

		return $info;
	}

	/**
	 * Get detailed information about a comment.
	 *
	 * @param int|\WP_Comment $comment The comment ID or WP_Comment object.
	 * @param array           $meta_data Metadata keys to retrieve.
	 * @return array|\WP_Error An array of comment details or WP_Error if invalid.
	 */
	public static function get_comment_info( int|\WP_Comment $comment, array $meta_data = array() ): array|\WP_Error {
		$current_user_id = get_current_user_id();

		if ( is_int( $comment ) ) {
			$comment = get_comment( $comment );
			if ( empty( $comment ) ) {
				return new \WP_Error( 'comment_invalid_id', __( 'Invalid comment ID.' ), array( 'status' => 404 ) );
			}
		}

		$info = array(
			'id'      => (int) $comment->comment_ID,
			'date'    => strtotime( $comment->comment_date_gmt ),
			'author'  => $comment->comment_author,
			'content' => wp_strip_all_tags( $comment->comment_content ),
		);

		if ( (int) $comment->user_id === $current_user_id ) {
			$info += array(
				'approved' => boolval( $comment->comment_approved ),
			);
		}

		foreach ( $meta_data as $key => $value ) {
			$info[ $key ] = get_comment_meta( $comment->comment_ID, $value['meta_key'], $value['is_single'] );
			switch ( $value['type'] ?? '' ) {
				case 'integer':
					$info[ $key ] = (int) $info[ $key ];
					break;
				case 'boolean':
					$info[ $key ] = (bool) $info[ $key ];
			}
		}

		$children = $comment->get_children(
			array(
				'format'             => 'flat',
				'status'             => 'approve',
				'orderby'            => 'date',
				'order'              => 'asc',
				'include_unapproved' => array(
					$current_user_id,
				),
			)
		);

		if ( ! empty( $children ) ) {
			$children = array_filter(
				$children,
				function ( \WP_Comment $sub_comment ) use ( $comment ) {
					return $sub_comment->comment_parent === $comment->comment_ID;
				}
			);

			$info['children'] = array_map(
				function ( \WP_Comment $sub_comment ) use ( $meta_data ) {
					return self::get_comment_info( $sub_comment, $meta_data );
				},
				$children
			);
		}

		return $info;
	}

	/**
	 * Render post content with the option for mobile-specific filters.
	 *
	 * @param string $content The post content.
	 * @param bool   $for_mobile Whether to apply mobile-specific filters (default is false).
	 * @return string The rendered content.
	 */
	public static function render_content( string $content, bool $for_mobile = false ): string {
		$content = apply_filters( 'the_content', $content );
		if ( $for_mobile ) {
			return wp_kses(
				$content,
				array(
					'a'      => array(
						'href' => true,
					),
					'p'      => true,
					'strong' => true,
					'b'      => true,
					'h1'     => true,
					'h2'     => true,
					'h3'     => true,
					'h4'     => true,
					'h5'     => true,
					'h6'     => true,
					'table'  => true,
					'tr'     => true,
					'th'     => true,
					'td'     => true,
					'img'    => array(
						'src' => true,
					),
				)
			);
		}
		return $content;
	}
}
