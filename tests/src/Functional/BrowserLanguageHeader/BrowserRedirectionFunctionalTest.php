<?php

namespace Drupal\Tests\home_redirect_lang\Functional\BrowserLanguageHeader;

use Drupal\home_redirect_lang\HomeRedirectLangInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Tests\home_redirect_lang\Functional\FunctionalTestBase;
use Drupal\Tests\home_redirect_lang\Functional\AssertRedirectTrait;

/**
 * Cover the Browser Language Redirection.
 *
 * @group home_redirect_lang
 * @group home_redirect_lang_functional
 * @group home_redirect_lang_browser
 */
class BrowserRedirectionFunctionalTest extends FunctionalTestBase {
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
  public function setUp(): void {
    parent::setUp();

    $this->setUpLanguages();
    $this->setUpArticles();

    $settings = $this->container->get('config.factory')->getEditable('home_redirect_lang.browser_fallback');
    $settings->set('enable_browser_fallback', TRUE)->save();
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
   * Prevent redirection when the fallback setting is disabled.
   */
  public function testFallbackSettingsDisabledShouldNotRedirect() {
    $settings = $this->container->get('config.factory')->getEditable('home_redirect_lang.browser_fallback');
    $settings->set('enable_browser_fallback', FALSE)->save();

    $session = $this->getSession();
    $session->setRequestHeader(HomeRedirectLangInterface::BROWSER_HTTP_HEADER_PREFERRED_LANGCODE, 'fr-CH,fr;q=0.9,fr;q=0.8,de;q=0.7');

    $this->assertNoRedirect('');
    $this->assertNoRedirect('/fr');
    $this->assertNoRedirect('/de');
  }

  /**
   * Prevent redirection on any other page than homepage.
   */
  public function testNotHomepageShouldNotRedirect() {
    $session = $this->getSession();
    $session->setRequestHeader(HomeRedirectLangInterface::BROWSER_HTTP_HEADER_PREFERRED_LANGCODE, 'fr-CH,fr;q=0.9,fr;q=0.8,de;q=0.7');

    $this->assertNoRedirect("/node/1");
    $this->assertNoRedirect("/fr/node/1");
    $this->assertNoRedirect("/de/node/1");
  }

  /**
   * Prevent redirection without preferred langcode header (Accept-Language).
   */
  public function testWithoutHeaderShouldNotRedirect() {
    $this->assertNoRedirect('');
    $this->assertNoRedirect('/fr');
    $this->assertNoRedirect('/de');
  }

  /**
   * Prevent redirecting when preferred langcode and current lang are identical.
   *
   * When visiting the homepage on the same langcode of the browser preferred
   * language, then the end-client should not be redirected anywhere.
   */
  public function testSameLangcoderShouldNotRedirect() {
    $session = $this->getSession();
    $session->setRequestHeader(HomeRedirectLangInterface::BROWSER_HTTP_HEADER_PREFERRED_LANGCODE, 'en-US,en;q=0.9,fr;q=0.8,de;q=0.7');
    $this->assertNoRedirect('');
    $session->setRequestHeader(HomeRedirectLangInterface::BROWSER_HTTP_HEADER_PREFERRED_LANGCODE, 'fr-FR,fr;q=0.9,fr;q=0.8,de;q=0.7');
    $this->assertNoRedirect('/fr');
    $session->setRequestHeader(HomeRedirectLangInterface::BROWSER_HTTP_HEADER_PREFERRED_LANGCODE, 'de-CH,de;q=0.9,fr;q=0.8,de;q=0.7');
    $this->assertNoRedirect('/de');
  }

  /**
   * Redirection based on browser preferred langcode header (Accept-Language).
   *
   * When visiting the homepage on any other langcode of the browser preferred
   * language, without cookie langcode, then the end-client should be
   * redirected to the browser preferred lang.
   *
   * @dataProvider providerBrowserRedirections
   */
  public function testBrowserRedirections(string $preferred_langcode, string $path, string $expected) {
    $session = $this->getSession();
    $session->setRequestHeader(HomeRedirectLangInterface::BROWSER_HTTP_HEADER_PREFERRED_LANGCODE, $preferred_langcode);

    // Ensure the homepage will redirect to the preferred langcode.
    $this->assertRedirect($path, $expected, Response::HTTP_FOUND);
  }

  /**
   * Provides test data for the testBrowserRedirections() method.
   */
  public function providerBrowserRedirections(): iterable {
    yield ['en-US,en;q=0.9,fr;q=0.8,de;q=0.7', 'fr', '/'];
    yield ['en-US,en;q=0.9,fr;q=0.8,de;q=0.7', 'de', '/'];

    yield ['de-CH,de;q=0.9,fr;q=0.8,de;q=0.7', 'fr', '/de'];
    yield ['de-CH,de;q=0.9,fr;q=0.8,de;q=0.7', 'index.php', 'index.php/de'];

    yield ['fr-FR,fr;q=0.9,fr;q=0.8,de;q=0.7', 'de', '/fr'];
    yield ['fr-FR,fr;q=0.9,fr;q=0.8,de;q=0.7', 'index.php', 'index.php/fr'];
  }

  /**
   * Unsupported browser preferred langcode.
   *
   * When visiting the homepage on any langcode with a browser preferred lang
   * using an unsupported lang, then the end-client should not be redirected.
   */
  public function testUnsupportedLangcoderShouldNotRedirect() {
    $session = $this->getSession();
    $session->setRequestHeader(HomeRedirectLangInterface::BROWSER_HTTP_HEADER_PREFERRED_LANGCODE, 'foo');

    $this->assertNoRedirect('');
    $this->assertNoRedirect('/fr');
    $this->assertNoRedirect('/de');
  }

}
