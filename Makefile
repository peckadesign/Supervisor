composer:
	composer validate
	composer update --no-interaction --prefer-dist

phpstan:
	vendor/bin/phpstan analyse -l 6 -c phpstan.neon src/ --no-progress --error-format github

tester:
	vendor/bin/tester tests
