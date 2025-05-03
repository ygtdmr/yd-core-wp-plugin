/**
 * yd_core UI Initialization Script
 * This script initializes all YD input components (dropdowns, selections, image pickers, etc.) inside elements with the `.yd-core` class.
 * It registers inputs dynamically based on their type, enhances accessibility and interactivity (e.g., help tips, form change tracking),
 * and ensures keyboard usability. It also integrates form change detection and confirmation dialogs on page unload.
 *
 * Author: Yigit Demir
 * Since: 1.0.0
 * Version: 1.0.0
 */

"use strict";

jQuery(function ($) {
  window.yd_core = Object.assign(window.yd_core, {
    ui: {
      /**
       * Object to store registered input components.
       * @type {Object}
       */
      input: {},

      /**
       * Initializes all UI input elements and attaches behaviors like help tips.
       * Dynamically detects input type and registers it accordingly.
       * @returns {Promise<void>}
       */
      init: async () => {
        /**
         * Registers a new input component.
         * @param {string|number} id - Identifier for the input.
         * @param {Object} input - Input instance to register.
         */
        const register = (id, input) => {
          const inputLength = Object.keys(window.yd_core.ui.input).length;
          window.yd_core.ui.input[id || inputLength] = input;
        };

        /**
         * Attaches event listeners to help tips for showing and hiding tooltip text.
         * @param {jQuery} rootDom - Root DOM element containing the help-tip.
         */
        const checkHelpTip = (rootDom) => {
          var timeOutId, helpTip;

          rootDom
            .find(".help-tip")
            .on("mouseenter focus", (e) => {
              helpTip = $(e.target);
              timeOutId = setTimeout(() => {
                helpTip.css("z-index", "999").find(".help-text").fadeIn(100);
              }, 300);
            })
            .on("mouseleave blur", (e) => {
              clearTimeout(timeOutId);
              helpTip.find(".help-text").fadeOut(100, () => {
                helpTip.css("z-index", "");
              });
            })
            .on("click keyup keydown", (e) => {
              if (e.type === "keydown") {
                if ([13, 32].includes(e.keyCode)) {
                  e.preventDefault();
                }
              } else {
                const id = $(e.target).attr("data-input-id");
                if (id?.length) {
                  if (e.type === "keyup" && ![13, 32].includes(e.keyCode))
                    return;

                  $(`#${id}_input`)[0]?.focus();
                  $(`#${id}_input_click`)[0]?.click();

                  e.preventDefault();
                }
              }
            });
        };

        for await (let rootDom of $(
          ".yd-core .yd-admin-ui-input:not(.loaded)",
        )) {
          rootDom = $(rootDom);

          const isRegistered =
            window.yd_core.ui.findByDom(rootDom) !== undefined;

          if (isRegistered) continue;

          /**
           * Helper to determine if the input matches a type.
           * @param {string} type - Type to check against.
           * @returns {boolean}
           */
          const is = (type) => rootDom.hasClass(`yd-admin-ui-input-${type}`);

          const [id, config, properties, value, dropdownOptions] = [
            rootDom.attr("id"),
            rootDom.attr("data-config"),
            rootDom.attr("data-properties"),
            rootDom.attr("data-value"),
            rootDom.attr("data-dropdown-options"),
          ];

          if (is("dropdown")) {
            await register(id, new YD_Input_Dropdown(rootDom, config, value));
          } else if (is("selection")) {
            await register(
              id,
              new YD_Input_Selection(rootDom, config, properties, value),
            );
          } else if (is("selection-media")) {
            await register(id, new YD_Input_Selection_Media(rootDom, config));
          } else if (is("color-picker")) {
            await register(id, new YD_Input_Color_Picker(rootDom));
          } else if (is("selection-action")) {
            await register(
              id,
              new YD_Input_Selection_Action(
                rootDom,
                config,
                dropdownOptions,
                value,
              ),
            );
          }

          checkHelpTip(rootDom);

          rootDom.addClass("loaded");
        }
      },

      /**
       * Finds an input instance by matching its root DOM.
       * @param {jQuery} rootDom - DOM element to search for.
       * @returns {Object|undefined}
       */
      findByDom: (rootDom) => {
        for (const [id, input] of Object.entries(window.yd_core.ui.input)) {
          if (
            rootDom[0] === input.getRootDom().closest(".yd-admin-ui-input")[0]
          )
            return input;
          if (rootDom[0] === input.getRootDom()[0]) return input;
        }
      },

      /**
       * Returns translated text from language data or falls back to original.
       * @param {string} text - Key to translate.
       * @returns {string}
       */
      getText: (text) => window.yd_core.language.text[text] ?? text,
    },

    action: {
      /**
       * Sends an AJAX request to WordPress backend and calls onDone callback.
       * @param {Function} onDone - Callback after successful response.
       * @param {string} actionName - Action name (without 'yd-' prefix).
       * @param {Object} [data=undefined] - Request data.
       * @param {string} [type="POST"] - Request type (GET or POST).
       * @returns {jqXHR}
       */
      runAjax: (onDone, actionName, data = undefined, type = "POST") => {
        return jQuery
          .ajax({
            type: type,
            url: window.ajaxurl,
            data: jQuery.extend({ action: "yd-" + actionName }, data),
          })
          .done(function (response) {
            onDone(response?.data);
          });
      },
    },
  });

  // Track form change events and set data attribute accordingly
  $(window.document)
    .on("yd-form-change", (_, data = undefined) => {
      const body = $(window.document.body);
      if (data?.changed ?? true) {
        body.attr("data-form-changed", "1");
      } else {
        body.removeAttr("data-form-changed");
      }
    })
    .on(
      "input",
      ".yd-core .yd-admin-ui-input:not(.ignored) input, body:not(.post-new-php) #title",
      () => {
        $(window.document).trigger("yd-form-change");
      },
    )
    .on("change", '.yd-core .yd-admin-ui-input input[type="checkbox"]', (e) => {
      const checkbox = $(e.currentTarget);
      checkbox
        .closest(".yd-admin-ui-input")
        .find('input[type="hidden"]')
        .val(checkbox.is(":checked") ? 1 : 0);
    })
    .on("keyup keydown", "[tabindex]", (e) => {
      if (
        (e.type === "keydown" && [32, 13].includes(e.keyCode)) ||
        (e.type === "keyup" && ![32, 13].includes(e.keyCode))
      )
        return false;
      if (e.type === "keydown" && ![32, 13].includes(e.keyCode)) return true;
      e.target.click();
      e.preventDefault();
    });

  // Reset form change state when valid form is submitted
  $('form [type="submit"]').on("click", (e) => {
    if (e.currentTarget.form.checkValidity()) {
      $(window.document).trigger("yd-form-change", { changed: false });
    }
  });

  // Warn users about unsaved form changes on page unload
  window.addEventListener("beforeunload", (e) => {
    if ($(window.document.body).attr("data-form-changed")) {
      e.returnValue =
        "Are you sure to leave? It looks like you have been editing something.";
      e.preventDefault();
    }
  });

  // Start UI initialization
  window.yd_core.ui.init();
});
