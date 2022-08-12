<?php

namespace Drupal\Tests\home_redirect_lang\FunctionalJavascript;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\Core\Language\LanguageInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\home_redirect_lang\HomeRedirectLangInterface;
use WebDriver\Exception\UnknownError;

/**
 * Ensure the Language Switcher Cookie handler works as design.
 *
 * Inspired from \Drupal\Tests\language\Functional\LanguageSwitchingTest.
 *
 * @group home_redirect_lang
 * @group home_redirect_lang_functional
 * @group home_redirect_lang_functional_js
 *
 * @internal
 * @coversNothing
 */
final class LanguageSwitcherHandlerTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'locale',
    'locale_test',
    'language',
    'block',
    'language_test',
    'menu_ui',
    'home_redirect_lang',
  ];

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create and log in user.
    $this->adminUser = $this->drupalCreateUser([
      'administer blocks',
      'administer languages',
      'access administration pages',
    ]);
  }

  /**
   * Ensure the module JS libraries are attached on Language Switcher block.
   */
  public function testLanguageSwitcherLibrary(): void {
    $xpath_common_js = $this->assertSession()->buildXPathQuery('//script[contains(@src, :value)]', [':value' => '/modules/contrib/home_redirect_lang/js/home_redirect_lang.common.js']);
    $xpath_switcher_js = $this->assertSession()->buildXPathQuery('//script[contains(@src, :value)]', [':value' => '/modules/contrib/home_redirect_lang/js/home_redirect_lang.language_switcher.js']);

    $common_js = $this->getSession()->getPage()->find('xpath', $xpath_common_js);
    $switcher_js = $this->getSession()->getPage()->find('xpath', $xpath_switcher_js);

    self::assertEmpty($common_js);
    self::assertEmpty($switcher_js);

    // Ensure the language switcher block is enabled.
    $this->setupLanguageBlock();

    $this->drupalGet('<front>');

    $common_js = $this->getSession()->getPage()->find('xpath', $xpath_common_js);
    $switcher_js = $this->getSession()->getPage()->find('xpath', $xpath_switcher_js);

    if (empty($common_js)) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'Common JavaScript library missing.', 'script', 'home_redirect_lang.common.js');
    }

    if (empty($switcher_js)) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'Language Switcher JavaScript library missing.', 'script', 'home_redirect_lang.language_switcher.js');
    }
  }

  /**
   * Ensure the module JavasScript on Language Switcher add Cookie.
   */
  public function testLanguageSwitcherCookie(): void {
    // Ensure the language switcher block is enabled.
    $this->setupLanguageBlock();

    $cookie_preferred_lang = NULL;

    try {
      $cookie_preferred_lang = $this->getSession()->getDriver()->getWebDriverSession()->getCookie(HomeRedirectLangInterface::COOKIE_PREFERRED_LANGCODE);
    }
    catch (UnknownError $e) {
      // getCookie will throw an exception when the cookie is not found.
    } finally {
      self::assertNull($cookie_preferred_lang);
    }

    // Navigate to the homepage and change the language to french.
    $this->drupalGet('<front>');
    $this->getSession()->getPage()->clickLink('français');

    $cookie_preferred_lang = $this->getSession()->getDriver()->getWebDriverSession()->getCookie(HomeRedirectLangInterface::COOKIE_PREFERRED_LANGCODE);
    self::assertEquals('/', $cookie_preferred_lang['path']);
    self::assertEquals('fr', $cookie_preferred_lang['value']);
    self::assertFalse($cookie_preferred_lang['secure']);
    self::assertFalse($cookie_preferred_lang['httpOnly']);
  }

  /**
   * Saves the native name of a language entity in configuration as a label.
   *
   * @param string $langcode
   *   The language code of the language.
   * @param string $label
   *   The native name of the language.
   */
  protected function saveNativeLanguageName($langcode, $label): void {
    \Drupal::service('language.config_factory_override')
      ->getOverride($langcode, 'language.entity.' . $langcode)->set('label', $label)->save();
  }

  /**
   * Place the Language Switcher Block.
   */
  private function setupLanguageBlock(): void {
    $this->drupalLogin($this->adminUser);

    // Add language.
    $edit = [
      'predefined_langcode' => 'fr',
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    // Set the native language name.
    $this->saveNativeLanguageName('fr', 'français');

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => '1'];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');

    // Enable the language switcher block.
    $this->drupalPlaceBlock('language_block:' . LanguageInterface::TYPE_INTERFACE, ['id' => 'test_language_block']);

    $this->drupalLogout();
  }

}
