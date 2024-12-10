clear

vendor/bin/psalm --show-info=true

vendor/bin/phpstan analyze

vendor/bin/parallel-lint --exclude vendor .

vendor/bin/phpcs --standard=PSR12 --extensions=php --tab-width=4 -sp src public