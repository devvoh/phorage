vendor/autoload.php:
	@composer install \
		--no-interaction \
		--no-plugins \
		--no-scripts

phpunit: vendor/autoload.php
	@vendor/bin/phpunit tests

phpstan:
	@vendor/bin/phpstan analyse --level 8 src

phpstan-strict:
	@vendor/bin/phpstan analyse --level max src

php-cs-fixer:
	@vendor/bin/php-cs-fixer fix src -vvv --using-cache no

quality: php-cs-fixer phpstan phpunit
