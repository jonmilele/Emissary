FROM php:8.2-apache
RUN docker-php-ext-install mysqli
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev cron \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && rm -rf /var/lib/apt/lists/*
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["entrypoint.sh"]
