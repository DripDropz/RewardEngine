FROM php:8.3-apache-bookworm
MAINTAINER Latheesan Kanesamoorthy <latheesan87@gmail.com>

# Update base system
ARG DEBIAN_FRONTEND=noninteractive
RUN apt-get update && \
    apt-get install --no-install-recommends -y wget curl nano sudo libpng-dev libzip-dev zip unzip libicu-dev

# Install & enable required php libraries
RUN pecl install -o -f redis \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable redis \
    && docker-php-ext-install -j$(nproc) pcntl zip mysqli pdo_mysql gd bcmath intl

# Configure php & apache
RUN cp /usr/local/etc/php/php.ini-production php.ini && \
    rm -rf /etc/apache2/sites-available/* && \
    rm -rf /etc/apache2/sites-enabled/* && \
    echo 'ServerName rewardengine.app' >> /etc/apache2/apache2.conf
COPY /docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY /docker/rewardengine.app.conf /etc/apache2/sites-available/rewardengine.app.conf
RUN a2enmod rewrite && \
    a2ensite rewardengine.app

# Clean-up
RUN sudo apt-get -y purge && sudo apt-get -y clean && \
    sudo apt-get -y autoremove && sudo rm -rf /var/lib/apt/lists/* && \
    sudo rm -rf /usr/bin/apt*

# Create rewardengine user
RUN adduser --disabled-password --gecos '' rewardengine && \
    echo '%sudo ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers && \
    adduser rewardengine sudo && \
    chown -R rewardengine:rewardengine /home/rewardengine/.*

# Install composer globally
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && rm -f composer-setup.php \
    && mv composer.phar /usr/local/bin/composer

# Set rewardengine user
USER rewardengine
WORKDIR /home/rewardengine/application

# Expose ports
EXPOSE 80
