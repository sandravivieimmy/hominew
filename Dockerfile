# HOMI - API PHP + cliente estatico en un solo servicio (Render Web Service, runtime Docker)
FROM php:8.3-apache

RUN docker-php-ext-install mysqli \
    && a2enmod rewrite \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'variables_order=EGPCS\n' > "$PHP_INI_DIR/conf.d/zz-homi.ini"

# Render inyecta PORT; por defecto 10000
ENV PORT=10000
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && a2dissite 000-default

COPY docker/apache.conf /etc/apache2/sites-available/homi.conf
RUN a2ensite homi

# Backend (includes/, models/, controllers/, vendor/ ...) fuera del DocumentRoot, salvo public/
COPY server/ /var/www/server/
# Cliente (index.html, js/, styles/) dentro de public/ -> mismo dominio que la API
COPY client/ /var/www/server/public/

RUN chown -R www-data:www-data /var/www/server

EXPOSE 10000
