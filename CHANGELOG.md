# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Fixed
- fix Issue #3320300: Avoid "Uncaught ReferenceError: Drupal is not defined" for anonymous users
- fix parse_url(): passing null to parameter #1 () of type string is deprecated

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

[1.0.0-alpha1]: https://github.com/antistatique/drupal-home-redirect-lang/releases/tag/1.0.0-alpha1
