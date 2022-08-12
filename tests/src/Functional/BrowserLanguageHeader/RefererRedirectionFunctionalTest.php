<?php

namespace Drupal\Tests\home_redirect_lang\Functional\BrowserLanguageHeader;

use Drupal\home_redirect_lang\HomeRedirectLangInterface;
use Drupal\Tests\home_redirect_lang\Functional\FunctionalTestBase;
use Drupal\Tests\home_redirect_lang\Functional\AssertRedirectTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cover the HTTP Referer Header preventing Browser Language Redirections.
 *
 * When visiting the homepage with an External REFERER Header on any langcode
 * even different of the browser preferred language should never trigger
 * redirection to the end-client.
 *
 * @group home_redirect_lang
 * @group home_redirect_lang_functional
 * @group home_redirect_lang_browser
 */
class RefererRedirectionFunctionalTest extends FunctionalTestBase {
  use AssertRedirectTrait;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->setUpLanguages();
    $this->setUpArticles();

    $settings = $this->container->get('config.factory')->getEditable('home_redirect_lang.browser_fallback');
    $settings->set('enable_browser_fallback', TRUE)->save();
    $settings->set('enable_referer_bypass', TRUE)->save();
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

  /**
   * External Referer should prevent redirections.
   *
   * @dataProvider providerBrowserRedirections
   */
  public function testRefererExternalBrowserRedirections(string $preferred_langcode, string $path) {
    $session = $this->getSession();
    $session->setRequestHeader(HomeRedirectLangInterface::BROWSER_HTTP_HEADER_PREFERRED_LANGCODE, $preferred_langcode);
    $session->setRequestHeader('referer', 'https://www.google.ch');

    // Ensure the homepage will not trigger any redirecion because of referer.
    $this->assertNoRedirect($path);
  }

  /**
   * Ensure redirection still triggered when the bypass settings is disabled.
   */
  public function testRefererBypasskSettingsDisabledShouldRedirect() {
    $settings = $this->container->get('config.factory')->getEditable('home_redirect_lang.browser_fallback');
    $settings->set('enable_referer_bypass', FALSE)->save();

    $session = $this->getSession();
    $session->setRequestHeader(HomeRedirectLangInterface::BROWSER_HTTP_HEADER_PREFERRED_LANGCODE, 'fr-CH,fr;q=0.9,fr;q=0.8,de;q=0.7');
    $session->setRequestHeader('referer', 'https://www.google.ch');

    // Ensure redirection still trigger as the refere bypass is disabled.
    $this->assertRedirect('/de', '/fr', Response::HTTP_FOUND);
  }

  /**
   * Internal Referer should still generate redirection.
   *
   * @dataProvider providerBrowserRedirections
   */
  public function testRefererInernalBrowserRedirections(string $preferred_langcode, string $path) {
    $session = $this->getSession();
    $session->setRequestHeader(HomeRedirectLangInterface::BROWSER_HTTP_HEADER_PREFERRED_LANGCODE, $preferred_langcode);
    $session->setRequestHeader('referer', '/node/1');

    $url = $this->getAbsoluteUrl($path);
    $this->getSession()->visit($url);

    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(Response::HTTP_FOUND);
  }

  /**
   * Provides test data for the testBrowserRedirections() method.
   */
  public function providerBrowserRedirections(): iterable {
    yield ['en-US,en;q=0.9,fr;q=0.8,de;q=0.7', '/fr'];
    yield ['en-US,en;q=0.9,fr;q=0.8,de;q=0.7', '/de'];

    yield ['de-CH,de;q=0.9,fr;q=0.8,de;q=0.7', '/fr'];
    yield ['de-CH,de;q=0.9,fr;q=0.8,de;q=0.7', 'index.php'];

    yield ['fr-CH,fr;q=0.9,fr;q=0.8,de;q=0.7', '/de'];
    yield ['fr-CH,fr;q=0.9,fr;q=0.8,de;q=0.7', 'index.php'];
  }

}
