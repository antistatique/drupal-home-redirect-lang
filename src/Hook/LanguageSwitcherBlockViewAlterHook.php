<?php

namespace Drupal\home_redirect_lang\Hook;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Implements hook_block_view_alter().
 *
 * Inject JavaScript switcher handler of the Homepage Redirect Language module
 * when the drupal language is rendered in any page.
 */
#[Hook('block_view_alter')]
final class LanguageSwitcherBlockViewAlterHook {

  /**
   * Attach the language switcher library to the language interface block.
   *
   * @param array $build
   *   A renderable array representing the block.
   * @param \Drupal\Core\Block\BlockPluginInterface $block
   *   The block plugin instance.
   */
  public function __invoke(array &$build, BlockPluginInterface $block): void {
    if ($block->getPluginId() !== 'language_block:language_interface') {
      return;
    }

    $build['#attached']['library'][] = 'home_redirect_lang/language_switcher';
  }

}
