composer:
	composer validate
	composer install --no-interaction --prefer-dist

phpstan:
	vendor/bin/phpstan analyse -l 5 src/ --error-format github
