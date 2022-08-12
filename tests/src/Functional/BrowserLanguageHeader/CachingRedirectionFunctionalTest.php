<?php

namespace Drupal\Tests\home_redirect_lang\Functional\BrowserLanguageHeader;

use Drupal\home_redirect_lang\HomeRedirectLangInterface;
use Drupal\Tests\home_redirect_lang\Functional\FunctionalTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Cover the caching strategy of the BrowserHeader Redirection.
 *
 * @group home_redirect_lang
 * @group home_redirect_lang_functional
 * @group home_redirect_lang_browser_cache
 *
 * @internal
 * @coversNothing
 */
final class CachingRedirectionFunctionalTest extends FunctionalTestBase {
  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'dynamic_page_cache',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->setUpLanguages();

    // Be sure The Anonymous Page Cache is disabled as incompatible.
    \Drupal::service('module_installer')->uninstall(['page_cache'], FALSE);

    $settings = $this->container->get('config.factory')->getEditable('home_redirect_lang.browser_fallback');
    $settings->set('enable_browser_fallback', TRUE)->save();
  }

  /**
   * Cacheable BrowserHeader redirections.
   *
   * When visiting the homepage with a browser preferred language header,
   * the redirection results must be cached by header & URLs variations.
   *
   * @dataProvider providerCachedBrowserHeaderRedirections
   */
  public function testCachingBrowserHeaderRedirections(array $scenarios) {
    foreach ($scenarios as $scenario) {
      [$preferred_langcode, $path, $expected, $cache_contexts, $cache] = $scenario;

      $session = $this->getSession();
      $session->setRequestHeader(HomeRedirectLangInterface::BROWSER_HTTP_HEADER_PREFERRED_LANGCODE, $preferred_langcode);

      $this->drupalGet($path);
      $this->assertSession()->addressEquals($expected);
      $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', $cache);

      $cache_contexts = array_merge($cache_contexts, [
        'cookies:home_redirect_lang_preferred_langcode',
        'headers:Accept-Language',
        'headers:REFERER',
        'languages:language_interface',
        'route',
        'theme',
        'url.path',
        'url.query_args',
        'user.permissions',
        'user.roles:authenticated',
      ]);

      $this->assertCacheContexts($cache_contexts);
    }
  }

  /**
   * Provides test data for the testCachingBrowserHeaderRedirections() method.
   */
  public function providerCachedBrowserHeaderRedirections(): iterable {
    yield [[
      [
        'fr',
        '<front>',
        '/fr',
        ['languages:language_url'],
        'MISS',
      ],
      [
        'fr',
        '/de',
        '/fr',
        ['languages:language_url'],
        'MISS',
      ],
      [
        'fr',
        '/fr',
        '/fr',
        ['languages:language_url'],
        'HIT',
      ],
      [
        'de',
        '<front>',
        '/de',
        ['languages:language_url'],
        'MISS',
      ],
      [
        'de',
        '/fr',
        '/de',
        ['languages:language_url'],
        'MISS',
      ],
      [
        'de',
        '/de',
        '/de',
        ['languages:language_url'],
        'HIT',
      ],
      [
        'en',
        '/fr',
        '/',
        [],
        'MISS',
      ],
      [
        'en',
        '/de',
        '/',
        [],
        'MISS',
      ],
      [
        'en',
        '<front>',
        '/',
        [],
        'HIT',
      ],
      [
        '',
        '<front>',
        '/',
        [],
        'MISS',
      ],
      [
        '',
        '/fr',
        '/fr',
        ['languages:language_url'],
        'MISS',
      ],
      [
        '',
        '/de',
        '/de',
        ['languages:language_url'],
        'MISS',
      ],
    ],
    ];
  }

}
