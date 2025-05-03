<?php
/**
 * WooCommerce Product Management Utilities
 *
 * This file defines the `Product` class, which provides utility methods for managing WooCommerce products,
 * including retrieving product information, handling variations, attributes, and images, and supporting product-related operations
 * like upselling and getting detailed product info with options for summaries and extended details.
 *
 * @package YD\Core
 * @subpackage Utils
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\Utils\WC;

use YD\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Product class provides utility methods for managing WooCommerce products.
 *
 * It includes methods for retrieving product information, handling variations,
 * attributes, images, upselling, and obtaining detailed product info with options
 * for summaries and extended details.
 */
final class Product {

	/**
	 * Retrieves image IDs for product variations.
	 *
	 * @param int $variation_id The variation ID.
	 *
	 * @return array An array of image IDs.
	 */
	private static function get_variation_image_ids( int $variation_id ): array {
		if ( Utils\XTS::is_support_woodmart() ) {
			$parent_id  = wc_get_product( $variation_id )->get_parent_id();
			$variations = get_post_meta( $parent_id, 'woodmart_variation_gallery_data', true );
			return explode( ',', $variations[ $variation_id ] ?? '' );
		}
		return array();
	}

	/**
	 * Maps product attributes to a specific format.
	 *
	 * @param array $attributes An array of WC_Product_Attribute objects.
	 *
	 * @return array An array of formatted attributes.
	 */
	private static function get_attributes( array $attributes ): array {
		return array_map(
			function ( \WC_Product_Attribute $attribute ) {
				return array(
					'name'         => $attribute->get_name(),
					'label'        => wc_attribute_label( $attribute->get_name() ),
					'is_variation' => $attribute->get_variation(),
					'values'       => array_map(
						function ( \WP_Term $term ) {
							return array(
								'slug'  => $term->slug,
								'label' => $term->name,
								'meta'  => array(
									'color'        => get_term_meta( $term->term_id, 'color', true ),
									'not_dropdown' => boolval( get_term_meta( $term->term_id, 'not_dropdown', true ) ),
								),
							);
						},
						$attribute->get_terms() ?? array()
					// phpcs:ignore Universal.Operators.DisallowShortTernary
					) ?: $attribute->get_options(),
				);
			},
			$attributes
		);
	}

	/**
	 * Retrieves product information, either a summary or full details.
	 *
	 * @param int|\WC_Product $product The product ID or WC_Product object.
	 * @param bool            $is_summary Whether to retrieve a summary (default true).
	 *
	 * @return array|\WP_Error The product info, or WP_Error on failure.
	 */
	public static function get_info( int|\WC_Product $product, bool $is_summary = true ): array|\WP_Error {
		if ( is_int( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( empty( $product ) ) {
			return new \WP_Error( 'product_invalid_id', __( 'Invalid product ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$is_variation   = $product instanceof \WC_Product_Variation;
		$is_variable    = $product instanceof \WC_Product_Variable;
		$is_grouped     = $product instanceof \WC_Product_Grouped;
		$parent_product = $is_variation ? wc_get_product( $product->get_parent_id() ) : null;

		$info = array(
			'id'             => $product->get_id(),
			'type'           => $product->get_type(),
			'on_sale'        => $product->is_on_sale(),
			'in_stock'       => $product->is_in_stock(),
			'average_rating' => $product->get_average_rating(),
			'name'           => $product->get_name(),
			'price'          => html_entity_decode(
				wp_strip_all_tags(
					$product->get_price_html()
				)
			),
			'categories'     => array_map(
				function ( int $category_id ) {
					$term = get_term_by( 'id', $category_id, 'product_cat' );
					return array(
						'id'   => $category_id,
						'name' => $term->name,
					);
				},
				$is_variation
				? array()
				: $product->get_category_ids()
			),
			'images'         => array_map(
				function ( string $image_id ) {
					// phpcs:ignore Universal.Operators.DisallowShortTernary
					return wp_get_attachment_url( $image_id ) ?: wc_placeholder_img_src();
				},
				// phpcs:ignore Universal.Operators.DisallowShortTernary
				array( $product->get_image_id() ?: 0 ) + (
					$is_variation
					? self::get_variation_image_ids( $product->get_id() )
					: $product->get_gallery_image_ids()
				)
			),
		);

		if ( ! $is_summary ) {
			$info += array(
				'sku'               => $product->get_sku(),
				'global_unique_id'  => $product->get_global_unique_id(),
				'review_count'      => $product->get_review_count(),
				'description'       => Utils\Post::render_content( $product->get_description(), true ),
				'short_description' => Utils\Post::render_content( $product->get_short_description(), true ),
				'reviews_allowed'   => $product->get_reviews_allowed(),
				'upsells'           => array_map(
					function ( int $id ) {
						return self::get_info( $id );
					},
					$product->get_upsell_ids()
				),
				'weight'            => $product->get_weight(),
				'dimensions'        => array(
					'length' => $product->get_length(),
					'width'  => $product->get_width(),
					'height' => $product->get_height(),
				),
				'attributes'        => $is_variation
				? $product->get_attributes()
				: self::get_attributes(
					array_filter( array_values( $product->get_attributes() ), 'wc_attributes_array_filter_visible' )
				),
			);

			if ( $is_variable ) {
				$info += array(
					'variations' => array_map(
						function ( \WC_Product_Variation $variation ) {
							$variation_info = Utils\Main::sanitize_array_by_keys(
								self::get_info( $variation, false ),
								array(
									'id',
									'sku',
									'global_unique_id',
									'attributes',
									'price',
									'on_sale',
									'in_stock',
									'images',
								)
							);
							return $variation_info;
						},
						$product->get_available_variations( 'objects' )
					),
				);
			}
		}

		return $info;
	}
}
