FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y \
        mariadb-server \
        mariadb-client \
        default-libmysqlclient-dev \
        pkg-config \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . /app

RUN chmod +x /app/start-render.sh \
    && mkdir -p /var/lib/mysql /var/run/mysqld \
    && chown -R root:root /var/lib/mysql /var/run/mysqld

ENV PORT=724
EXPOSE 724

CMD ["bash", "/app/start-render.sh"]
