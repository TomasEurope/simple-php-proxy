killall -9 certbot
echo "Domain $1..."
certbot certonly --manual --agree-tos -m admin@$1 -d $1 -d *.$1 --expand --preferred-challenges=dns --server https://acme-v02.api.letsencrypt.org/directory
cp ../apache2/domain.conf /etc/apache2/sites-enabled/$1.conf
sed -i 's/domain.com/$1/' /etc/apache2/sites-enabled/$1.conf
echo "Done :)"