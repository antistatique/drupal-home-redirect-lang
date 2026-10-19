<?php

namespace Drupal\home_redirect_lang\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\home_redirect_lang\HomeRedirectLangInterface;

/**
 * Implements hook_preprocess_HOOK() for page.
 *
 * Add both cache context based on cookie and referer for homepage.
 */
#[Hook('preprocess_page')]
final class PreprocessPageHook {

  /**
   * Constructs a new PreprocessPageHook object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Add the cache contexts needed by the homepage redirection.
   *
   * @param array $variables
   *   The page template variables.
   */
  public function __invoke(array &$variables): void {
    if (!$variables['is_front']) {
      return;
    }

    $variables['#cache']['contexts'][] = 'cookies:' . HomeRedirectLangInterface::COOKIE_PREFERRED_LANGCODE;
    $variables['#cache']['contexts'][] = 'headers:REFERER';

    $browser_config = $this->configFactory->get('home_redirect_lang.browser_fallback');

    // When the Fallback on browser language is enabled for the first visit.
    if ($browser_config->get('enable_browser_fallback')) {
      $variables['#cache']['contexts'][] = 'headers:' . HomeRedirectLangInterface::BROWSER_HTTP_HEADER_PREFERRED_LANGCODE;
    }
  }

}
