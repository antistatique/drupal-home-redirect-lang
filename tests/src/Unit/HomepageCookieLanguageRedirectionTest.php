<?php

namespace Drupal\Tests\home_redirect_lang\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\home_redirect_lang\EventSubscriber\HomepageCookieLanguageRedirection;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\home_redirect_lang\EventSubscriber\HomepageCookieLanguageRedirection
 *
 * @group home_redirect_lang
 * @group home_redirect_lang_unit
 */
class HomepageCookieLanguageRedirectionTest extends UnitTestCase {

  use ProphecyTrait;
  /**
   * The event subscriber to be tested.
   *
   * @var \Drupal\home_redirect_lang\EventSubscriber\HomepageCookieLanguageRedirection
   */
  protected $cookieLanguageRedirectionEventSubscriber;


  /**
   * The event subscriber to be tested.
   *
   * @var \Drupal\home_redirect_lang\EventSubscriber\HomepageBrowserLanguageRedirection
   */
  protected $browserLanguageRedirectionEventSubscriber;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The patch matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The test Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
    $container->set('url_generator', $this->urlGenerator);

    $this->request = Request::createFromGlobals();
    $this->request->headers->set('HOST', 'drupal');

    $this->requestStack = $this->createMock(RequestStack::class);

    $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($this->request);

    $this->pathMatcher = $this->createMock(PathMatcher::class);

    $this->languageManager = $this->createMock(ConfigurableLanguageManagerInterface::class);

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);

    $this->cookieLanguageRedirectionEventSubscriber = new HomepageCookieLanguageRedirection($this->requestStack, $this->pathMatcher, $this->languageManager, $this->configFactory);
  }

  /**
   * @covers ::redirectPreferredLanguage
   */
  public function testStopOnFrontpage() {
    $kernel = $this->prophesize(HttpKernelInterface::class);
    $request = Request::create('/', 'GET');

    $event = new RequestEvent($kernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST);

    $this->pathMatcher
      ->expects($this->once())
      ->method('isFrontPage')->willReturn(FALSE);

    $this->languageManager
      ->expects($this->never())
      ->method('getCurrentLanguage');

    self::assertNull($this->cookieLanguageRedirectionEventSubscriber->redirectPreferredLanguage($event));
    self::assertNull($event->getResponse());
  }

  /**
   * @covers ::redirectPreferredLanguage
   */
  public function testStopWhenReferrerBypassEnabledAndExternal() {
    $kernel = $this->prophesize(HttpKernelInterface::class);
    $request = Request::create('/', 'GET');

    $this->request->server->set('HTTP_ACCEPT_LANGUAGE', 'fr');
    $this->request->server->set('HTTP_REFERER', 'https://www.google.ch');

    $event = new RequestEvent($kernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST);

    $currentLanguage = $this->createMock(LanguageInterface::class);
    $currentLanguage->expects($this->never())
      ->method('getId');

    $this->pathMatcher
      ->expects($this->once())
      ->method('isFrontPage')->willReturn(TRUE);

    $this->languageManager
      ->expects($this->once())
      ->method('getCurrentLanguage')
      ->willReturn($currentLanguage);

    $immutable_config_object = $this->createMock(ImmutableConfig::class);
    $immutable_config_object->expects($this->once())
      ->method('get')
      ->with('enable_referer_bypass')
      ->willReturn(TRUE);

    $this->configFactory
      ->expects($this->once())
      ->method('get')
      ->with('home_redirect_lang.cookie')
      ->willReturn($immutable_config_object);

    self::assertNull($this->cookieLanguageRedirectionEventSubscriber->redirectPreferredLanguage($event));
    self::assertNull($event->getResponse());
  }

  /**
   * @covers ::redirectPreferredLanguage
   */
  public function testStopWhenCookieNotExists() {
    $kernel = $this->prophesize(HttpKernelInterface::class);
    $request = Request::create('/', 'GET');

    $this->request->server->set('HTTP_ACCEPT_LANGUAGE', 'fr');

    $event = new RequestEvent($kernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST);

    $currentLanguage = $this->createMock(LanguageInterface::class);
    $currentLanguage->expects($this->never())
      ->method('getId');

    $this->pathMatcher
      ->expects($this->once())
      ->method('isFrontPage')->willReturn(TRUE);

    $this->languageManager
      ->expects($this->once())
      ->method('getCurrentLanguage')
      ->willReturn($currentLanguage);

    $immutable_config_object = $this->createMock(ImmutableConfig::class);
    $immutable_config_object->expects($this->once())
      ->method('get')
      ->with('enable_referer_bypass')
      ->willReturn(TRUE);

    $this->configFactory
      ->expects($this->once())
      ->method('get')
      ->with('home_redirect_lang.cookie')
      ->willReturn($immutable_config_object);

    self::assertNull($this->cookieLanguageRedirectionEventSubscriber->redirectPreferredLanguage($event));
    self::assertNull($event->getResponse());
  }

  /**
   * @covers ::redirectPreferredLanguage
   */
  public function testStopWhenCookieLanguageSameAsRequestLanguage() {
    $kernel = $this->prophesize(HttpKernelInterface::class);
    $request = Request::create('/', 'GET');

    $this->request->server->set('HTTP_ACCEPT_LANGUAGE', 'fr');
    $this->request->cookies->set('home_redirect_lang_preferred_langcode', 'fr');

    $event = new RequestEvent($kernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST);

    $currentLanguage = $this->createMock(LanguageInterface::class);
    $currentLanguage->expects($this->once())
      ->method('getId')
      ->willReturn('fr');

    $this->pathMatcher
      ->expects($this->once())
      ->method('isFrontPage')->willReturn(TRUE);

    $this->languageManager
      ->expects($this->once())
      ->method('getCurrentLanguage')
      ->willReturn($currentLanguage);

    $immutable_config_object = $this->createMock(ImmutableConfig::class);
    $immutable_config_object->expects($this->once())
      ->method('get')
      ->with('enable_referer_bypass')
      ->willReturn(TRUE);

    $this->configFactory
      ->expects($this->once())
      ->method('get')
      ->with('home_redirect_lang.cookie')
      ->willReturn($immutable_config_object);

    self::assertNull($this->cookieLanguageRedirectionEventSubscriber->redirectPreferredLanguage($event));
    self::assertNull($event->getResponse());
  }

  /**
   * @covers ::redirectPreferredLanguage
   */
  public function testStopWhenCookieLanguageNotSupported() {
    $kernel = $this->prophesize(HttpKernelInterface::class);
    $request = Request::create('/', 'GET');

    $this->request->server->set('HTTP_ACCEPT_LANGUAGE', 'fr');
    $this->request->cookies->set('home_redirect_lang_preferred_langcode', 'ru');

    $event = new RequestEvent($kernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST);

    $currentLanguage = $this->createMock(LanguageInterface::class);
    $currentLanguage->expects($this->once())
      ->method('getId')
      ->willReturn('fr');

    $this->pathMatcher
      ->expects($this->once())
      ->method('isFrontPage')->willReturn(TRUE);

    $this->languageManager
      ->expects($this->once())
      ->method('getCurrentLanguage')
      ->willReturn($currentLanguage);

    $immutable_config_object = $this->createMock(ImmutableConfig::class);
    $immutable_config_object->expects($this->once())
      ->method('get')
      ->with('enable_referer_bypass')
      ->willReturn(TRUE);

    $this->configFactory
      ->expects($this->once())
      ->method('get')
      ->with('home_redirect_lang.cookie')
      ->willReturn($immutable_config_object);

    $this->languageManager->expects($this->once())
      ->method('getLanguage')
      ->with('ru')
      ->willReturn(NULL);

    self::assertNull($this->cookieLanguageRedirectionEventSubscriber->redirectPreferredLanguage($event));
    self::assertNull($event->getResponse());
  }

  /**
   * @covers ::redirectPreferredLanguage
   */
  public function testRedirectPreferredLanguage() {
    $kernel = $this->prophesize(HttpKernelInterface::class);
    $request = Request::create('/', 'GET');

    $this->request->server->set('HTTP_ACCEPT_LANGUAGE', 'fr');
    $this->request->cookies->set('home_redirect_lang_preferred_langcode', 'en');

    $event = new RequestEvent($kernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST);

    $currentLanguage = $this->createMock(LanguageInterface::class);
    $currentLanguage->expects($this->once())
      ->method('getId')
      ->willReturn('fr');

    $this->pathMatcher
      ->expects($this->once())
      ->method('isFrontPage')->willReturn(TRUE);

    $this->languageManager
      ->expects($this->once())
      ->method('getCurrentLanguage')
      ->willReturn($currentLanguage);

    $immutable_config_object = $this->createMock(ImmutableConfig::class);
    $immutable_config_object->expects($this->once())
      ->method('get')
      ->with('enable_referer_bypass')
      ->willReturn(TRUE);

    $this->configFactory
      ->expects($this->once())
      ->method('get')
      ->with('home_redirect_lang.cookie')
      ->willReturn($immutable_config_object);

    $this->languageManager->expects($this->once())
      ->method('getLanguage')
      ->with('en')
      ->willReturn('en');

    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->willReturn('https://foo.bar');

    self::assertNull($this->cookieLanguageRedirectionEventSubscriber->redirectPreferredLanguage($event));
    self::assertInstanceOf(RedirectResponse::class, $event->getResponse());
    self::assertEquals('https://foo.bar', $event->getResponse()->getTargetUrl());
  }

}
