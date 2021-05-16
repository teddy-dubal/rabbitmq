#++++++++++++++++++++++++++++++++++++++
# PHP application Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:7.4
LABEL author="Teddy Dona <teddy.dona@gmail.com>"

RUN docker-service disable postfix \
    && docker-service disable ssh \
    && docker-service disable cron \
    && docker-service disable dnsmasq \
    && docker-service disable php-fpm \
    && ln -sf /usr/local/bin/php /usr/bin/php
