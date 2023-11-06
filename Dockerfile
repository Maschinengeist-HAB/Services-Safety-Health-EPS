FROM php:8.2-alpine

LABEL org.opencontainers.image.source="https://github.com/Maschinengeist-HAB/Services-Safety-Health-EPS"
LABEL org.opencontainers.image.description=""
LABEL org.opencontainers.image.licenses="MIT"

COPY Service /opt/Service
COPY Library /opt/Library
ENV VERBOSE=false

VOLUME [ "/opt/Service" ]
WORKDIR "/opt/Service/"
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
CMD ["sh", "./Entry.sh"]