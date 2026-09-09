FROM php:8.2-apache

# 1. Instalar dependencias del sistema necesarias (zip/unzip para Composer)
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql mysqli zip \
    && rm -rf /var/lib/apt/lists/*

# 2. Copiar el binario oficial de Composer al contenedor
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Habilitar mod_rewrite de Apache para URLs amigables / .htaccess
RUN a2enmod rewrite

# 4. Copiar la carpeta pública al directorio raíz de Apache
COPY src/public/ /var/www/html/

# 5. Copiar la carpeta privada fuera de la raíz web de Apache
COPY src/app/ /var/www/app/

# 6. Configuración VirtualHost al contenedor
COPY apache/eros.conf /etc/apache2/sites-available/eros.conf
RUN a2ensite eros.conf