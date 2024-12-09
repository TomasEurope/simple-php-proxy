
vendor/bin/psalm

vendor/bin/phpstan analyze

vendor/bin/parallel-lint --exclude vendor .

vendor/bin/phpcs --standard=SlevomatCodingStandard --extensions=php --tab-width=4 -sp src public