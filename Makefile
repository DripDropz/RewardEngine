export COMPOSE_PROJECT_NAME=rewardengine
export COMPOSE_FILE=docker/docker-compose.yml

.PHONY: up
up:
	$(MAKE) down
	docker compose up -d
	$(MAKE) composer-install
	./docker/wait-for-mysql.sh
	$(MAKE) db-migrate

.PHONY: down
down:
	docker compose down --remove-orphans

.PHONY: build
build:
	docker compose build
	$(MAKE) up
	docker exec -it rewardengine-web bash -c "npm install && npm run build"

#
# Helper functions
#

.PHONY: composer-install
composer-install:
	docker exec -it rewardengine-web bash -c "composer install"

.PHONY: db-migrate
db-migrate:
	docker exec -it rewardengine-web bash -c "php artisan migrate"

.PHONY: db-refresh
db-refresh:
	docker exec -it rewardengine-web bash -c "php artisan migrate:fresh --seed"

.PHONY: tinker
tinker:
	docker exec -it rewardengine-web bash -c "php artisan tinker"

.PHONY: status
status:
	docker compose ps

.PHONY: logs
logs:
	docker compose logs -f --tail=100

.PHONY: logs-web
logs-web:
	docker compose logs -f --tail=100 rewardengine-web

.PHONY: shell
shell:
	docker exec -it rewardengine-web bash

.PHONY: stats
stats:
	docker stats rewardengine-web rewardengine-mysql rewardengine-redis

.PHONY: artisan
artisan:
	docker exec -it rewardengine-web bash -c "php artisan $(COMMAND)"
