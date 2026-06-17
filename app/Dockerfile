FROM node:22-alpine

WORKDIR /usr/src/app

COPY docker/entrypoint.sh /usr/local/bin/helpdesk-app-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/helpdesk-app-entrypoint \
    && chmod +x /usr/local/bin/helpdesk-app-entrypoint

EXPOSE 4200

CMD ["sh", "/usr/local/bin/helpdesk-app-entrypoint"]
