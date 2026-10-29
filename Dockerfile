FROM php:8.4-apache

RUN apt-get update \
	&& apt-get install -y --no-install-recommends libonig-dev \
	&& docker-php-ext-install pdo_mysql mbstring \
	&& a2enmod headers \
	&& rm -rf /var/lib/apt/lists/*

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache-security.conf /etc/apache2/conf-available/zz-app-security.conf
COPY docker/php-security.ini /usr/local/etc/php/conf.d/zz-app-security.ini

RUN a2enconf zz-app-security

WORKDIR /var/www/html