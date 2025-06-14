# PBPIS

Posėdžių balsavimo ir protokolavimo informacinė sistema - IS, kuri skirta Vilniaus universiteto Kauno fakulteto studijų programų komitetų elektroninių posėdžių administravimui bei jų protokolų generavimui. Ši IS kuriama, siekiant įgyvendinti Informacijos sistemų ir kibernetinės saugos „Kursinio darbo“ modulio keliamus reikalavimus.

## IS funkcijos
- posėdžių ir darinių valdymas;
- vartotojų balsavimo procesas;
- protokolų generavimas;
- vartotojų valdymas.

## Sistemos reikalavimai:
- IS leidžiama Linux aplinkoje (Rekomendojama Debian distribucija);
- Įrašyta [Docker Engine](https://docs.docker.com/engine/install/debian/) programinė įranga (privalo būti suinstaliuotas Docker Compose).

## Prisijungimo informacija:
Pirmą kartą paleidus IS, sukuriami šie vartotojai. Esant poreikiui, IT administratoriaus rolę turintis naudotojas gali juos ištrinti:
- **IT administratorius:** 
  - El. paštas: `admin@knf.vu.lt`
  - Slaptažodis: `admin123`
- **Sekretorius**
  - El. paštas: `meduolis.saunuolis@knf.vu.lt`
  - Slaptažodis: `sekre123`
- **Balsuojantysis**
  - El. paštas: `umede.garduole@knf.vu.lt`
  - Slaptažodis: `balsa123`

Įprastas DB prisijungimas (BŪTNA PASIKEISTI PRIEŠ PALEIDŽIANT PRODUCTION APLINKOJE):
- DB_CONNECTION=mysql
- DB_HOST=mysql
- DB_PORT=3306
- DB_DATABASE=pbpis
- DB_USERNAME=user
- DB_PASSWORD=secret

## Instaliacijos instrukcija

1. Įvykdykite šias komandas:
```shell
cd ~/Documents;
mkdir -p pbpis/docker;
cd pbpis;
git clone https://github.com/archlich03/pbpis.git;
cp ~/Documents/pbpis/pbpis/.env.example ~/Documents/pbpis/pbpis/.env
```
2. Į `~/Documents/pbpis/docker` aplanką įterpkite šiuos failus:
- **Dockerfile**
```dockerfile
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git zip unzip curl gnupg2 ca-certificates libpng-dev libjpeg-dev \
    libfreetype6-dev libonig-dev libpq-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd bcmath exif intl

# Install Node.js (v20+ recommended)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy Laravel files (including package.json, vite.config.js)
COPY pbpis/ .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install JS dependencies and build assets
RUN npm install && npm run build

# Set correct permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Copy .env if needed
COPY pbpis/.env /var/www/.env

# Entrypoint to run artisan tasks and start PHP-FPM
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
```
- **entrypoint.sh**
```sh
#!/bin/bash

# Wait for DB if needed (optional)
# sleep 10

# Laravel setup
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan key:generate

# Start PHP-FPM
exec php-fpm
```
- **nginx.conf**
```conf
server {
    listen 80;
    server_name localhost;
    root /var/www/public;

    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass pbpis:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```
3. Į `~/Documents/pbpis` įterpkite šį failą:
- **docker-compose.yml**
```yml
services:
  pbpis:
    build:
      context: .
      dockerfile: docker/Dockerfile
    container_name: pbpis
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./pbpis:/var/www
    networks:
      - laravel
    depends_on:
      - mysql

  nginx:
    image: nginx:alpine
    container_name: nginx
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - ./pbpis:/var/www
      - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - pbpis
    networks:
      - laravel

  mysql:
    image: mysql:8
    container_name: mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: pbpis
      MYSQL_ROOT_PASSWORD: root
      MYSQL_USER: user
      MYSQL_PASSWORD: secret
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - laravel

networks:
  laravel:

volumes:
  mysql_data:
```
4. Pakeiskite DB prisijungimo duomenis `.env` ir `docker-compose.yml` failuose.
5. Paleiskite aplikaciją:
```sh
cd ~/Documents/pbpis;
sudo docker compose up -d;
```
6. Įvykdyti DB migracijas: `sudo docker exec pbpis php artisan migrate`
7. Atidarykite web aplikaciją per naršyklę: `http://localhost:8000`

## Licencija

PBPIS veikia su GNU GPLv3 licencija.
