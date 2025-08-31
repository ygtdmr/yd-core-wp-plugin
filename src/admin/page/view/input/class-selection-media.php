<?php
/**
 * Selection Media Input UI Component for Admin Pages
 *
 * This file defines the `Selection_Media` class, which renders and manages a
 * *media*-selection control (images **or** videos) inside the WordPress admin
 * interface. The class outputs the markup for the control, handles attribute
 * rendering, and exposes the data required to initialise the JavaScript
 * controller that powers the component.
 *
 * @package    YD\Core
 * @subpackage Admin\Page\View
 * @author     Yigit Demir
 * @since      1.0.0
 * @version    1.0.0
 */

namespace YD\Admin\Page\View;

defined( 'ABSPATH' ) || exit;

/**
 * UI component that renders a media‑selection input in the admin area.
 *
 * Extends the generic `Input` class and overrides the methods required to
 * output the control and its configuration.
 */
final class Selection_Media extends Input {

	/**
	 * Initializes the `Selection_Media` class by setting the data name.
	 *
	 * @param string $data_name The name of the data attribute.
	 */
	public function __construct( string $data_name ) {
		$this->set_data_name( $data_name );
	}

	/**
	 * This method returns the name used for the selection input, which is
	 * used to identify the input field in the HTML.
	 *
	 * @return string The name of the selection input.
	 */
	protected function get_name(): string {
		return 'selection-media';
	}

	/**
	 * Outputs the HTML markup for the media‑selection control.
	 *
	 * The markup consists of a button that either opens the WordPress Media
	 * Library (for choosing an image or a video) or removes the currently
	 * selected attachment, and a hidden <input> element that stores the
	 * selected attachment ID.
	 *
	 * @return void
	 */
	protected function get_content() {
		?>
		<div class="selection-media">
			<span class="button action" tabindex="0" <?php $this->render_attribute_id_input(); ?>>
			<?php
			if ( ( (int) $this->get_value() ) === 0 ) :
				esc_html_e( 'Select media', 'yd-core' );
else :
	esc_html_e( 'Remove media', 'yd-core' );
endif;
?>
</span>
			<input type="hidden" 
			<?php
					$this->render_attribute( 'name', $this->get_data_name() );
					$this->render_attribute( 'value', $this->get_value() );
			?>
				>
		</div>
		<?php
	}

	/**
	 * This method returns an array of data attributes that will be used to
	 * configure the behavior of the selection input, such as whether it is
	 * required or not.
	 *
	 * @return array The data attributes for the selection input.
	 */
	protected function get_data_attributes(): array {
		return array(
			'config' => array(
				'is_required' => $this->is_required(),
			),
		);
	}
}

?>
