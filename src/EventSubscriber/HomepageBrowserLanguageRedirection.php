<?php

namespace Drupal\home_redirect_lang\EventSubscriber;

use Drupal\Component\Utility\UserAgent;
use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Url;
use Drupal\home_redirect_lang\HomeRedirectLangInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Redirect visitor to there preferred from the browser HTTP header.
 *
 * The redirection will not be fired when the REFERER header has been given.
 * The redirection will only be triggered when landing on homepage.
 * The option "Fallback redirection using visitor browser preferred language"
 * must be enabled.
 */
class HomepageBrowserLanguageRedirection implements EventSubscriberInterface {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Drupal\language\ConfigurableLanguageManagerInterface definition.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new Homepage BrowserLanguageRedirection object.
   */
  public function __construct(RequestStack $request_stack, PathMatcher $path_matcher, ConfigurableLanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory) {
    $this->request = $request_stack->getCurrentRequest();
    $this->pathMatcher = $path_matcher;
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['redirectPreferredLanguage'],
    ];
  }

  /**
   * Redirect visitor to there preferred language when landing on homepage.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event.
   */
  public function redirectPreferredLanguage(RequestEvent $event) {
    if (!$this->pathMatcher->isFrontPage()) {
      return;
    }

    // Don't redirect when the fallback on browser preferred lang is disabled.
    $enabled = $this->configFactory->get('home_redirect_lang.browser_fallback')->get('enable_browser_fallback');
    if (!$enabled) {
      return;
    }

    // Get the header Accept-Language from the browser.
    $http_accept_language = $this->request->server->get(HomeRedirectLangInterface::SERVER_HTTP_PREFERRED_LANGCODE);
    if (!$http_accept_language) {
      return;
    }

    // Whether or not preventing redirection when Referer Header is given.
    $referer_bypass_enabled = (bool) $this->configFactory->get('home_redirect_lang.browser_fallback')->get('enable_referer_bypass');

    /** @var \Drupal\Core\Language\Language $current_language */
    $current_language = $this->languageManager->getCurrentLanguage();
    $http_referer = $this->request->server->get('HTTP_REFERER');
    $current_host = $this->request->getHost();
    $referer_host = parse_url($http_referer, PHP_URL_HOST);

    // Ensure the REFERER is external to disable redirection.
    if ($referer_bypass_enabled && !empty($referer_host) && !empty($current_host) && $current_host !== $referer_host) {
      return;
    }

    // When the preferred language cookie exists, then use it instead of the
    // browser fallback.
    if ($this->request->cookies->has(HomeRedirectLangInterface::COOKIE_PREFERRED_LANGCODE)) {
      return;
    }

    $langcodes = array_keys($this->languageManager->getLanguages());
    $mappings = $this->configFactory->get('language.mappings')->get('map');
    /** @var string $destination_langcode */
    $destination_langcode = UserAgent::getBestMatchingLangcode($http_accept_language, $langcodes, $mappings);

    if ($current_language->getId() === $destination_langcode) {
      return;
    }

    // Ensure the stored langcode on the cookie is supported by Drupal.
    /** @var \Drupal\Core\Language\Language|null $destination_language */
    $destination_language = $this->languageManager->getLanguage($destination_langcode);
    if (!$destination_language) {
      return;
    }

    $url = Url::fromRoute('<front>', [], ['language' => $destination_language]);

    $response = new RedirectResponse($url->toString(), Response::HTTP_FOUND);
    $event->setResponse($response);
  }

}
