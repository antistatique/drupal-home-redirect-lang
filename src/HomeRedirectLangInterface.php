<?php

namespace Drupal\home_redirect_lang;

/**
 * Provides constants used for retrieving cookies names.
 */
interface HomeRedirectLangInterface {

  /**
   * Key for the cookie preferred langcode.
   *
   * @var string
   */
  const COOKIE_PREFERRED_LANGCODE = 'home_redirect_lang_preferred_langcode';

  /**
   * Server header name of the client preferred lang (Accept-Language).
   *
   * @var string
   */
  const SERVER_HTTP_PREFERRED_LANGCODE = 'HTTP_ACCEPT_LANGUAGE';

  /**
   * Browser header  name of the client preferred lang (HTTP_ACCEPT_LANGUAGE).
   *
   * @var string
   */
  const BROWSER_HTTP_HEADER_PREFERRED_LANGCODE = 'Accept-Language';

}
