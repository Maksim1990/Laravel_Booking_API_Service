FROM php:8.1.0-fpm-alpine

RUN apk --update add wget \
  curl \
  git \
  grep \
  build-base \
  libmcrypt-dev \
  libxml2-dev \
  pcre-dev \
  libtool \
  make \
  autoconf \
  g++ \
  cyrus-sasl-dev \
  libgsasl-dev \
  supervisor \
  zip \
  nano \
  vim \
  libzip-dev \
  pkgconfig curl-dev openssl-dev \
  gmp gmp-dev
RUN pecl channel-update pecl.php.net

RUN pecl install redis
    #&& pecl install xdebug-3.0.0
RUN docker-php-ext-enable redis
#RUN docker-php-ext-enable redis xdebug
RUN docker-php-ext-configure zip
RUN docker-php-ext-install -j$(nproc) pdo_mysql zip mysqli bcmath pcntl gmp
RUN apk add --no-cache libpng libpng-dev freetype-dev libjpeg-turbo-dev libpng-dev \
        && docker-php-ext-install gd

RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Install composer and update path
ENV COMPOSER_HOME /composer
ENV PATH /composer/vendor/bin:$PATH
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Add global laravel installer
#RUN composer global require laravel/installer
## Create sample laravel app
#RUN laravel new laravel-test-app

# Set working directory PHP
WORKDIR /var/www

#COPY ./storage /var/www/storage
#COPY ./scripts/set_storage_write_permissions.sh /var/www/set_storage_write_permissions.sh
#RUN /var/www/set_storage_write_permissions.sh

#### Run image under non Root user
#RUN adduser --disabled-password --gecos "" maksim
#USER maksim

CMD ["php-fpm"]