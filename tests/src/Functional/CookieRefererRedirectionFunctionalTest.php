<?php

namespace Drupal\Tests\home_redirect_lang\Functional;

use Drupal\home_redirect_lang\HomeRedirectLangInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cover the HTTP Referer Header preventing Cookie Redirections.
 *
 * When visiting the homepage with an External REFERER Header on any langcode
 * even different of the preferred language cookie should never trigger
 * redirection to the end-client.
 *
 * @group home_redirect_lang
 * @group home_redirect_lang_functional
 * @group home_redirect_lang_cookie
 */
class CookieRefererRedirectionFunctionalTest extends FunctionalTestBase {
  use AssertRedirectTrait;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->setUpLanguages();
    $this->setUpArticles();
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
   * @dataProvider providerCookieRedirections
   */
  public function testRefererExternalCookieRedirections(string $preferred_langcode, string $path) {
    $session = $this->getSession();
    $session->setCookie(HomeRedirectLangInterface::COOKIE_PREFERRED_LANGCODE, $preferred_langcode);
    $session->setRequestHeader('referer', 'https://www.google.ch');

    // Ensure the homepage will not trigger any redirecion because of referer.
    $this->assertNoRedirect($path);
  }

  /**
   * Internal Referer should still generate redirection.
   *
   * @dataProvider providerCookieRedirections
   */
  public function testRefererInernalCookieRedirections(string $preferred_langcode, string $path) {
    $session = $this->getSession();
    $session->setCookie(HomeRedirectLangInterface::COOKIE_PREFERRED_LANGCODE, $preferred_langcode);
    $session->setRequestHeader('referer', '/node/1');

    // Be sure there is no redirection on node.
    $this->assertNoRedirect('/node/1');
    $this->assertNoRedirect('/fr/node/1');
    $this->assertNoRedirect('/de/node/1');

    $url = $this->getAbsoluteUrl($path);
    $this->getSession()->visit($url);

    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(Response::HTTP_FOUND);
  }

  /**
   * Provides test data for the testCookieRedirections() method.
   */
  public function providerCookieRedirections(): iterable {
    yield ['en', 'fr'];
    yield ['en', 'de'];

    yield ['de', 'fr'];
    yield ['de', 'index.php'];

    yield ['fr', 'de'];
    yield ['fr', 'index.php'];
  }

}
