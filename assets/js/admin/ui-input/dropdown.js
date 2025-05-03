/**
 * YD_Input_Dropdown class
 * A customizable and accessible dropdown input UI component designed for dynamic option rendering, keyboard navigation,
 * and seamless value selection. It utilizes jQuery for DOM manipulation and event handling.
 *
 * Author: Yigit Demir
 * Since: 1.0.0
 * Version: 1.0.0
 */

"use strict";

class YD_Input_Dropdown {
  /**
   * Root DOM element of the dropdown input
   *
   * @type {jQuery}
   */
  #rootDom;

  /**
   * Configuration object passed to the component
   *
   * @type {Object}
   */
  #config;

  /**
   * Currently selected value
   *
   * @type {string}
   */
  #value;

  /**
   * DOM element of the dropdown container
   *
   * @type {jQuery}
   */
  #dropdownDom;

  /**
   * DOM element for the optional description
   *
   * @type {jQuery}
   */
  #descriptionDom;

  /**
   * DOM element for the option list
   *
   * @type {jQuery}
   */
  #list;

  /**
   * Hidden input element storing the selected value
   *
   * @type {jQuery}
   */
  #input;

  /**
   * Display element showing the selected option name
   *
   * @type {jQuery}
   */
  #displayName;

  /**
   * Creates an instance of YD_Input_Dropdown.
   *
   * @param {HTMLElement|string} rootDom - Root DOM element or selector
   * @param {string} config - JSON string of configuration
   * @param {string} [value=""] - Selected value
   */
  constructor(rootDom, config, value = "") {
    this.#rootDom = jQuery(rootDom);
    this.#config = JSON.parse(config);
    this.#value = value;

    this.#config.options = Object.entries(this.#config.options);

    this.#render();
    this.#loadEvents();
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
   * Returns the hidden input element
   *
   * @returns {jQuery}
   */
  getInput() {
    return this.#input;
  }

  /**
   * Binds event listeners for keyboard and mouse interaction
   *
   * @private
   */
  #loadEvents() {
    const changeValueBySelected = () => {
      const selectedItem = this.#list.find("[data-hover]");

      this.#list.find("[data-selected]").removeAttr("data-selected");
      selectedItem.attr("data-selected", "");

      this.#input.attr("value", selectedItem.attr("data-value"));
      this.#input.trigger("input");
      this.#input.trigger("change");
      this.#displayName.text(selectedItem.text());
    };

    this.#dropdownDom
      .on("click keyup keydown", (e) => {
        if (e.type === "keydown" && [13, 32].includes(e.keyCode)) return false;

        if (e.type === "keydown" && [38, 40].includes(e.keyCode)) {
          const hoverListItem = this.#list.find("li[data-hover]");
          const targetListItem =
            e.keyCode === 38 ? hoverListItem.prev() : hoverListItem.next();

          if (targetListItem.length > 0) {
            hoverListItem.removeAttr("data-hover");
            targetListItem.attr("data-hover", "");

            const targetPositionTop = targetListItem.position().top;
            const allItemsListHeight = this.#list.height();

            if (
              targetPositionTop > allItemsListHeight ||
              targetPositionTop < 0
            ) {
              this.#list.scrollTop(this.#list.scrollTop() + targetPositionTop);
            }
          }
        } else {
          if (
            ["keyup", "keydown"].includes(e.type) &&
            ![13, 32].includes(e.keyCode)
          )
            return true;

          if (this.#dropdownDom.hasClass("focused")) {
            this.#dropdownDom.removeClass("focused");
            this.#list.hide();
          } else {
            this.#list.find("[data-hover]").removeAttr("data-hover");

            const selectedItem = this.#list.find("[data-selected]");
            selectedItem.attr("data-hover", "");

            this.#dropdownDom.addClass("focused");
            this.#list.show();

            var targetPosition = 0;
            selectedItem.prevAll().each((_, itemDom) => {
              targetPosition += jQuery(itemDom).outerHeight();
            });
            this.#list.scrollTop(targetPosition);
          }
        }

        e.preventDefault();
        e.stopPropagation();
      })
      .on("focusout", () => {
        this.#dropdownDom.removeClass("focused");
        this.#list.hide();
      });

    this.#list
      .find("li")
      .on("pointerenter", (e) => {
        this.#list.find("[data-hover]").removeAttr("data-hover");
        jQuery(e.target).attr("data-hover", "");
        e.preventDefault();
      })
      .on("click", (e) => {
        changeValueBySelected();
        e.preventDefault();
      });
  }

  /**
   * Renders the dropdown component and initializes internal DOM references
   *
   * @private
   */
  #render() {
    const selectedValue = this.#value.length
      ? this.#config.options.find((item) => item[0] === this.#value)
      : this.#config.options[0];

    this.#dropdownDom = this.#rootDom
      .append(
        `
			  <div class="dropdown regular-text" tabindex="0" id=${this.#rootDom.attr("id")?.length ? this.#rootDom.attr("id") + "_input" : ""}>
				  <div class="display-name">${selectedValue[1]}</div>
				  <ul style="display:none;">
				  ${this.#config.options
            .map(
              (item) =>
                `<li data-value="${item[0]}" ${item[0] === selectedValue[0] ? 'data-selected="" data-hover=""' : ""}>${item[1]}</li>`,
            )
            .join("")}
				  </ul>
				  <input type="hidden" name="${this.#config?.data_name ?? ""}" value="${selectedValue[0]}">
			  </div>
		  `,
      )
      .find(".dropdown");

    this.#list = this.#dropdownDom.find("ul");
    this.#input = this.#dropdownDom.find('input[type="hidden"]');
    this.#displayName = this.#dropdownDom.find(".display-name");
    this.#descriptionDom = this.#rootDom.find(".description");

    this.#rootDom.height(
      this.#dropdownDom.outerHeight() +
        (this.#descriptionDom.outerHeight() ?? 0),
    );
  }
}
