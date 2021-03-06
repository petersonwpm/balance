.PHONY: all
all: install

config/autoload/balance_modules.local.php:
	echo '<?php return [];' > config/autoload/balance_modules.local.php
	chmod 666 config/autoload/balance_modules.local.php

.PHONY: install
install: config/autoload/balance_modules.local.php dependencies database

.PHONY: uninstall
uninstall: dependencies
	php vendor/bin/phinx migrate -t0

.PHONY: tests
tests: install
	php vendor/bin/phpunit --coverage-text=php://stdout --coverage-clover=build/coverage.xml --whitelist module/Balance/src
	php vendor/bin/phpcpd module/Balance
	php vendor/bin/parallel-lint module/Balance
	php vendor/bin/phpcs --standard=phpcs.xml --runtime-set ignore_warnings_on_exit true --encoding=utf-8 --extensions=php,phtml --colors --ignore=module/Balance/migrations module/Balance
	php vendor/bin/php-cs-fixer fix --config-file=php-cs-fixer.php --dry-run --diff
	php vendor/bin/phpmd module/Balance text phpmd.xml

.PHONY: reports
reports: dependencies
	php vendor/bin/phploc --log-xml=build/phploc.xml module/Balance/src

.PHONY: api
api:
	apigen generate --source module/Balance/src --destination module/Balance/docs/gh-pages/api/latest --template-theme bootstrap --title "Balance `git describe --tag`" --tree

.PHONY: clean
clean:
	rm -rf build

# Dependências

.PHONY: dependencies
dependencies:
	composer install
	bower install

.PHONY: database
database:
	php vendor/bin/phinx migrate
