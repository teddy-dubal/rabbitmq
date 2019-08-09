#++++++++++++++++++++++++++++++++++++++
# PHP application Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:7.3
LABEL author="Teddy Dona <teddy.dona@gmail.com>"

RUN apt-get -qq update && apt-get -qq -y install librabbitmq-dev \
    && pecl install amqp \
    && docker-php-ext-enable amqp \
    && docker-service disable postfix \
    && docker-service disable ssh \
    && docker-service disable cron \
    && docker-service disable dnsmasq \
    && docker-service disable php-fpm \
    && ln -sf /usr/local/bin/php /usr/bin/php
