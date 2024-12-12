certbot certonly --manual --agree-tos -m admin@tomas.buzz -d tomas.buzz -d *.tomas.buzz --expand --preferred-challenges=dns --server https://acme-v02.api.letsencrypt.org/directory
echo "Done :)"
sleep 5s