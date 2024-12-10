FROM alpine:3.21.0
WORKDIR /var/www/html
#COPY ./ /var/www/html
RUN apk add php83 php83-curl php83-apache2 php83-tokenizer php83-dom php83-simplexml apache2 curl
EXPOSE 80
#CMD ["sleep", "9999999"]
CMD ["httpd", "-D", "FOREGROUND"]