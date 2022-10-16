FROM php:8.1.11-apache

RUN apt-get update

RUN apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev unzip locales zlib1g-dev libzip-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd pdo_mysql zip

RUN a2enmod rewrite

RUN sed -i -e 's/# ru_RU.UTF-8 UTF-8/ru_RU.UTF-8 UTF-8/' /etc/locale.gen && locale-gen

ENV LANG ru_RU.UTF-8
ENV LANGUAGE ru_RU:ru
ENV LC_LANG ru_RU.UTF-8
ENV LC_ALL ru_RU.UTF-8

ENV TZ Europe/Moscow
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

ADD ./000-default.conf /etc/apache2/sites-available

# RUN chown -R www-data:www-data /var/www/html

# RUN usermod -aG 0 www-data

# RUN adduser www-data root

# RUN chown -R www-data:www-data /var/www

# USER www-data

# ENV APACHE_RUN_USER root
# ENV APACHE_RUN_GROUP root

# RUN usermod --non-unique --uid 0 www-data

RUN groupadd -r app -g 1000 && useradd -u 1000 -r -g app -m -d /app -s /sbin/nologin -c "App user" app && \
  chmod 755 /var/www/html

CMD ["apache2-foreground"]

# CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
