<?php

namespace Drupal\Tests\home_redirect_lang\Functional;

use Drupal\home_redirect_lang\HomeRedirectLangInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cover the Cookie Redirection.
 *
 * @group home_redirect_lang
 * @group home_redirect_lang_functional
 * @group home_redirect_lang_cookie
 *
 * @internal
 * @coversNothing
 */
final class CookieRedirectionFunctionalTest extends FunctionalTestBase {
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
  }

  /**
   * Prevent redirection without langcode cookie.
   *
   * By default, on the first visite (aka without having the preferred lang
   * cookie) the end-client should not be redirected anywhere.
   */
  public function testWithoutCookieShouldNotBeRedirected() {
    $this->assertNoRedirect('/node/1');
    $this->assertNoRedirect('/fr/node/1');
    $this->assertNoRedirect('/de/node/1');

    $this->assertNoRedirect('');
    $this->assertNoRedirect('/fr');
    $this->assertNoRedirect('/de');
  }

  /**
   * Prevent redirecting when cookie and current pare are identical.
   *
   * When visiting the homepage on the same langcode of the preferred language
   * cookie, then the end-client should not be redirected anywhere.
   */
  public function testCookieRedirectionsSameLangcode() {
    $session = $this->getSession();
    $session->setCookie(HomeRedirectLangInterface::COOKIE_PREFERRED_LANGCODE, 'en');
    $this->assertNoRedirect('');

    $this->assertNoRedirect('/node/1');
    $this->assertNoRedirect('/fr/node/1');
    $this->assertNoRedirect('/de/node/1');

    $session->setCookie(HomeRedirectLangInterface::COOKIE_PREFERRED_LANGCODE, 'fr');
    $this->assertNoRedirect('fr');

    $this->assertNoRedirect('/node/1');
    $this->assertNoRedirect('/fr/node/1');
    $this->assertNoRedirect('/de/node/1');

    $session->setCookie(HomeRedirectLangInterface::COOKIE_PREFERRED_LANGCODE, 'de');
    $this->assertNoRedirect('de');

    $this->assertNoRedirect('/node/1');
    $this->assertNoRedirect('/fr/node/1');
    $this->assertNoRedirect('/de/node/1');
  }

  /**
   * Redirection based on cookie stored langcode.
   *
   * When visiting the homepage on an other langcode of the preferred language
   * cookie, then the end-client should be redirected to the preferred lang.
   *
   * @dataProvider providerCookieRedirections
   */
  public function testCookieRedirections(string $preferred_langcode, string $path, string $expected) {
    $session = $this->getSession();
    $session->setCookie(HomeRedirectLangInterface::COOKIE_PREFERRED_LANGCODE, $preferred_langcode);

    // Be sure there is no redirection on node.
    $this->assertNoRedirect('/node/1');
    $this->assertNoRedirect('/fr/node/1');
    $this->assertNoRedirect('/de/node/1');

    // Ensure the homepage will redirect to the preferred langcode.
    $this->assertRedirect($path, $expected, Response::HTTP_FOUND);
  }

  /**
   * Provides test data for the testCookieRedirections() method.
   */
  public function providerCookieRedirections(): iterable {
    yield ['en', 'fr', '/'];

    yield ['en', 'de', '/'];

    yield ['de', 'fr', '/de'];

    yield ['de', 'index.php', 'index.php/de'];

    yield ['fr', 'de', '/fr'];

    yield ['fr', 'index.php', 'index.php/fr'];
  }

  /**
   * Unsupported cookie langcode.
   *
   * When visiting the homepage on any langcode with a cookie using an
   * unsupported langcode cookie, then the end-client should not be redirected.
   */
  public function testCookieWillNotRedirectUnsupportedLangcode() {
    $session = $this->getSession();
    $session->setCookie(HomeRedirectLangInterface::COOKIE_PREFERRED_LANGCODE, 'foo');

    $this->assertNoRedirect('');
    $this->assertNoRedirect('fr');
    $this->assertNoRedirect('de');
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
