# Developing on Entity to Text

* Pull requests can be made against
https://github.com/antistatique/drupal-home-redirect-lang/pulls

## 📦 Repositories

Github repo

  ```
  $ git remote add github git@github.com:antistatique/drupal-home-redirect-lang.git
  ```

Drupal repo

  ```
  $ git remote add drupal git@git.drupal.org:project/home_redirect_lang.git
  ```

## 🔧 Prerequisites

First, you will need to have the following tools installed
globally on your environment:

  * drush
  * Latest dev release of Drupal 9.x/10.x.
  * docker
  * docker-compose

### Project bootstrap

Once run, you will be able to access to your fresh installed Drupal on `localhost::8888`.

    docker-compose build --pull --build-arg BASE_IMAGE_TAG=9.3 drupal
    (get a coffee, this will take some time...)
    docker-compose up -d drupal chrome
    docker-compose exec -u www-data drupal drush site-install demo_umami --db-url="mysql://drupal:drupal@db/drupal" --site-name=Example -y

    # You may be interesed by reseting the admin passowrd of your Docker and install the module using those cmd.
    docker-compose exec drupal drush user:password admin admin
    docker-compose exec drupal drush pmu page_cache
    docker-compose exec drupal drush en home_redirect_lang

## 🏆 Tests

We use the [Docker for Drupal Contrib images](https://hub.docker.com/r/wengerk/drupal-for-contrib) to run testing on our project.

Run testing by stopping at first failure using the following command:

    docker-compose exec -u www-data drupal phpunit --group=home_redirect_lang --no-coverage --stop-on-failure --configuration=/var/www/html/phpunit.xml

## 🚔 Check Drupal coding standards & Drupal best practices

During Docker build, the following Static Analyzers will be installed on the Docker `drupal` via Composer:

- `drupal/coder^8.3.1`  (including `squizlabs/php_codesniffer` & `phpstan/phpstan`),

The following Analyzer will be downloaded & installed as PHAR:

- `phpmd/phpmd`
- `sebastian/phpcpd`
- `wapmorgan/PhpDeprecationDetector`
- `vimeo/psalm`

### Command Line Usage

    ./scripts/hooks/post-commit
    # or run command on the container itself
    docker-compose exec drupal bash

#### Running Code Sniffer Drupal & DrupalPractice

https://github.com/squizlabs/PHP_CodeSniffer

PHP_CodeSniffer is a set of two PHP scripts; the main `phpcs` script that tokenizes PHP, JavaScript and CSS files to
detect violations of a defined coding standard, and a second `phpcbf` script to automatically correct coding standard
violations.
PHP_CodeSniffer is an essential development tool that ensures your code remains clean and consistent.

  ```
  $ docker-compose exec drupal ./vendor/bin/phpcs ./web/modules/contrib/home_redirect_lang/
  ```

Automatically fix coding standards

  ```
  $ docker-compose exec drupal ./vendor/bin/phpcbf ./web/modules/contrib/home_redirect_lang/
  ```

#### Running PHP Mess Detector

https://github.com/phpmd/phpmd

Detect overcomplicated expressions & Unused parameters, methods, properties.

  ```
  $ docker-compose exec drupal phpmd ./web/modules/custom text ./phpmd.xml \
  --suffixes php,module,inc,install,test,profile,theme,css,info,txt --exclude *Test.php,*vendor/*
  ```

  ```
  $ docker-compose exec drupal phpmd text ./phpmd.xml \
  --suffixes php,module,inc,install,test,profile,theme,css,info,txt --exclude *Test.php,*vendor/*
  ```

#### Running PHP Copy/Paste Detector

https://github.com/sebastianbergmann/phpcpd

`phpcpd` is a Copy/Paste Detector (CPD) for PHP code.

  ```
  $ docker-compose exec drupal phpcpd ./web/modules/custom ./behat \
--names=*.php,*.module,*.inc,*.install,*.test,*.profile,*.theme,*.css,*.info,*.txt --names-exclude=*.md,*.info.yml \
--exclude tests --exclude vendor/ --ansi
  ```

#### Running PhpDeprecationDetector

https://github.com/wapmorgan/PhpDeprecationDetector

A scanner that checks compatibility of your code with PHP interpreter versions.

  ```
  $ docker-compose exec drupal phpdd ./web/modules/custom ./behat \
    --file-extensions php,module,inc,install,test,profile,theme,info --exclude vendor
  ```
