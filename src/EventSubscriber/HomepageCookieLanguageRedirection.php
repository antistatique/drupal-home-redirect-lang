<?php

namespace Drupal\home_redirect_lang\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Url;
use Drupal\home_redirect_lang\HomeRedirectLangInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirect visitor to there preferred language based on Cookie values.
 *
 * The redirection will not be fired when the REFERER header has been given.
 * The redirection will only be triggered when landing on homepage.
 */
class HomepageCookieLanguageRedirection implements EventSubscriberInterface {

  /**
   * The Cookie Redirection must be triggered after the Browser redirection.
   *
   * The value here must be lower than
   * {@HomepageBrowserLanguageRedirection::PRIORITY}.
   * This needs to run after \Symfony\Component\HttpKernel\EventListener\RouterListener::onKernelRequest(),
   * which has a priority of 32.
   * This needs to run after \Drupal\home_redirect_lang\EventSubscriber\HomepageBrowserLanguageRedirection::redirectPreferredLanguage(),
   * which has a priority of 31.
   *
   * @var int
   */
  private const PRIORITY = 30;

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
   * Constructs a new LanguageRedirection object.
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
      KernelEvents::REQUEST => ['redirectPreferredLanguage', self::PRIORITY],
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

    // Whether preventing redirection when Referer Header is given.
    $referer_bypass_enabled = (bool) $this->configFactory->get('home_redirect_lang.cookie')->get('enable_referer_bypass');

    $current_language = $this->languageManager->getCurrentLanguage();
    $http_referer = $this->request->server->get('HTTP_REFERER');
    $current_host = $this->request->getHost();
    $referer_host = parse_url((string) $http_referer, \PHP_URL_HOST);

    // Ensure the REFERER is external to disable redirection.
    if ($referer_bypass_enabled && !empty($referer_host) && !empty($current_host) && $current_host !== $referer_host) {
      return;
    }

    // Trigger a redirection when visiting the homepage in another lang than
    // then stored preferred one.
    if ($this->request->cookies->has(HomeRedirectLangInterface::COOKIE_PREFERRED_LANGCODE)
      && $this->request->cookies->get(HomeRedirectLangInterface::COOKIE_PREFERRED_LANGCODE) !== $current_language->getId()
    ) {

      // Ensure the stored langcode on the cookie is supported by Drupal.
      /** @var \Drupal\Core\Language\Language|null $destination_language */
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
