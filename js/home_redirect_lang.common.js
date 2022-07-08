/**
 * @file home_redirect_lang.common.js
 *
 * Common helper functions used by various parts of Homepage Redirect Language.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.homeRedirectLang = {};

  /**
   * Create or updates the preferred language cookie.
   * *
   * @param {string} langcode
   *   The langcode to be set as preferred language.
   */
  Drupal.homeRedirectLang.setPreferredLanguage = function (langcode) {
    var date = new Date();
    date.setTime(date.getTime() + (365*24*60*60*1000));
    document.cookie = 'home_redirect_lang_preferred_langcode=' + langcode + '; expires=' + date.toUTCString() + '; path=/';
  };

}(jQuery, Drupal, drupalSettings));
