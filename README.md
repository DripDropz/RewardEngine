# RewardEngine

## Prerequisite
- [Ubuntu](https://ubuntu.com/download/server)
- [Make](https://askubuntu.com/questions/161104/how-do-i-install-make)
- [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)
- Docker ([Part-1](https://docs.docker.com/engine/install/ubuntu/) & [Part-2](https://docs.docker.com/engine/install/linux-postinstall/))

## Local Install
- Open terminal and type `cd $HOME/Desktop`
- Clone repo `git clone git@github.com:DripDropz/RewardEngine.git`
- Switch to repo dir `cd $HOME/Desktop/RewardEngine`
- Copy `.env.example` as `.env` (then make necessary changes to `.env` file)
- Run `make build` to build & start the containers
- Application should be running locally at `http://localhost:8200`

### Available Make Commands (Local Development)
* `frontend-build` Rebuild frontend
* `frontend-watch` Runs `npm run dev` (vite watch/hot-reload mode) inside _rewardengine-web_ container
* `frontend-upgrade` Upgrades npm packages inside _rewardengine-web_ container
* `up` Restart all docker containers
* `down` Shutdown all docker containers
* `build` Rebuilds all docker containers
* `composer-install` Run composer install
* `db-migrate` Run database migration(s)
* `db-refresh` Drop all database tables, re-run the migration(s) with seeds
* `api-docs` Generates api documentation based on code annotations
* `tinker` Starts a new php artisan tinker session
* `status` View the status of all running containers
* `logs` View the logs out of all running containers
* `logs-web` View the logs out of `rewardengine-web` container only
* `logs-cardano-sidecar` View the logs out of `rewardengine-cardano-sidecar` container only
* `shell` Drop into an interactive shell inside _rewardengine-web_ container
* `stats` View the resource usage of all running containers
* `artisan` Execute Laravel `artisan` command inside _rewardengine-web_ container (e.g. usage: `make artisan COMMAND="make:model MyModel -m"`)

### Local Dev MySQL Container Connection Info
```
Host: 127.0.0.1
Port: 33100
User: rewardengine
Database: rewardengine
Password: 123456
```
