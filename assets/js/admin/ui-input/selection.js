/**
 * YD_Input_Selection class
 * A dynamic input selection component supporting both single and multiple selection modes.
 * Features include asynchronous item loading via AJAX, live search with debounce, full keyboard navigation,
 * accessibility support, dynamic placeholder handling, and integration with hidden input elements for form data handling.
 * It also ensures responsive DOM rendering and user interaction tracking.
 *
 * Author: Yigit Demir
 * Since: 1.0.0
 * Version: 1.0.0
 */

"use strict";

class YD_Input_Selection {
  /**
   * Ajax action name used for requests
   *
   * @type {string}
   */
  #ajaxActionName;

  /**
   * Current selected data values
   *
   * @type {Array}
   */
  #data;

  /**
   * Input element used for editing/typing
   *
   * @type {jQuery}
   */
  #inputEdit;

  /**
   * Root DOM element for the selection component
   *
   * @type {jQuery}
   */
  #rootDom;

  /**
   * DOM element for the main selection container
   *
   * @type {jQuery}
   */
  #selectionDom;

  /**
   * DOM element for displaying all available items
   *
   * @type {jQuery}
   */
  #allItemsDom;

  /**
   * DOM element for the list of all items
   *
   * @type {jQuery}
   */
  #allItemsListDom;

  /**
   * DOM element for single selected name container (non-multiple mode)
   *
   * @type {jQuery}
   */
  #nameDom;

  /**
   * DOM element displaying the selected name value (non-multiple mode)
   *
   * @type {jQuery}
   */
  #nameValueDom;

  /**
   * DOM element for removing the selected name (non-multiple mode)
   *
   * @type {jQuery}
   */
  #nameRemoveDom;

  /**
   * DOM element for description (optional)
   *
   * @type {jQuery}
   */
  #descriptionDom;

  /**
   * Configuration object passed to the component
   *
   * @type {Object}
   */
  #config;

  /**
   * Additional properties passed to the component
   *
   * @type {Object}
   */
  #properties;

  /**
   * Initial value passed to the component
   *
   * @type {Array|string|number}
   */
  #value;

  /**
   * Creates an instance of YD_Input_Selection.
   *
   * @param {HTMLElement|string} rootDom - The root DOM element or selector
   * @param {string} config - JSON string of configuration
   * @param {string} properties - JSON string of properties
   * @param {string} value - JSON string of value
   */
  constructor(rootDom, config, properties, value) {
    this.#rootDom = jQuery(rootDom);
    this.#config = JSON.parse(config);
    this.#properties = JSON.parse(properties);
    this.#value = JSON.parse(value);

    this.#ajaxActionName = this.#config.ajax_action_name ?? "wc-search";
    this.#data = this.#value;

    this.#render();
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
   * Retrieves a value from the config by key
   *
   * @param {string} key - Config key
   * @returns {*}
   */
  getConfig(key) {
    return this.#config[key];
  }

  /**
   * Modifies a key in the config object
   *
   * @param {string} key - Config key
   * @param {*} value - New value to assign
   */
  modifyConfig(key, value) {
    Object.defineProperty(this.#config, key, { value: value });
  }

  /**
   * Modifies a key in the properties object
   *
   * @param {string} key - Property key
   * @param {*} value - New value to assign
   */
  modifyProperties(key, value) {
    Object.defineProperty(this.#properties, key, { value: value });
  }

  /**
   * Renders the component and sets up its structure and data
   *
   * @private
   */
  #render() {
    this.#rootDom.prepend(
      this.#config.is_multiple
        ? `
            <div class="selection multiple regular-text">
                <div class="spinner"></div>
                <ul class="selected-items">
                    <li class="edit"><input type="text" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" placeholder=""/></li>
                </ul>
                <div class="all-items" style="display:none;">
                    <div class="spinner"></div>
                    <ul></ul>
                </div>
            </div>
            `
        : `
            <div class="selection regular-text" tabindex="0">
                <div class="spinner"></div>
                <div class="name" style="display:none;">
                    <div class="value"></div>
                    <span class="remove" tabindex="0">x</span>
                </div>
                <div class="all-items" style="display:none;">
                    <div class="spinner"></div>
                    <div class="edit"><input type="text" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" placeholder="${window.yd_core.ui.getText("Enter keyword")}"/></div>
                    <ul></ul>
                </div>
            </div>
            `,
    );

    this.#inputEdit = this.#rootDom.find(".edit > input");

    this.#selectionDom = this.#rootDom.find(".selection");
    this.#allItemsDom = this.#selectionDom.find(".all-items");
    this.#allItemsListDom = this.#allItemsDom.find("ul");
    this.#descriptionDom = this.#rootDom.find(".description");

    const rootDomId = this.#rootDom.attr("id");

    if (this.#config.is_multiple) {
      if (rootDomId?.length) this.#inputEdit.attr("id", rootDomId + "_input");

      this.#getItemsByValue((items) => {
        this.#rootDom.find(".selected-items").prepend(
          items.map((item) => {
            return this.#createItemDom(item);
          }),
        );
        this.#updateDomHeight();
      });
    } else {
      this.#nameDom = this.#rootDom.find(".selection > .name");
      this.#nameValueDom = this.#nameDom.find(".value");
      this.#nameRemoveDom = this.#nameDom.find(".remove");

      if (rootDomId?.length)
        this.#nameValueDom.attr("id", rootDomId + "_input_click");

      this.#getItemByValue((item) => {
        this.#nameDom.show();
        if (item?.name) {
          this.#nameValueDom.text(item.name);
          this.#nameRemoveDom.show();
        }
        this.#data = item?.id ? [item.id] : [];
      });
    }

    this.#updateDataInput();
    this.#loadEvents();
    this.#updateDomHeight();
  }

  /**
   * Binds all necessary event handlers for interaction
   *
   * @private
   */
  #loadEvents() {
    this.#selectionDom.on("click mousedown", (e) => {
      if (e.type === "mousedown" && this.#selectionDom.hasClass("focused")) {
        this.#inputEdit.focus();
      } else if (e.type === "click") {
        this.#inputEdit.focus();
      }
      e.preventDefault();
    });
    this.#inputEdit.on("focus", () => {
      this.#selectionDom.addClass("focused");
      this.#selectionDom.removeAttr("tabindex");
    });
    this.#inputEdit.on("focusout", () => {
      this.#selectionDom.attr("tabindex", "0");
      this.#selectionDom.removeClass("focused");
      this.#allItemsDom.hide();
      this.#allItemsListDom.empty();
      if (!this.#config.is_multiple) {
        this.#inputEdit.val("");
      }
    });

    var timeoutId;
    const allItemsSpinner = this.#allItemsDom.find(".spinner");
    this.#inputEdit.on("input", (e) => {
      const keyword = e.target.value;
      clearTimeout(timeoutId);

      if (keyword.length) {
        allItemsSpinner.addClass("is-active");
        this.#allItemsListDom.empty();
        this.#allItemsDom.show();

        timeoutId = setTimeout(() => {
          this.#listItemsByKeyword((items) => {
            allItemsSpinner.removeClass("is-active");
            this.#allItemsListDom.empty();
            this.#allItemsListDom.append(items);

            const selectedItem = this.#allItemsListDom.find("[data-selected]");
            if (selectedItem.length) {
              var targetPosition = 0;
              selectedItem.prevAll().each((_, itemDom) => {
                targetPosition += jQuery(itemDom).outerHeight();
              });
              this.#allItemsListDom.scrollTop(targetPosition);
            }
          }, keyword);
        }, 500);
      } else {
        if (this.#config.is_multiple) {
          this.#allItemsDom.hide();
        }
        allItemsSpinner.removeClass("is-active");
        this.#allItemsListDom.empty();
      }
    });

    this.#inputEdit.on("keydown keyup", (e) => {
      if (this.#allItemsListDom.children().length > 0) {
        if (e.type === "keydown" && [38, 40].includes(e.keyCode)) {
          const hoverListItem = this.#allItemsListDom.find("li[data-hover]");
          const targetListItem =
            e.keyCode === 38 ? hoverListItem.prev() : hoverListItem.next();

          if (targetListItem.length > 0) {
            hoverListItem.removeAttr("data-hover");
            targetListItem.attr("data-hover", "");

            const targetPositionTop = targetListItem.position().top;
            const allItemsListHeight = this.#allItemsListDom.height();

            if (
              targetPositionTop > allItemsListHeight ||
              targetPositionTop < 0
            ) {
              this.#allItemsListDom.scrollTop(
                this.#allItemsListDom.scrollTop() + targetPositionTop,
              );
            }
          }

          e.preventDefault();
        } else if (e.type === "keyup" && e.keyCode == 13) {
          this.#allItemsListDom.find("[data-hover").click();
          this.#selectionDom.focus();
        }
      }

      if (e.keyCode == 13) {
        e.preventDefault();
      }
    });

    if (this.#config.is_multiple) {
      this.#inputEdit
        .on("keydown", (e) => {
          const isPressedBackspace = [8, 46].includes(e.keyCode);
          const isNotEmptyData = this.#data.length > 0;
          const isEmptyValue = !e.target.value.length;

          if (isPressedBackspace && isNotEmptyData && isEmptyValue) {
            const lastItem = this.#rootDom
              .find(".selected-items > li:not(.edit)")
              .last();

            this.#inputEdit.val(lastItem.find(".name").text());
            lastItem.find(".remove").click();

            e.preventDefault();
          }
        })
        .on("input", (e) => {
          const length = e.target.value.length + 1;
          const unit = 0.75;
          const value = length * unit;

          this.#inputEdit.css("width", `${value}em`);
        });
    } else {
      this.#nameValueDom.on("click", (e) => {
        this.#allItemsDom.show();
      });
      this.#nameRemoveDom.on("click keydown keyup", (e) => {
        if (
          (["keyup", "keydown"].includes(e.type) &&
            [32, 13].includes(e.keyCode)) ||
          e.type === "click"
        ) {
          this.#data = [];
          this.#updateDataInput();

          this.#inputEdit.focusout().trigger("input");
          this.#selectionDom.focus();
          return false;
        }
      });
      this.#selectionDom.on("keydown keyup", (e) => {
        if (
          e.target === this.#selectionDom[0] &&
          [32, 13].includes(e.keyCode)
        ) {
          this.#nameValueDom.click();
          e.preventDefault();
        }
      });
    }
  }

  /**
   * Creates a DOM element for a selected item
   *
   * @private
   * @param {Object} item - Item to create DOM for
   * @returns {jQuery} - The created item DOM
   */
  #createItemDom(item) {
    const itemDom = jQuery(
      `<li data-id="${item.id}"><span class="remove">x</span> <span class="name">${item.name}</span></li>`,
    );
    itemDom.find("span.remove").on("click", () => {
      if (!this.#rootDom.hasClass("ignored")) {
        jQuery(window.document).trigger("yd-form-change");
      }
      this.#data = this.#data.filter((value) => value != item.id);
      this.#updateDataInput();
      itemDom.remove();
      this.#updateDomHeight();
    });
    return itemDom;
  }

  /**
   * Gets a single item based on the current value
   *
   * @private
   * @param {Function} onReadyValue - Callback when item is fetched
   */
  #getItemByValue(onReadyValue) {
    if (!this.#data.length) {
      onReadyValue();
      return;
    }

    const spinner = this.#rootDom.find(".selection > .spinner");
    spinner.addClass("is-active");

    window.yd_core.action.runAjax(
      (response) => {
        spinner.remove();
        onReadyValue(response[0]);
      },
      this.#ajaxActionName,
      jQuery.extend({}, this.#properties, { value: this.#value }),
    );
  }

  /**
   * Gets multiple items based on the current values
   *
   * @private
   * @param {Function} onReadyValue - Callback when items are fetched
   */
  #getItemsByValue(onReadyValue) {
    if (!this.#data.length) return;

    const spinner = this.#rootDom.find(".selection > .spinner");
    spinner.addClass("is-active");

    window.yd_core.action.runAjax(
      (response) => {
        spinner.remove();
        onReadyValue(response);
      },
      this.#ajaxActionName,
      jQuery.extend({}, this.#properties, { value: this.#value }),
    );
  }

  /**
   * Lists items by keyword entered in input
   *
   * @private
   * @param {Function} onReadyValue - Callback with matched items
   * @param {string} keyword - Keyword to search with
   */
  #listItemsByKeyword(onReadyValue, keyword) {
    window.yd_core.action.runAjax(
      (response) => {
        const listItems = response.map((item) => {
          const listItemDom = jQuery(
            `<li data-id="${item.id}">${item.name}</li>`,
          );

          listItemDom.on("click", () => {
            if (this.#config.is_multiple) {
              const isItemInData = this.#data.includes(item.id);

              if (isItemInData) {
                this.#rootDom
                  .find(`.selected-items > li[data-id="${item.id}"] > .remove`)
                  .click();
              } else {
                this.#createItemDom(item).insertBefore(
                  this.#rootDom.find(".selected-items > .edit"),
                );

                this.#data.push(item.id);
                this.#updateDataInput();
              }
            } else {
              this.#nameRemoveDom.show();
              this.#nameValueDom.text(item.name);
              this.#data = [item.id];
              this.#updateDataInput();
            }

            this.#allItemsDom.hide();
            this.#allItemsListDom.empty();
            this.#inputEdit.val("");

            this.#updateDomHeight();
          });

          listItemDom.on("mouseenter", () => {
            this.#allItemsListDom.find("li").removeAttr("data-hover");
            listItemDom.attr("data-hover", "");
          });

          if (this.#config.is_multiple) {
            if (response[0].id === item.id) {
              listItemDom.attr("data-hover", "");
            }
          } else if (this.#data[0] === item.id) {
            listItemDom.attr({ "data-selected": "", "data-hover": "" });
          } else if (
            !response.map((e) => e.id).includes(this.#data[0]) &&
            response[0].id === item.id
          ) {
            listItemDom.attr("data-hover", "");
          }

          return listItemDom;
        });
        onReadyValue(listItems);
      },
      this.#ajaxActionName,
      jQuery.extend({}, this.#properties, { keyword: keyword }),
    );
  }

  /**
   * Updates the hidden input fields with current selected data
   *
   * @private
   */
  #updateDataInput() {
    this.#rootDom.find(".selection > input").remove();

    this.#data.forEach((value, index) => {
      this.#selectionDom.append(`
                <input type="hidden" name="${this.#config.data_name}[${index}]" value="${value}"/>
            `);
    });

    if (!this.#data.length && this.#config.is_required) {
      this.#selectionDom.append(
        '<input type="text" required="" onkeypress="return false;" tabindex="-1" />',
      );
    }

    this.#checkInputPlaceHolder();
  }

  /**
   * Updates the placeholder text of the input if needed
   *
   * @private
   */
  #checkInputPlaceHolder() {
    const placeholderText = this.#config.display_name;

    if (this.#config.is_multiple) {
      this.#inputEdit.attr(
        "placeholder",
        !this.#data.length ? placeholderText : "",
      );
    } else if (!this.#data.length) {
      this.#nameValueDom.text(placeholderText);
      this.#nameRemoveDom.hide();
    }
  }

  /**
   * Updates the height of the root DOM based on its content
   *
   * @private
   */
  #updateDomHeight() {
    this.#rootDom.height(
      this.#selectionDom.outerHeight() +
        (this.#descriptionDom.outerHeight() ?? 0),
    );
  }
}
