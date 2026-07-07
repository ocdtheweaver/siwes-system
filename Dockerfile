FROM php:8.2-apache

# libpq-dev is required to compile the pdo_pgsql extension
RUN apt-get update && apt-get install -y libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Enable the PDO PostgreSQL driver (required by config/db.php)
RUN docker-php-ext-install pdo pdo_pgsql

# Apache should serve this project as the document root directly,
# so visiting http://localhost:8080/ works with BASE_URL=''
WORKDIR /var/www/html

# Copy the project into the image. Locally, docker-compose's volume mount
# overrides this with your live folder - but Render builds from the
# Dockerfile alone with no volume mount, so without this COPY the deployed
# container would have no application files in it at all.
COPY . /var/www/html