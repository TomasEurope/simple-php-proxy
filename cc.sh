clear

echo -e "\n\n# psalm\n\n"
vendor/bin/psalm --show-info=true

echo -e "\n\n# phpstan\n\n"
vendor/bin/phpstan analyze

echo -e "\n\n# lint\n\n"
vendor/bin/parallel-lint --exclude vendor .

echo -e "\n\n# phpcs\n\n"
vendor/bin/phpcs --standard=PSR12 --extensions=php --tab-width=4 -sp src public