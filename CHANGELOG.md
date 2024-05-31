# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2024-05-31
### Added
- add Drupal GitlabCI
- add coverage of Drupal 10.2.x
- add coverage of Drupal 10.3.x
- add experimental coverage of Drupal 11.0-dev
- add cspell project words for Gitlab-CI
- add phpstan.neon file

### Removed
- drop tests support on Drupal <= 9.4

### Fixed
- fix deprecation creation of dynamic property
- fix phpcs use statements should be sorted alphabetically
- fix missing call to parent::setUp() on tests
- use responseHeaderDoesNotExist instead of responseHeaderEquals with NULL value
- fix automated Drupal 10 compatibility fixes - Issue #3329302 by Project Update Bot
- fix library testing path that may be inconsistent between Github Actions & GitlabCI
- fix call to deprecated method withConsecutive() on PHPUnit
- fix deprecation by passing @dataprovider as static function
- fix deprecation using HttpKernelInterface::MAIN_REQUEST instead of HttpKernelInterface::MASTER_REQUEST until drop support of Drupal 9.x

## [1.0.0] - 2022-12-16
### Fixed
- fix Issue #3320300: Avoid "Uncaught ReferenceError: Drupal is not defined" for anonymous users
- fix parse_url(): passing null to parameter #1 () of type string is deprecated
- fix Drupal 10 (Symfony 6) Kernel Event Priorities

### Removed
- remove satackey/action-docker-layer-caching on Github Actions
- drop support of drupal below 9.3.x

### Added
- add coverage for Drupal 9.3, 9.4 & 9.5
- add official support of drupal 9.5 & 10.0

### Changed
- re-enable PHPUnit Symfony Deprecation notice

## [1.0.0-alpha1] - 2022-09-22
### Added
- init module with Cookies & Browser redirection on Homepage only
- handling of missing common JS library
- remove dependency on JQuery

[Unreleased]: https://github.com/antistatique/drupal-home-redirect-lang/compare/1.1.0...HEAD
[1.1.0]: https://github.com/antistatique/drupal-home-redirect-lang/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/antistatique/drupal-home-redirect-lang/compare/1.0.0-alpha1...1.0.0
[1.0.0-alpha1]: https://github.com/antistatique/drupal-home-redirect-lang/releases/tag/1.0.0-alpha1
