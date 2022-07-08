<?php

namespace Drupal\Tests\home_redirect_lang\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Base class initializing multilingual Drupal with article entity and.
 */
abstract class FunctionalTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'locale',
    'language',
    'user',
    'home_redirect_lang',
  ];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Sets up languages (fr & de) needed for test.
   *
   * The english (en) langcode will be available by default on functional tests.
   */
  protected function setUpLanguages() {
    // English (en) is created by default.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('de')->save();
  }

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {

    parent::setUp();

    /** @var \Drupal\Core\Entity\EntityTypeManager $entityTypeManager */
    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Setup default articles node for testing.
   *
   * Summary:
   * | Nid | Title    | EN           | DE | FR           |
   * |-----|----------|--------------|----|--------------|
   * |   1 | News N°1 | X (original) |    |              |
   */
  protected function setUpArticles() {
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    $article = $this->entityTypeManager->getStorage('node')->create([
      'type'  => 'article',
      'title' => 'News N°1',
    ]);
    $article->save();
  }

}
