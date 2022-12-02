

### How to run tests locally
1. Run service locally via docker-compose
```
docker-compose up -d
```
2. Exec into the running container
```
docker exec -it app_laravel bash
```
3. Run PHPSpec tests
```
vendor/bin/phpspec run
```
4. Run PHPUnit tests
```
vendor/bin/phpunit
```
5. Run Behat tests
```
vendor/bin/behat
```
