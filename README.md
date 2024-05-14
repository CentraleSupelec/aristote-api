# ARISTOTE

## Requirements

* Git
* Docker / docker-compose

## Local installation

### Copy environments file into local ones

* Environment variables
```
cp -f .env .env.local
```

* Environment variables for testing
```
cp -f .env.test .env.test.local
```
Make sure the database name and ports are accurate in the `DATABASE_URL`. Otherwise change the variable accordingly to the database definition in `docker-compose.yml`.
```
DATABASE_URL="postgresql://<POSTGRES_USER>:<POSTGRES_PASSWORD>@postgres-test:<PORT>/<POSTGRES_DB>?serverVersion=15&charset=utf8"
```

### Copy php fixer into a local file for customisation needs (optional)

```
cp -f .php-cs-fixer.dist.php .php-cs-fixer.php
```

### Add a pre-commit logic (optional)

Run this command to execute `pre-commit.sh` each time a commit is created:
```
ln -s ../../bin/pre-commit.sh .git/hooks/pre-commit
```

### Run the containers

```
docker-compose up --build -d
```

### Update the testing database

The previous command will automatically run the not already executed migrations. But the testing database needs to be updated accordingly. To do so run this command:
```
docker-compose exec -it php sh tests/init-test-database.sh
```

## URLs

### Local

* Web server : http://localhost:8080
* Admin dashboard : http://localhost:8080/admin/dashboard
* Profiler : http://localhost:8080/_profiler/
* Api documentation : http://localhost:8080/api/doc

## Testing

Command to run all tests:
```
docker-compose exec -it php php vendor/bin/phpunit
```

To run a given testing directory:
```
docker-compose exec -it php vendor/bin/phpunit tests/<SUBDIRECTORY_NAME>
```

## Other useful commands

* Run the php fixer
```
docker-compose exec -it php php vendor/bin/php-cs-fixer fix -v --dry-run
```

* Run rector
```
docker-compose exec -it php php vendor/bin/rector process --dry-run
```

 * Générer une migration :
```
docker-compose exec php symfony console make:migration
```

* Execute migrations:
```
docker-compose exec -it php bin/console app:sync-migrate
```

* Create an active admin with the console:
```
docker-compose exec -it php bin/console app:create-admin
```

### Oauth2

* Generate public and private keys to be used by the authorization server.
They keys will be stored under `config/jwt` as defined in `.env`.
```
docker-compose exec -it php openssl genrsa -out config/jwt/private.key 2048
docker-compose exec -it php openssl rsa -in config/jwt/private.key -pubout -out config/jwt/public.key
```
