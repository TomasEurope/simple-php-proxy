  sudo -i
  apt update
  apt upgrade -y
  apt install -y apache2
  apt install -y mysql-server
  apt install -y php8.3
  apt install -y php8.3-mysqli
  apt install -y php8.3-dom
  apt install -y php8.3-simplexml
  apt install -y libapache2-mod-php
  apt install -y composer
  apt install -y certbot python3-certbot-apache
  cd /var/www/html || echo "No html dir" || exit 1
  rm -f index.html
  chown -R www-data:www-data /var/www/html
  git config --global --add safe.directory /var/www/html
  git clone https://github.com/TomasEurope/simple-php-proxy .
  chmod +x ./bin/*
  sudo -H -u www-data bash -c 'cd /var/www/html && mkdir logs'
  sudo -H -u www-data bash -c 'cd /var/www/html && composer install'

  systemctl enable apache2
  systemctl start apache2
  systemctl enable mysql
  systemctl start mysql

  echo "Done :)"
  sleep 5s

  reboot