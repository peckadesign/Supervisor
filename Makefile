composer:
	composer validate
	composer update --no-interaction --prefer-dist

phpstan:
	vendor/bin/phpstan analyse -l 6 src/ --no-progress --error-format github
