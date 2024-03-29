ARG BASE_IMAGE_TAG=10.1
FROM wengerk/drupal-for-contrib:${BASE_IMAGE_TAG}

ARG BASE_IMAGE_TAG
ENV BASE_IMAGE_TAG=${BASE_IMAGE_TAG}

# Disable deprecation notice.
# ENV SYMFONY_DEPRECATIONS_HELPER=disabled

# Register the Drupal and DrupalPractice Standard with PHPCS.
#RUN ./vendor/bin/phpcs --config-set installed_paths \
#    `pwd`/vendor/drupal/coder/coder_sniffer

# Copy the Analyzer definition files to ease run.
COPY phpcs.xml.dist phpmd.xml ./

# Download & install PHPMD.
RUN set -eux; \
  curl -LJO https://phpmd.org/static/latest/phpmd.phar; \
  chmod +x phpmd.phar; \
  mv phpmd.phar /usr/bin/phpmd

# Download & install PHPCPD.
RUN set -eux; \
  curl -LJO https://phar.phpunit.de/phpcpd.phar; \
  chmod +x phpcpd.phar; \
  mv phpcpd.phar /usr/bin/phpcpd

# Download & install PhpDeprecationDetector.
RUN set -eux; \
  \
  apt-get update; \
  apt-get install -y \
   libbz2-dev \
  ; \
  \
  docker-php-ext-install bz2; \
  \
  curl -LJO https://github.com/wapmorgan/PhpDeprecationDetector/releases/download/2.0.29/phpdd-2.0.29.phar; \
  chmod +x phpdd-2.0.29.phar; \
  mv phpdd-2.0.29.phar /usr/bin/phpdd
