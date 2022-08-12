<?php

namespace Drupal\Tests\home_redirect_lang\Functional;

use Drupal\home_redirect_lang\HomeRedirectLangInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cover the override of Cookie Redirection over Browser Redirection.
 *
 * @group home_redirect_lang
 * @group home_redirect_lang_functional
 * @group home_redirect_lang_cooker_and_browser
 *
 * @internal
 * @coversNothing
 */
final class CookieOverrideBrowserLanguageRedirectionFunctionalTest extends FunctionalTestBase {
  use AssertRedirectTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpLanguages();
    $this->setUpArticles();

    $settings = $this->container->get('config.factory')->getEditable('home_redirect_lang.browser_fallback');
    $settings->set('enable_browser_fallback', TRUE)->save();
  }

  /**
   * Redirection should first use the cookie then the browser as fallback.
   *
   * When visiting the homepage on any other langcode other than preferred
   * language cookie, then the end-client should be redirected to the cookie
   * preferred lang.
   *
   * @dataProvider providerCookieMustRedirectOverBrowserPreferredRedirection
   */
  public function testCookieMustRedirectOverBrowserPreferredRedirection(string $cookie_preferred_langcode, string $server_http_preferred_langcode, string $path, string $expected) {
    $session = $this->getSession();
    $session->setCookie(HomeRedirectLangInterface::COOKIE_PREFERRED_LANGCODE, $cookie_preferred_langcode);
    $session->setRequestHeader(HomeRedirectLangInterface::BROWSER_HTTP_HEADER_PREFERRED_LANGCODE, $server_http_preferred_langcode);

    // Ensure the homepage will redirect to the preferred langcode.
    $this->assertRedirect($path, $expected, Response::HTTP_FOUND);
  }

  /**
   * Provides test data for the cookie redirection overrode browser.
   */
  public function providerCookieMustRedirectOverBrowserPreferredRedirection(): iterable {
    yield ['en', 'en', 'fr', '/'];

    yield ['en', 'fr', 'fr', '/'];

    yield ['en', 'de', 'fr', '/'];

    yield ['en', '', 'fr', '/'];

    yield ['en', 'en', 'de', '/'];

    yield ['en', 'fr', 'de', '/'];

    yield ['en', 'de', 'de', '/'];

    yield ['en', '', 'de', '/'];

    yield ['de', 'en', 'fr', '/de'];

    yield ['de', 'fr', 'fr', '/de'];

    yield ['de', 'de', 'fr', '/de'];

    yield ['de', '', 'fr', '/de'];

    yield ['de', 'en', 'index.php', '/index.php/de'];

    yield ['de', 'fr', 'index.php', 'index.php/de'];

    yield ['de', 'de', 'index.php', 'index.php/de'];

    yield ['de', '', 'index.php', 'index.php/de'];

    yield ['fr', 'en', 'de', '/fr'];

    yield ['fr', 'fr', 'de', '/fr'];

    yield ['fr', 'de', 'de', '/fr'];

    yield ['fr', '', 'de', '/fr'];

    yield ['fr', 'en', 'index.php', 'index.php/fr'];

    yield ['fr', 'fr', 'index.php', 'index.php/fr'];

    yield ['fr', 'de', 'index.php', 'index.php/fr'];

    yield ['fr', '', 'index.php', 'index.php/fr'];

    yield ['', 'en', 'fr', '/'];

    yield ['', 'de', 'fr', '/de'];

    yield ['', 'fr', 'index.php', '/index.php/fr'];

    yield ['', 'de', 'index.php', '/index.php/de'];

    yield ['', 'en', 'de', '/'];

    yield ['', 'fr', 'de', '/fr'];
  }

  /**
   * {@inheritdoc}
   */
  protected function initMink() {
    $session = parent::initMink();

    /** @var \Behat\Mink\Driver\BrowserKitDriver $driver */
    $driver = $session->getDriver();
    // Since we are testing low-level redirect stuff, the HTTP client should
    // NOT automatically follow redirects sent by the server.
    $driver->getClient()->followRedirects(FALSE);

    return $session;
  }

}
