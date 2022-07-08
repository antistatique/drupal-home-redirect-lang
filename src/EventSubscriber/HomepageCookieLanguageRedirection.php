<?php

namespace Drupal\home_redirect_lang\EventSubscriber;

use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Url;
use Drupal\home_redirect_lang\HomeRedirectLangInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirect visitor to there preferred language based on Cookie values.
 *
 * The redirection will not be fired when the REFERER header has been given.
 * The redirection will only be triggered when landing on homepage.
 */
class HomepageCookieLanguageRedirection implements EventSubscriberInterface {

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
   * Constructs a new LanguageRedirection object.
   */
  public function __construct(RequestStack $request_stack, PathMatcher $path_matcher, ConfigurableLanguageManagerInterface $language_manager) {
    $this->request = $request_stack->getCurrentRequest();
    $this->pathMatcher = $path_matcher;
    $this->languageManager = $language_manager;
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

    $current_language = $this->languageManager->getCurrentLanguage();
    $http_referer = $this->request->server->get('HTTP_REFERER');
    $current_host = $this->request->getHost();
    $referer_host = parse_url($http_referer, PHP_URL_HOST);

    // Ensure the REFERER is external to disable redirection.
    if (!empty($referer_host) && !empty($current_host) && $current_host !== $referer_host) {
      return;
    }

    // Trigger a redirection when visiting the homepage in another lang than
    // then stored preferred one.
    if ($this->request->cookies->has(HomeRedirectLangInterface::COOKIE_PREFERRED_LANGCODE)
      && $this->request->cookies->get(HomeRedirectLangInterface::COOKIE_PREFERRED_LANGCODE) !== $current_language->getId()
    ) {

      // Ensure the stored langcode on the cookie is supported by Drupal.
      $destination_language = $this->languageManager->getLanguage($this->request->cookies->get(HomeRedirectLangInterface::COOKIE_PREFERRED_LANGCODE));
      if (!$destination_language) {
        return;
      }

      $url = Url::fromRoute('<front>', [], ['language' => $destination_language]);

      $response = new RedirectResponse($url->toString(), Response::HTTP_FOUND);
      $event->setResponse($response);
    }
  }

}
