FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql mysqli

RUN a2enmod rewrite

RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf