<?php

namespace Drupal\home_redirect_lang\Install\Requirements;

use Drupal\Core\Extension\InstallRequirementsInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Install time requirements for the Homepage Redirect Language module.
 *
 * Only supported by Drupal 11.2 and upper, older versions rely on the legacy
 * hook_requirements() implementation.
 *
 * @see https://www.drupal.org/project/drupal/issues/3409372
 */
final class HomeRedirectLangRequirements implements InstallRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRequirements(): array {
    $requirements = [];

    // The module code is not loaded yet: only rely on Drupal core here.
    if (\Drupal::moduleHandler()->moduleExists('page_cache')) {
      $requirements['home_redirect_lang'] = [
        'description' => new TranslatableMarkup('Homepage Redirect Language cannot be installed because the Page Cache module (page_cache) is installed and incompatible.'),
        'severity' => RequirementSeverity::Error,
      ];
    }

    return $requirements;
  }

}
