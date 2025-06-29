# Usa una imagen oficial de PHP con Apache
FROM php:8.2-apache

# Copia los archivos a la carpeta pública del servidor
COPY web/ /var/www/html/
COPY api/ /var/www/html/api/

# Habilita módulos necesarios (opcional: ajusta según tus necesidades)
RUN docker-php-ext-install pdo pdo_mysql

# Asigna permisos si es necesario
RUN chown -R www-data:www-data /var/www/html

# Expone el puerto 80 para Render
EXPOSE 80
