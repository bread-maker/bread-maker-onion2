FROM php:alpine

EXPOSE 8000

ENV PHP php
ENV EMULATTION true

ADD root/etc /etc
ADD root/usr /usr
ADD root/www /www

RUN mknod /tmp/breadmaker_from_device p
RUN chmod 0777 /usr/share/breadmaker/breadmaker.sh

WORKDIR /www/api
CMD /usr/share/breadmaker/breadmaker.sh & /usr/local/bin/php -S 0.0.0.0:8000
