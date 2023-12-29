# Usa la imagen oficial de PHP como base
FROM php:8.2.14-apache

# Copia el código de tu aplicación al contenedor
COPY ./app /var/www/html

# Expone el puerto 80
EXPOSE 80

# Habilitar módulos deseados
RUN docker-php-ext-enable sodium

# Limpiar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Usa la imagen oficial de PgAdmin
FROM dpage/pgadmin4
