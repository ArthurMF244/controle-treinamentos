FROM php:8.2-apache

WORKDIR /var/www/html

# Instala apenas as extensões necessárias para MySQL/PDO
RUN docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite

# Configuração do Apache
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

# EntryPoint da aplicação
COPY docker/php-entrypoint.sh /usr/local/bin/app-entrypoint

RUN sed -i 's/\r$//' /usr/local/bin/app-entrypoint \
    && chmod +x /usr/local/bin/app-entrypoint

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

ENTRYPOINT ["/usr/local/bin/app-entrypoint"]
CMD ["apache2-foreground"]