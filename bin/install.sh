  sudo -i
  apt update
  apt upgrade -y
  apt install -y apache2
  apt install -y mysql-server
  apt install -y php8.3
  apt install -y php8.3-mysqli
  apt install -y php8.3-dom
  apt install -y php8.3-gd
  apt install -y php8.3-simplexml
  apt install -y libapache2-mod-php
  apt install -y composer
  apt install -y certbot python3-certbot-apache
  cd /var/www/html || echo "No html dir" || exit 1
  rm -f index.html
  chown -R www-data:www-data /var/www
  git config --global --add safe.directory /var/www/html
  git clone https://github.com/TomasEurope/simple-php-proxy .
  chmod +x ./bin/*
  sudo -H -u www-data bash -c 'cd /var/www/html && mkdir logs'
  sudo -H -u www-data bash -c 'cd /var/www/html && composer install'
  sudo -H -u www-data bash -c 'cd /var/www/ && mkdir piwik'
  chown -R www-data:www-data /var/www

  sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
  a2enmod ssl

  cd /var/www || echo "No www dir" || exit 1
  wget https://builds.matomo.org/matomo-latest.zip
  unzip matomo-latest.zip
  chown -R www-data:www-data /var/www/matomo
  find /var/www/matomo/tmp -type f -exec chmod 644 {} \;
  find /var/www/matomo/tmp -type d -exec chmod 755 {} \;
  find /var/www/matomo/tmp/assets/ -type f -exec chmod 644 {} \;
  find /var/www/matomo/tmp/assets/ -type d -exec chmod 755 {} \;
  find /var/www/matomo/tmp/cache/ -type f -exec chmod 644 {} \;
  find /var/www/matomo/tmp/cache/ -type d -exec chmod 755 {} \;
  find /var/www/matomo/tmp/logs/ -type f -exec chmod 644 {} \;
  find /var/www/matomo/tmp/logs/ -type d -exec chmod 755 {} \;
  find /var/www/matomo/tmp/tcpdf/ -type f -exec chmod 644 {} \;
  find /var/www/matomo/tmp/tcpdf/ -type d -exec chmod 755 {} \;
  find /var/www/matomo/tmp/templates_c -type f -exec chmod 644 {} \;
  find /var/www/matomo/tmp/templates_c -type d -exec chmod 755 {} \;

  systemctl enable apache2
  systemctl start apache2
  systemctl enable mysql
  systemctl start mysql

  mysql -u root -p
  #CREATE DATABASE matomo;
  #GRANT ALL PRIVILEGES ON matomo.* TO 'root'@'localhost';
  #FLUSH PRIVILEGES;
  #exit

  echo "Done :)"
  sleep 5s

  reboot