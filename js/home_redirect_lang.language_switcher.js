/**
 * @file home_redirect_lang.language_switcher.js
 *
 * Defines the behavior of the language switcher cookie storage.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.homeRedirectLangSwitcher = {};

  /**
   * Registers behaviours related to drupal language switcher.
   */
  Drupal.behaviors.homeRedirectLangSwitcher = {
    attach: function (context) {
      let links = document.querySelectorAll('.language-switcher-language-url .language-link');

      // Don't process when standard language switcher not found.
      if (links.length === 0) {
        return;
      }

      links.forEach(box => {
        box.addEventListener('click', function (event) {
          var hreflang = event.target.getAttribute('hreflang');
          Drupal.homeRedirectLang.setPreferredLanguage(hreflang);
        });
      });

    }
  };


}(jQuery, Drupal, drupalSettings));
