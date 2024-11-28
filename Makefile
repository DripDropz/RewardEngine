export COMPOSE_PROJECT_NAME=rewardengine
export COMPOSE_FILE=docker/docker-compose.yml

.PHONY: up
up:
	$(MAKE) down
	docker compose up -d
	$(MAKE) composer-install
	./docker/wait-for-mysql.sh
	$(MAKE) db-migrate
	$(MAKE) frontend-build

.PHONY: down
down:
	docker compose down --remove-orphans

.PHONY: build
build:
	docker compose build
	$(MAKE) up
	$(MAKE) frontend-build

#
# Helper functions
#

.PHONY: frontend-build
frontend-build:
	docker exec -it rewardengine-web bash -c "npm install && npm run build"

.PHONY: frontend-watch
frontend-watch:
	docker exec -it rewardengine-web bash -c "npm install && npm run dev"

.PHONY: frontend-upgrade
frontend-upgrade:
	docker exec -it rewardengine-web bash -c "npm update"

.PHONY: composer-install
composer-install:
	docker exec -it rewardengine-web bash -c "composer install"

.PHONY: deploy-sidecar
deploy-sidecar:
	# docker exec -it rewardengine-web bash -c "php artisan sidecar:deploy --activate --env=local"
	# docker exec -it rewardengine-web bash -c "php artisan sidecar:deploy --activate --env=staging"
	docker exec -it rewardengine-web bash -c "php artisan sidecar:deploy --activate --env=production"

.PHONY: db-migrate
db-migrate:
	docker exec -it rewardengine-web bash -c "php artisan migrate"

.PHONY: db-refresh
db-refresh:
	docker exec -it rewardengine-web bash -c "php artisan migrate:fresh --seed"

.PHONY: api-docs
api-docs:
	docker exec -it rewardengine-web bash -c "php artisan scribe:generate --force"

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

.PHONY: logs-cardano-sidecar
logs-cardano-sidecar:
	docker compose logs -f --tail=100 rewardengine-cardano-sidecar

.PHONY: shell
shell:
	docker exec -it rewardengine-web bash

.PHONY: stats
stats:
	docker stats rewardengine-web rewardengine-mysql rewardengine-redis rewardengine-cardano-sidecar

.PHONY: artisan
artisan:
	docker exec -it rewardengine-web bash -c "php artisan $(COMMAND)"
