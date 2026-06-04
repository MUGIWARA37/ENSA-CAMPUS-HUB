export IMAGE_NAME ?= ensa-campus-hub-db
export CONTAINER_NAME ?= ensa-campus-hub-db
export DB_PORT ?= 3307
export MYSQL_ROOT_PASSWORD ?= DB_password105@
export MYSQL_DATABASE ?= club_management
export PHP_HOST ?= 127.0.0.1
export PHP_PORT ?= 8001
export WEB_URL ?= http://$(PHP_HOST):$(PHP_PORT)/index.php

.PHONY: build run stop rm logs

build:
	docker compose build

run:
	docker compose up -d --build
	@echo "Open: $(WEB_URL)"
	php -S $(PHP_HOST):$(PHP_PORT) -t .

stop:
	docker compose stop

rm:
	docker compose down

logs:
	docker compose logs -f
