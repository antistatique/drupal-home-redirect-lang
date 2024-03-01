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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\home_redirect_lang\EventSubscriber\HomepageCookieLanguageRedirection
 *
 * @group home_redirect_lang
 * @group home_redirect_lang_unit
 */
class HomepageCookieLanguageRedirectionTest extends UnitTestCase {

  /**
   * The event subscriber to be tested.
   *
   * @var \Drupal\home_redirect_lang\EventSubscriber\HomepageCookieLanguageRedirection
   */
  protected $cookieLanguageRedirectionEventSubscriber;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
    $container->set('url_generator', $this->urlGenerator);

    $this->request = Request::createFromGlobals();
    $this->request->headers->set('HOST', 'drupal');

    $this->requestStack = $this->getMockBuilder(RequestStack::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($this->request);

    $this->pathMatcher = $this->getMockBuilder(PathMatcher::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->languageManager = $this->getMockBuilder(ConfigurableLanguageManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->configFactory = $this->getMockBuilder(ConfigFactoryInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

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

    $immutable_config_object = $this->getMockBuilder(ImmutableConfig::class)
      ->disableOriginalConstructor()
      ->getMock();
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

    $immutable_config_object = $this->getMockBuilder(ImmutableConfig::class)
      ->disableOriginalConstructor()
      ->getMock();
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

    $immutable_config_object = $this->getMockBuilder(ImmutableConfig::class)
      ->disableOriginalConstructor()
      ->getMock();
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

    $immutable_config_object = $this->getMockBuilder(ImmutableConfig::class)
      ->disableOriginalConstructor()
      ->getMock();
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

    $immutable_config_object = $this->getMockBuilder(ImmutableConfig::class)
      ->disableOriginalConstructor()
      ->getMock();
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