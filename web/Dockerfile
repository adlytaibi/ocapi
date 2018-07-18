FROM php:7.0-apache
RUN apt-get clean && apt-get update
COPY src/ /var/www/html/
COPY apache2/servername.conf /etc/apache2/conf-available/
COPY apache2/security.conf /etc/apache2/conf-available/
COPY apache2/ssl.conf /etc/apache2/mods-available/
COPY apache2/ports.conf /etc/apache2/
COPY php/php.ini /usr/local/etc/php/
COPY sslkeys/host.pem /etc/ssl/certs/ssl-cert-snakeoil.pem
COPY sslkeys/host.key /etc/ssl/private/ssl-cert-snakeoil.key
RUN a2ensite default-ssl
RUN a2enconf security
RUN a2enconf servername
RUN a2enmod ssl
RUN chown -R www-data:www-data /var/www/html/
EXPOSE 443
