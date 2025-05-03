/**
 * YD_Input_Color_Picker
 *
 * Lightweight wrapper around WordPress' built‑in `wpColorPicker` that
 * dispatches a custom **yd-color-change** event whenever the user picks or
 * clears a colour. Designed to integrate with other YD Admin UI widgets for
 * instant live‑preview updates.
 *
 * Author:  Yigit Demir
 * Version: 1.0.0
 * Since:   1.0.0
 */

"use strict";

class YD_Input_Color_Picker {
  /**
   * Root DOM element of the color picker.
   *
   * @type {jQuery}
   */
  #rootDom;

  /**
   * Holds the colour value
   * @type {jQuery}
   */
  #inputValue;

  /**
   * Creates an instance of YD_Input_Color_Picker.
   *
   * @param {HTMLElement|string} rootDom - Root DOM element or selector
   */
  constructor(rootDom) {
    const onChangeColor = (e) => {
      this.#rootDom.trigger("yd-color-change", e.target.value);
    };

    this.#rootDom = jQuery(rootDom);
    this.#inputValue = this.#rootDom.find("input");

    this.#inputValue.wpColorPicker({
      change: onChangeColor,
      clear: onChangeColor,
    });
  }

  /**
   * Returns the root DOM element
   *
   * @returns {jQuery}
   */
  getRootDom() {
    return this.#rootDom;
  }
}
