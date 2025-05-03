/**
 * YD_Input_Selection_Media
 *
 * A reusable media‑selection component for the WP‑Admin interface.
 *
 * Lets editors **select** or **remove** a single image or video from the
 * WordPress Media Library.
 *
 * Provides an instant preview of the chosen attachment and emits custom
 * events so outer code can react (`yd-on-media-load`, `yd-on-media-change`).
 *
 * Supports a **required** flag that enforces validation by inserting a
 * hidden input while no media is selected.
 *
 * When an attachment‑ID is already present (saved previously) it fetches
 * the corresponding medium–sized thumbnail via AJAX and renders a preview
 * on initialisation.
 *
 * Author:  Yigit Demir
 * Version: 1.0.0
 * Since:   1.0.0
 */

"use strict";

class YD_Input_Selection_Media {
  /**
   * WordPress AJAX action used to retrieve attachment metadata.
   *
   * @type {string}
   */
  #ajaxActionName = "url-media";

  /**
   * Root DOM element of the media selection input
   *
   * @type {jQuery}
   */
  #rootDom;

  /**
   * Configuration object passed to the input
   *
   * @type {Object}
   */
  #config;

  /**
   * DOM element for the image tag
   *
   * @type {jQuery}
   */
  #imgDom;

  /**
   * DOM element for the video tag
   *
   * @type {jQuery}
   */
  #videoDom;

  /**
   * DOM element for the action button
   *
   * @type {jQuery}
   */
  #buttonAction;

  /**
   * DOM element for the hidden input that stores the selected media ID
   *
   * @type {jQuery}
   */
  #inputValue;

  /**
   * Creates an instance of YD_Input_Selection_Media.
   *
   * @param {HTMLElement|string} rootDom - Root DOM element or selector
   * @param {string} config - JSON string of configuration options
   */
  constructor(rootDom, config) {
    this.#rootDom = jQuery(rootDom).find(".selection-media");
    this.#config = JSON.parse(config);

    this.#imgDom = jQuery('<img class="media"/>');
    this.#videoDom = jQuery(
      '<video class="media" autoplay="1" loop="1" muted="1"/>',
    );
    this.#buttonAction = this.#rootDom.find(".button.action");
    this.#inputValue = this.#rootDom.find('input[type="hidden"]');

    this.#loadEvents();
    this.#checkRequiredInput();

    if (parseInt(this.#inputValue.val()) > 0) {
      this.#buttonAction.text(window.yd_core.ui.getText("Remove media"));

      const spinner = jQuery('<div class="spinner is-active"></div>');
      this.#rootDom.prepend(spinner);

      window.yd_core.action.runAjax(
        (data) => {
          spinner.remove();
          switch (data.type) {
            case "image":
              this.#imgDom.attr("src", data.url);
              this.#rootDom.prepend(this.#imgDom);
              this.#rootDom.trigger("yd-on-media-load", this.#imgDom);
              break;
            case "video":
              this.#videoDom.attr("src", data.url);
              this.#rootDom.prepend(this.#videoDom);
              this.#rootDom.trigger("yd-on-media-load", this.#videoDom);
              break;
          }
        },
        this.#ajaxActionName,
        { id: this.#inputValue.val(), size: "medium" },
      );
    }
  }

  /**
   * Returns the root DOM element
   *
   * @returns {jQuery}
   */
  getRootDom() {
    return this.#rootDom;
  }

  /**
   * Binds event handlers for the media selection input
   *
   * @private
   */
  #loadEvents() {
    this.#buttonAction.on("click", (e) => {
      if (parseInt(this.#inputValue.val()) > 0) {
        this.#buttonAction.text(window.yd_core.ui.getText("Select media"));

        this.#imgDom.remove();
        this.#videoDom.remove();
        this.#inputValue.val("0").trigger("input");
        this.#rootDom.trigger("yd-on-media-change");

        this.#checkRequiredInput();
      } else {
        const frame = window.wp.media({
          multiple: false,
          library: { type: ["image", "video"] },
        });

        frame.on("select", () => {
          const attachment = frame.state().get("selection").first().toJSON();

          switch (attachment.type) {
            case "image":
              this.#imgDom.attr(
                "src",
                attachment.sizes?.medium?.url ?? attachment.sizes.full.url,
              );
              this.#rootDom.prepend(this.#imgDom);
              this.#rootDom.trigger("yd-on-media-change", this.#imgDom);
              break;
            case "video":
              this.#videoDom.attr("src", attachment.url);
              this.#rootDom.prepend(this.#videoDom);
              this.#rootDom.trigger("yd-on-media-change", this.#videoDom);
              break;
          }

          this.#buttonAction.text(window.yd_core.ui.getText("Remove media"));
          this.#inputValue.val(attachment.id).trigger("input");

          this.#checkRequiredInput();
        });

        frame.open();
      }

      e.preventDefault();
    });
  }

  /**
   * Adds or removes a required input field depending on current selection
   *
   * @private
   */
  #checkRequiredInput() {
    if (this.#config.is_required) {
      if (parseInt(this.#inputValue.val()) > 0) {
        this.#rootDom.find("input[required]").remove();
      } else {
        this.#rootDom.append(
          '<input type="text" required="" onkeypress="return false;" tabindex="-1" />',
        );
      }
    }
  }
}
