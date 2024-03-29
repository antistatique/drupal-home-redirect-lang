/**
 * @file home_redirect_lang.language_switcher.js
 *
 * Defines the behavior of the language switcher cookie storage.
 */

(function (Drupal) {

  'use strict';

  Drupal.homeRedirectLangSwitcher = {};

  /**
   * Registers behaviours related to drupal language switcher.
   */
  Drupal.behaviors.homeRedirectLangSwitcher = {
    attach: function (context) {

      // Prevent attempting to attach cookie if the common library is not available.
      if (typeof Drupal.homeRedirectLang == "undefined") {
        return;
      }

      let links = document.querySelectorAll('.language-switcher-language-url .language-link');

      links.forEach(link => {
        link.addEventListener('click', function (event) {
          var hreflang = event.target.getAttribute('hreflang');
          Drupal.homeRedirectLang.setPreferredLanguage(hreflang);
        });
      });

    }
  };


}(Drupal));
