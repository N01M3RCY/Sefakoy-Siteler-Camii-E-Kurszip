FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y \
        default-libmysqlclient-dev \
        pkg-config \
        ca-certificates \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . /app

RUN chmod +x /app/start-render.sh

ENV PORT=724
EXPOSE 724

CMD ["bash", "/app/start-render.sh"]
