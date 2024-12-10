FROM alpine:3.21.0
WORKDIR /var/www/html
#COPY ./ /var/www/html
RUN apk add php83
RUN apk add php83-curl
RUN apk add php83-apache2
RUN apk add apache2
RUN apk add curl
EXPOSE 80
CMD ["httpd", "-D", "FOREGROUND"]